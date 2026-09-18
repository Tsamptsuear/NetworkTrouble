<?php
/**
 * NetworkTrouble - Modular AI Configuration & Symptom Interpreter
 * Supports Mock (local demo), OpenAI, Google Gemini, and DeepSeek endpoints.
 * 
 * Architecture:
 *   interpret() → router → adapter (Mock | OpenAI | Gemini | DeepSeek)
 *   testConnection() → lightweight ping to verify API key & endpoint
 * 
 * Debug mode collects step-by-step trace info without leaking secrets.
 */

require_once __DIR__ . '/database.php';

class AISymptomInterpreter {

    /** Required fields in structured AI output */
    private const REQUIRED_FIELDS = ['connection_type', 'connected', 'internet_access'];

    /** System prompt shared across all LLM providers */
    private const SYSTEM_PROMPT = "You are a specialized Network Symptom Interpreter. Extract strictly JSON representation of network symptoms from the user's issue description. Do NOT diagnose or recommend fixes. Only extract structured symptoms.\n"
        . "Output format JSON:\n"
        . "{\n"
        . "  \"connection_type\": \"wifi\" | \"lan\" | \"fiber\" | \"unknown\",\n"
        . "  \"connected\": boolean,\n"
        . "  \"internet_access\": boolean,\n"
        . "  \"other_devices_working\": boolean | null,\n"
        . "  \"extracted_keywords\": [\"string\"],\n"
        . "  \"possible_symptoms\": [\"string\"],\n"
        . "  \"summary\": \"short summary in Indonesian\"\n"
        . "}";

    // ─────────────────────────────────────────────────────────────
    //  Settings Loader
    // ─────────────────────────────────────────────────────────────

    private static function getSettings(): array {
        $defaults = [
            'provider'     => 'mock',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'api_key'      => '',
            'model_name'   => 'gpt-4o-mini',
            'enabled'      => 1,
            'timeout'      => 8
        ];

        $db = get_db();
        if ($db) {
            try {
                $stmt = $db->query("SELECT * FROM ai_settings LIMIT 1");
                $res = $stmt->fetch();
                if ($res) {
                    return array_merge($defaults, $res);
                }
            } catch (Exception $e) {}
        }
        return $defaults;
    }

    // ─────────────────────────────────────────────────────────────
    //  Main Interpret Entry Point
    // ─────────────────────────────────────────────────────────────

