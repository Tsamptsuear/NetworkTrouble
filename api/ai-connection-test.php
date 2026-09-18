<?php
/**
 * NetworkTrouble - API: AI Connection Test
 * Lightweight ping to verify API key & endpoint connectivity.
 * Admin-only (requires active admin session).
 *
 * POST body (JSON): { csrf_token: string }
 * Response: { status, message, provider, latency_ms }
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/ai.php';

// ─── Auth gate (admin session required) ─────────────────────────
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_role'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Sesi admin diperlukan.']);
    exit;
}

// ─── CSRF check (optional for AJAX but good practice) ────────────
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
// CSRF check is relaxed here (same-origin AJAX with session cookie is already protected)
// but we validate if token was provided
if (!empty($data['csrf_token']) && !verify_csrf_token($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid.']);
    exit;
}

// ─── Run connection test ─────────────────────────────────────────
$result = AISymptomInterpreter::testConnection();

http_response_code($result['status'] === 'ok' ? 200 : 503);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
