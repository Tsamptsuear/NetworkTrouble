<?php
/**
 * NetworkTrouble - RESTful API: AI Symptom Interpreter
 * Converts unstructured natural language complaints into structured symptom JSON.
 *
 * Accepted input (JSON body or POST form):
 *   user_text  string   Required. Raw complaint text from user.
 *   debug      bool     Optional. If true AND requester is admin, include debug_log.
 *   pipeline   '0'|'1'  Optional. If '1', also run NetworkClassificationEngine.
 *
 * Response format:
 *   { status, source, data, diagnosis?, input_text, debug_log? }
 *
 * HTTP Status Codes:
 *   200 → success
 *   400 → bad request (empty / missing user_text)
 *   500 → AI or pipeline error
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// Prevent caching of test results
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/ai.php';

// ─── Parse Input ────────────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

$userText     = trim($data['user_text'] ?? '');
$wantPipeline = !empty($data['pipeline']) && $data['pipeline'] !== '0';

// ─── Validate ────────────────────────────────────────────────────
if ($userText === '') {
    http_response_code(400);
    echo json_encode([
        'status'        => 'error',
        'error_code'    => 'empty_input',
        'error_message' => 'Parameter user_text tidak boleh kosong.',
        'input_text'    => '',
        'data'          => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Debug Mode (admin-only, session-gated) ──────────────────────
$isAdminSession = false;
if (!empty($data['debug']) || !empty($_GET['debug'])) {
    // Session already started by config/app.php
    $isAdminSession = !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_role']);
}
$enableDebug = $isAdminSession; // only admins get debug_log

// ─── Run Interpreter ─────────────────────────────────────────────
$result = AISymptomInterpreter::interpret($userText, $enableDebug);

// Always include the actual input text that was analysed
$result['input_text'] = $userText;

// ─── Optional: Full Pipeline (Interpret → Classify) ─────────────
if ($wantPipeline) {
    require_once __DIR__ . '/../includes/classification_engine.php';

    if ($result['status'] === 'success' && !empty($result['data'])) {
        $d = $result['data'];

        // ── Map interpreter output → classification engine input ──
        $classificationInput = [
            'connection_type'       => $d['connection_type'] ?? 'unknown',
            'symptoms'              => [], // AI provides slugs, not DB IDs
            'adaptive_answers'      => [],
            'custom_text'           => $userText,
            'classification_source' => $result['source'] ?? 'AI-Assisted'
        ];

        // adaptive_hints from Mock — richer signal than individual bool flags
        $hints = (array)($d['adaptive_hints'] ?? []);

        // Physical link down → no link signal on port
        if (in_array('physical', $hints, true)) {
            $classificationInput['adaptive_answers']['lan_led'] = 'off';
        }

        // DHCP failure → no gateway reachable
        if (in_array('dhcp', $hints, true)) {
            $classificationInput['adaptive_answers']['ping_gateway'] = 'no';
        }

        // DNS issue → can ping IP but not domains
        if (in_array('dns', $hints, true) || in_array('dns_partial', $hints, true)) {
            $classificationInput['adaptive_answers']['ping_ip_ok']    = 'yes';
            $classificationInput['adaptive_answers']['wifi_status']   = 'connected'; // implies L1-L3 ok
        }

        // Performance issue — connected + internet but poor quality
        // No specific adaptive answer needed; keyword matching will score cat 7

        // Wireless drop → SSID lost
        if (in_array('wireless_drop', $hints, true)) {
            $classificationInput['adaptive_answers']['wifi_status'] = 'missing';
        }

        // General "connected but no internet" (tanda seru case) → WiFi status connected
        if (($d['connected'] ?? true) && !($d['internet_access'] ?? true)
            && !in_array('physical', $hints, true)
            && !in_array('dhcp', $hints, true)
            && !in_array('dns', $hints, true)) {
            // Could be gateway or NAT problem
            if (($d['connection_type'] ?? 'unknown') === 'wifi') {
                $classificationInput['adaptive_answers']['wifi_status'] = 'connected';
            } else {
                $classificationInput['adaptive_answers']['ping_gateway'] = 'no';
            }
        }

        // Other devices
        if ($d['other_devices_working'] === true) {
            $classificationInput['adaptive_answers']['other_devices'] = 'yes';
        } elseif ($d['other_devices_working'] === false) {
            $classificationInput['adaptive_answers']['other_devices'] = 'no';
        }

        // Connected state
        if (!($d['connected'] ?? true)) {
            $classificationInput['adaptive_answers']['ping_gateway'] = 'no';
        }

        try {
            $diagnosis = NetworkClassificationEngine::diagnose($classificationInput);
            $result['diagnosis'] = $diagnosis;
        } catch (Throwable $e) {
            $result['diagnosis'] = [
                'error'   => 'Pipeline classify error: ' . $e->getMessage(),
                'status'  => 'error'
            ];
            http_response_code(500);
        }
    } else {
        // Interpreter failed; return pipeline not run
        $result['diagnosis'] = null;
        http_response_code(500);
    }
}

// ─── Set HTTP Status ─────────────────────────────────────────────
if ($result['status'] === 'error') {
    $ec = $result['error_code'] ?? '';
    http_response_code(in_array($ec, ['empty_input'], true) ? 400 : 500);
} else {
    http_response_code(200);
}

// ─── Strip debug_log if not admin ────────────────────────────────
if (!$enableDebug) {
    unset($result['debug_log']);
}

// ─── Output ──────────────────────────────────────────────────────
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