    /**
     * Interpret unstructured user input text into structured symptom JSON.
     * Does NOT make diagnosis. Only extracts features for the Rule Engine.
     *
     * @param string $userInput  The raw complaint text
     * @param bool   $debug      If true, collects step-by-step trace info
     * @return array  Structured result with 'status', 'source', 'data', and optionally 'debug_log'
     */
    public static function interpret(string $userInput, bool $debug = false): array {
        $debugLog = [];
        $settings = self::getSettings();

        if ($debug) {
            $debugLog[] = ['step' => 'load_settings', 'provider' => $settings['provider'], 'enabled' => (bool)$settings['enabled'], 'has_api_key' => !empty($settings['api_key']), 'model' => $settings['model_name'], 'timeout' => (int)$settings['timeout']];
        }

        if (empty(trim($userInput))) {
            $result = [
                'status'  => 'empty',
                'source'  => 'Rule-Based',
                'data'    => null
            ];
            if ($debug) {
                $debugLog[] = ['step' => 'validation', 'result' => 'empty_input'];
                $result['debug_log'] = $debugLog;
            }
            return $result;
        }

        // Route to appropriate adapter
        if (empty($settings['enabled']) || $settings['provider'] === 'mock' || empty($settings['api_key'])) {
            $reason = 'mock_selected';
            if (empty($settings['enabled'])) $reason = 'ai_disabled';
            elseif (empty($settings['api_key'])) $reason = 'no_api_key';

            if ($debug) {
                $debugLog[] = ['step' => 'routing', 'adapter' => 'mock', 'reason' => $reason];
            }

            $result = self::runMockInterpreter($userInput);
            if ($debug) {
                $debugLog[] = ['step' => 'mock_complete', 'status' => $result['status']];
                $result['debug_log'] = $debugLog;
            }
            return $result;
        }

        // External API routing
        $provider = strtolower($settings['provider']);
        if ($debug) {
            $debugLog[] = ['step' => 'routing', 'adapter' => $provider, 'endpoint' => self::maskUrl($settings['api_endpoint'])];
        }

        switch ($provider) {
            case 'gemini':
                $result = self::interpretWithGemini($userInput, $settings, $debug, $debugLog);
                break;
            case 'deepseek':
                $result = self::interpretWithDeepSeek($userInput, $settings, $debug, $debugLog);
                break;
            case 'openai':
            default:
                $result = self::interpretWithOpenAI($userInput, $settings, $debug, $debugLog);
                break;
        }

        if ($debug) {
            $result['debug_log'] = $debugLog;
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    //  Test Connection (Lightweight API health check)
    // ─────────────────────────────────────────────────────────────

    /**
     * Quick connectivity test — verifies endpoint & API key without running full interpretation.
     * @return array  ['status' => 'ok'|'error', 'message' => string, 'latency_ms' => int]
     */
    public static function testConnection(): array {
        $settings = self::getSettings();
        $provider = strtolower($settings['provider']);

        if ($provider === 'mock') {
            return ['status' => 'ok', 'message' => 'Mock engine aktif — tidak memerlukan koneksi API eksternal.', 'provider' => 'mock', 'latency_ms' => 0];
        }

        if (empty($settings['api_key'])) {
            return ['status' => 'error', 'message' => 'API Key belum dikonfigurasi.', 'provider' => $provider, 'latency_ms' => 0];
        }

        // Build a minimal request to test auth
        $startTime = microtime(true);

        switch ($provider) {
            case 'gemini':
                $model = $settings['model_name'] ?: 'gemini-1.5-flash';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $settings['api_key'];
                $payload = json_encode([
                    'contents' => [['parts' => [['text' => 'ping']]]],
                    'generationConfig' => ['maxOutputTokens' => 5]
                ]);
                $headers = ['Content-Type: application/json'];
                break;

            case 'deepseek':
                $url = rtrim($settings['api_endpoint'] ?: 'https://api.deepseek.com/chat/completions', '/');
                $payload = json_encode([
                    'model' => $settings['model_name'] ?: 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                    'max_tokens' => 5
                ]);
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $settings['api_key']
                ];
                break;

            case 'openai':
            default:
                $url = rtrim($settings['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions', '/');
                $payload = json_encode([
                    'model' => $settings['model_name'] ?: 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                    'max_tokens' => 5
                ]);
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $settings['api_key']
                ];
                break;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = (int)((microtime(true) - $startTime) * 1000);

        if ($curlError) {
            return [
                'status'     => 'error',
                'message'    => "Koneksi gagal: {$curlError}",
                'provider'   => $provider,
                'latency_ms' => $latencyMs
            ];
        }

        if ($httpCode === 401 || $httpCode === 403) {
            return [
                'status'     => 'error',
                'message'    => "Autentikasi gagal (HTTP {$httpCode}). Periksa API Key Anda.",
                'provider'   => $provider,
                'latency_ms' => $latencyMs
            ];
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);
            $errorMsg = $decoded['error']['message'] ?? "HTTP Error {$httpCode}";
            return [
                'status'     => 'error',
                'message'    => "API Error: {$errorMsg}",
                'provider'   => $provider,
                'latency_ms' => $latencyMs
            ];
        }

        return [
            'status'     => 'ok',
            'message'    => "Koneksi berhasil ke {$provider} API (HTTP {$httpCode}, {$latencyMs}ms).",
            'provider'   => $provider,
            'latency_ms' => $latencyMs
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Adapter: Mock (Local deterministic NLP)
    // ─────────────────────────────────────────────────────────────

    /**
     * Local Mock NLP Parser — Semantically differentiated by problem profile.
     * Produces clearly different outputs for: Performance, DNS, Physical Layer,
     * DHCP, Wireless drop, IP conflict, Specific-site issues, and general no-internet.
     */
    private static function runMockInterpreter(string $text): array {
        $lower = mb_strtolower(trim($text), 'UTF-8');

        // ── 1. Detect Connection Type ────────────────────────────
        $connType = 'unknown';
        if (preg_match('/(wifi|wi-fi|wireless|hotspot|ssid|wlan|access\s*point|ap\b)/i', $lower)) {
            $connType = 'wifi';
        } elseif (preg_match('/(kabel|lan\b|ethernet|rj45|port\s*lan|utp|switch|hub)/i', $lower)) {
            $connType = 'lan';
        } elseif (preg_match('/(modem|fiber|fo\b|indihome|biznet|firstmedia|myrepublic|ont\b)/i', $lower)) {
            $connType = 'fiber';
        }

        // ── 2. Possible Symptoms (rich semantic matching) ─────────
        $possibleSymptoms = [];
        $adaptiveHints    = []; // internal hints for classifier mapping

        // Performance — lambat, latency, ping tinggi, packet loss
        $isPerformance = (bool)preg_match(
            '/(lambat|lemot|lelet|slow|buffering|ping\s*tinggi|ping\s*\d+\s*ms|latency|latensi|delay|jitter|rto|packet\s*loss|kecepatan\s*rendah|bandwidth|unduh\s*lama|upload\s*lama|nonton\s*macet|video\s*macet|game\s*lag)/i',
            $lower
        );
        if ($isPerformance) {
            $possibleSymptoms[] = 'performance_degradation';
            $adaptiveHints[]    = 'performance';
        }

        // DNS — website tidak bisa dibuka, DNS error, nxdomain, name not resolved
        $isDns = (bool)preg_match(
            '/(dns|nxdomain|server\s*not\s*found|dns_probe|name\s*not\s*resolv|tidak\s*bisa\s*buka\s*web|tidak\s*bisa\s*akses\s*web|web\s*tidak\s*bisa\s*dibuka|domain\s*tidak\s*bisa|bisa\s*ping\s*(ip|8\.8)|tapi\s*(web|website|browser)\s*(tidak|ga|gak|nggak)\s*bisa)/i',
            $lower
        );
        if ($isDns) {
            $possibleSymptoms[] = 'dns_resolution_failure';
            $adaptiveHints[]    = 'dns';
        }

        // Specific site (partial DNS/routing issue)
        $isSpecificSite = (bool)preg_match(
            '/(website\s*tertentu|situs\s*tertentu|hanya\s*(satu|beberapa|sebagian)\s*web|satu\s*situs|sebagian\s*web|web\s*lain\s*bisa|situs\s*lain\s*bisa)/i',
            $lower
        );
        if ($isSpecificSite && !$isDns) {
            $possibleSymptoms[] = 'dns_resolution_failure';
            $adaptiveHints[]    = 'dns_partial';
        }

        // Physical — kabel tidak terdeteksi, lampu mati, RJ45, port rusak
        $isPhysical = (bool)preg_match(
            '/(tidak\s*terdeteksi|kabel\s*(lan\s*)?(tidak|putus|lepas|rusak)|lampu\s*(port|eth|lan|switch)\s*(mati|padam|tidak\s*nyala)|rj45\s*(longgar|copot|rusak)|port\s*(rusak|mati)|link\s*down|tidak\s*ada\s*sinyal\s*kabel|nic\s*(mati|error|rusak))/i',
            $lower
        );
        if ($isPhysical) {
            $possibleSymptoms[] = 'physical_link_down';
            $adaptiveHints[]    = 'physical';
        }

        // DHCP / IP failure — 169.254, APIPA, tidak dapat IP, DHCP
        $isDhcp = (bool)preg_match(
            '/(169\.254|apipa|tidak\s*(memperoleh|mendapat|dapat)\s*ip|gagal\s*(dapat|memperoleh)\s*ip|dhcp\s*(gagal|fail|error|tidak\s*merespons)|automatic\s*private|ip\s*(address)?\s*tidak\s*muncul|no\s*ip\s*address)/i',
            $lower
        );
        if ($isDhcp) {
            $possibleSymptoms[] = 'dhcp_lease_failure';
            $adaptiveHints[]    = 'dhcp';
        }

        // IP conflict
        $isIpConflict = (bool)preg_match(
            '/(konflik\s*ip|ip\s*conflict|tabrakan\s*ip|ip\s*(yang\s*)?(sama|duplikat|double)|duplicate\s*ip|address\s*conflict)/i',
            $lower
        );
        if ($isIpConflict) {
            $possibleSymptoms[] = 'ip_address_conflict';
            $adaptiveHints[]    = 'ip_conflict';
        }

        // Wireless drop — SSID hilang, sinyal lemah, sering putus, roaming
        $isWirelessDrop = (bool)preg_match(
            '/(ssid\s*(tidak\s*muncul|hilang|menghilang)|sinyal\s*(wifi\s*)?(lemah|jelek|buruk|kecil)|wifi\s*(sering\s*)?putus|putus-putus|disconnect\s*terus|sinyal\s*naik\s*turun|roaming\s*fail|tidak\s*bisa\s*connect\s*ke\s*wifi|wifi\s*tidak\s*terdeteksi)/i',
            $lower
        );
        if ($isWirelessDrop) {
            $possibleSymptoms[] = 'wireless_signal_drop';
            $adaptiveHints[]    = 'wireless_drop';
        }

        // Security hints
        $isSecurity = (bool)preg_match(
            '/(arp\s*spoof|rogue\s*dhcp|man.in.the.middle|dns\s*hijack|redirect\s*palsu|phishing|website\s*palsu|gateway\s*palsu)/i',
            $lower
        );
        if ($isSecurity) {
            $possibleSymptoms[] = 'security_anomaly';
            $adaptiveHints[]    = 'security';
        }

        // ── 3. Connectivity State ─────────────────────────────────
        $connected = true;
        if ($isPhysical) {
            $connected = false; // Physical layer down → not connected
        } elseif (preg_match(
            '/(tidak\s*(bisa\s*)?(terhubung|connect|konek|sambung)|putus\s*total|gagal\s*sambung|silang\s*merah|cannot\s*connect|disconnected|no\s*connection)/i',
            $lower
        )) {
            $connected = false;
        }

        $internetAccess = true;
        if (!$connected) {
            $internetAccess = false; // Physical disconnect → no internet
        } elseif ($isDhcp) {
            $internetAccess = false; // No IP from DHCP → no internet
        } elseif ($isPerformance) {
            $internetAccess = true; // Slow but still connected
        } elseif ($isSpecificSite && !$isDns) {
            $internetAccess = true; // Partial access — some sites work
        } elseif (preg_match(
            '/(tidak\s*(ada|bisa|dapat|bisa\s*digunakan|dapat\s*digunakan)?\s*internet|no\s*internet|g(a|ak|k)\s*(ada|bisa)?\s*internet|internet\s*(mati|terputus|tidak\s*ada)|tidak\s*bisa\s*(browsing|digunakan|dipakai|akses)|offline|tanda\s*seru|exclamation|cannot\s*browse|jaringan\s*mati)/i',
            $lower
        )) {
            $internetAccess = false;
        }

        // ── 4. Other Devices ──────────────────────────────────────
        $otherDevicesWorking = null;
        if (preg_match('/(hp\s*bisa|perangkat\s*lain\s*normal|laptop\s*lain\s*bisa|hanya\s*(pc|laptop|saya|komputer\s*saya)|device\s*lain\s*(aman|bisa|normal)|hp\s*normal|ponsel\s*normal)/i', $lower)) {
            $otherDevicesWorking = true;
        } elseif (preg_match('/(semua\s*perangkat|semua\s*orang|satu\s*kantor|semua\s*(laptop|komputer|hp)|seluruh\s*jaringan|jaringan\s*mati\s*semua)/i', $lower)) {
            $otherDevicesWorking = false;
        }

        // ── 5. Build Semantic Summary ─────────────────────────────
        $summaryParts = [];
        if ($isPhysical)        $summaryParts[] = 'masalah fisik (kabel/port)';
        if ($isDhcp)            $summaryParts[] = 'kegagalan DHCP / tidak ada IP';
        if ($isDns)             $summaryParts[] = 'DNS resolution gagal';
        if ($isSpecificSite && !$isDns) $summaryParts[] = 'akses parsial (situs tertentu bermasalah)';
        if ($isWirelessDrop)    $summaryParts[] = 'sinyal WiFi tidak stabil';
        if ($isPerformance)     $summaryParts[] = 'performa rendah / latensi tinggi';
        if ($isIpConflict)      $summaryParts[] = 'konflik alamat IP';
        if ($isSecurity)        $summaryParts[] = 'anomali keamanan jaringan';

        if ($connected && !$internetAccess && empty($summaryParts)) {
            $summaryParts[] = 'terhubung ke jaringan lokal namun tidak ada akses internet';
        }
        if (empty($summaryParts)) {
            $summaryParts[] = 'gejala umum jaringan, belum cukup spesifik';
        }

        $summary = 'Terdeteksi: ' . implode('; ', $summaryParts) . '. Koneksi: '
            . ($connType !== 'unknown' ? strtoupper($connType) : 'tidak diketahui') . '.';

        return [
            'status' => 'success',
            'source' => 'Mock NLP Engine',
            'data'   => [
                'connection_type'       => $connType,
                'connected'             => $connected,
                'internet_access'       => $internetAccess,
                'other_devices_working' => $otherDevicesWorking,
                'extracted_keywords'    => self::extractKeywords($lower),
                'possible_symptoms'     => array_values(array_unique($possibleSymptoms)),
                'adaptive_hints'        => array_values(array_unique($adaptiveHints)), // internal, for pipeline
                'summary'               => $summary
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Adapter: OpenAI (GPT-4o-mini, GPT-4o, etc.)
    // ─────────────────────────────────────────────────────────────

    private static function interpretWithOpenAI(string $text, array $settings, bool $debug, array &$debugLog): array {
        $url = rtrim($settings['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions', '/');
        $model = $settings['model_name'] ?: 'gpt-4o-mini';

        $payload = [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $text]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.1,
            'max_tokens'      => 350
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['api_key']
        ];

        if ($debug) {
            $debugLog[] = ['step' => 'openai_request', 'url' => self::maskUrl($url), 'model' => $model];
        }

        $apiResult = self::executeCurlRequest($url, $payload, $headers, (int)($settings['timeout'] ?: 8));

        if ($debug) {
            $debugLog[] = ['step' => 'openai_response', 'http_code' => $apiResult['http_code'], 'curl_error' => $apiResult['curl_error'] ?: null, 'response_preview' => self::truncate($apiResult['response'], 300)];
        }

        if ($apiResult['curl_error']) {
            return self::buildErrorResult('OpenAI', 'curl_error', $apiResult['curl_error'], $text);
        }

        if ($apiResult['http_code'] >= 400) {
            $decoded = json_decode($apiResult['response'], true);
            $msg = $decoded['error']['message'] ?? "HTTP {$apiResult['http_code']}";
            return self::buildErrorResult('OpenAI', 'api_error', $msg, $text);
        }

        // Parse OpenAI response format
        $decoded = json_decode($apiResult['response'], true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if ($debug) {
            $debugLog[] = ['step' => 'openai_parse', 'has_choices' => isset($decoded['choices']), 'has_content' => !empty($content)];
        }

        return self::parseAndValidateAIOutput($content, 'OpenAI (GPT)', $text, $debug, $debugLog);
    }

    // ─────────────────────────────────────────────────────────────
    //  Adapter: Google Gemini (proper Gemini API format)
    // ─────────────────────────────────────────────────────────────

    private static function interpretWithGemini(string $text, array $settings, bool $debug, array &$debugLog): array {
        $model = $settings['model_name'] ?: 'gemini-1.5-flash';
        // Gemini uses API key in URL, not Authorization header
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $settings['api_key'];

        // Gemini uses 'contents' format, not 'messages'
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => self::SYSTEM_PROMPT . "\n\nUser complaint:\n" . $text]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'    => 0.1,
                'maxOutputTokens' => 350,
                'responseMimeType' => 'application/json'
            ]
        ];

        $headers = ['Content-Type: application/json'];

        if ($debug) {
            // Mask the API key in the URL for debug output
            $debugLog[] = ['step' => 'gemini_request', 'url' => self::maskUrl($url), 'model' => $model];
        }

        $apiResult = self::executeCurlRequest($url, $payload, $headers, (int)($settings['timeout'] ?: 8));

        if ($debug) {
            $debugLog[] = ['step' => 'gemini_response', 'http_code' => $apiResult['http_code'], 'curl_error' => $apiResult['curl_error'] ?: null, 'response_preview' => self::truncate($apiResult['response'], 300)];
        }

        if ($apiResult['curl_error']) {
            return self::buildErrorResult('Gemini', 'curl_error', $apiResult['curl_error'], $text);
        }

        if ($apiResult['http_code'] >= 400) {
            $decoded = json_decode($apiResult['response'], true);
            $msg = $decoded['error']['message'] ?? "HTTP {$apiResult['http_code']}";
            return self::buildErrorResult('Gemini', 'api_error', $msg, $text);
        }

        // Parse Gemini response format
        $decoded = json_decode($apiResult['response'], true);
        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($debug) {
            $debugLog[] = ['step' => 'gemini_parse', 'has_candidates' => isset($decoded['candidates']), 'has_content' => !empty($content)];
        }

        return self::parseAndValidateAIOutput($content, 'Gemini', $text, $debug, $debugLog);
    }

    // ─────────────────────────────────────────────────────────────
    //  Adapter: DeepSeek (OpenAI-compatible format)
    // ─────────────────────────────────────────────────────────────

    private static function interpretWithDeepSeek(string $text, array $settings, bool $debug, array &$debugLog): array {
        $url = rtrim($settings['api_endpoint'] ?: 'https://api.deepseek.com/chat/completions', '/');
        $model = $settings['model_name'] ?: 'deepseek-chat';

        $payload = [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $text]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.1,
            'max_tokens'      => 350
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['api_key']
        ];

        if ($debug) {
            $debugLog[] = ['step' => 'deepseek_request', 'url' => self::maskUrl($url), 'model' => $model];
        }

        $apiResult = self::executeCurlRequest($url, $payload, $headers, (int)($settings['timeout'] ?: 8));

        if ($debug) {
            $debugLog[] = ['step' => 'deepseek_response', 'http_code' => $apiResult['http_code'], 'curl_error' => $apiResult['curl_error'] ?: null, 'response_preview' => self::truncate($apiResult['response'], 300)];
        }

        if ($apiResult['curl_error']) {
            return self::buildErrorResult('DeepSeek', 'curl_error', $apiResult['curl_error'], $text);
        }

        if ($apiResult['http_code'] >= 400) {
            $decoded = json_decode($apiResult['response'], true);
            $msg = $decoded['error']['message'] ?? "HTTP {$apiResult['http_code']}";
            return self::buildErrorResult('DeepSeek', 'api_error', $msg, $text);
        }

        // Parse DeepSeek response (OpenAI-compatible format)
        $decoded = json_decode($apiResult['response'], true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if ($debug) {
            $debugLog[] = ['step' => 'deepseek_parse', 'has_choices' => isset($decoded['choices']), 'has_content' => !empty($content)];
        }

        return self::parseAndValidateAIOutput($content, 'DeepSeek', $text, $debug, $debugLog);
    }

    // ─────────────────────────────────────────────────────────────
    //  Shared Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Execute a cURL POST request and return structured result
     */
    private static function executeCurlRequest(string $url, array $payload, array $headers, int $timeout): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => false // for seamless local XAMPP testing
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'response'   => $response ?: '',
            'http_code'  => $httpCode,
            'curl_error' => $curlError
        ];
    }

    /**
     * Parse raw AI text content, validate structure, and return standardized result.
     * Falls back to Mock interpreter ONLY if JSON is completely unparseable,
     * but marks the fallback explicitly (never silently pretends success).
     */
    private static function parseAndValidateAIOutput(?string $content, string $providerName, string $originalText, bool $debug, array &$debugLog): array {
        if (empty($content)) {
            if ($debug) {
                $debugLog[] = ['step' => 'parse_fail', 'reason' => 'empty_content'];
            }
            return self::buildErrorResult($providerName, 'empty_response', 'API mengembalikan respons kosong.', $originalText);
        }

        // Try to extract JSON from possible markdown code fences
        $jsonContent = $content;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
            $jsonContent = trim($m[1]);
        }

        $parsed = json_decode($jsonContent, true);

        if (!is_array($parsed)) {
            if ($debug) {
                $debugLog[] = ['step' => 'parse_fail', 'reason' => 'invalid_json', 'json_error' => json_last_error_msg(), 'raw_preview' => self::truncate($content, 200)];
            }
            return self::buildErrorResult($providerName, 'json_parse_error', 'Respons AI bukan JSON valid: ' . json_last_error_msg(), $originalText);
        }

        // Validate required fields
        $missingFields = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $parsed)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            if ($debug) {
                $debugLog[] = ['step' => 'validation_fail', 'missing_fields' => $missingFields, 'received_keys' => array_keys($parsed)];
            }
            return self::buildErrorResult($providerName, 'missing_fields', 'Field wajib tidak ditemukan: ' . implode(', ', $missingFields), $originalText);
        }

        // Normalize types
        $parsed['connected'] = (bool)($parsed['connected'] ?? false);
        $parsed['internet_access'] = (bool)($parsed['internet_access'] ?? false);
        $parsed['other_devices_working'] = isset($parsed['other_devices_working']) ? (bool)$parsed['other_devices_working'] : null;
        $parsed['extracted_keywords'] = (array)($parsed['extracted_keywords'] ?? []);
        $parsed['possible_symptoms'] = (array)($parsed['possible_symptoms'] ?? []);
        $parsed['summary'] = (string)($parsed['summary'] ?? '');

        if ($debug) {
            $debugLog[] = ['step' => 'parse_success', 'fields' => array_keys($parsed)];
        }

        return [
            'status' => 'success',
            'source' => "AI-Assisted ({$providerName})",
            'data'   => $parsed
        ];
    }

    /**
     * Build a structured error result (NEVER silently returns mock data)
     */
    private static function buildErrorResult(string $provider, string $errorCode, string $errorMessage, string $originalText): array {
        return [
            'status'        => 'error',
            'source'        => $provider,
            'error_code'    => $errorCode,
            'error_message' => $errorMessage,
            'data'          => null
        ];
    }

    /**
     * Extract significant keywords from text
     */
    private static function extractKeywords(string $text): array {
        $words = preg_split('/[\s,\.;:\-\?!]+/', $text);
        $stopWords = ['dan', 'atau', 'di', 'ke', 'dari', 'yang', 'ini', 'itu', 'pada', 'saya', 'laptop', 'komputer', 'untuk', 'dengan', 'ada', 'bisa', 'tetapi', 'tapi'];
        $filtered = array_filter($words, function($w) use ($stopWords) {
            return strlen($w) > 2 && !in_array($w, $stopWords);
        });
        return array_values(array_unique(array_slice($filtered, 0, 8)));
    }

    /**
     * Mask sensitive parts of a URL (API keys)
     */
    private static function maskUrl(string $url): string {
        // Mask key= parameter
        $masked = preg_replace('/([?&]key=)([^&]+)/', '$1***MASKED***', $url);
        return $masked;
    }

    /**
     * Truncate string for safe debug output
     */
    private static function truncate(string $text, int $maxLen): string {
        if (strlen($text) <= $maxLen) return $text;
        return substr($text, 0, $maxLen) . '... [truncated]';
    }
}

/**
 * Procedural helper to interpret symptoms with AI
 */
function interpret_symptoms_with_ai(string $text, bool $debug = false): array {
    return AISymptomInterpreter::interpret($text, $debug);
}
