<?php
/**
 * NetworkTrouble - RESTful API: Save Diagnosis Resolution
 * Updates resolution feedback (resolved / unresolved) and registers unresolved cases.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$resultId = (int)($data['result_id'] ?? 0);
$status   = sanitize($data['status'] ?? 'pending'); // resolved, unresolved, unsure

if (!in_array($status, ['resolved', 'unresolved', 'unsure'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status tidak valid.']);
    exit;
}

$db = get_db();
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit;
}

try {
    // 1. Update diagnosis_results table
    $stmt = $db->prepare("UPDATE diagnosis_results SET status = ?, resolved_at = IF(? = 'resolved', NOW(), resolved_at) WHERE id = ?");
    $stmt->execute([$status, $status, $resultId]);

    // 2. If unresolved, check if session has unresolved_cases entry or create one
    if ($status === 'unresolved' && $resultId > 0) {
        $checkStmt = $db->prepare("SELECT r.session_id, s.input_data_json, c.name as cat_name FROM diagnosis_results r JOIN diagnosis_sessions s ON r.session_id = s.id JOIN network_categories c ON r.category_id = c.id WHERE r.id = ?");
        $checkStmt->execute([$resultId]);
        $row = $checkStmt->fetch();

        if ($row) {
            $sessionId = (int)$row['session_id'];
            $input = json_decode($row['input_data_json'], true) ?: [];
            $userDesc = !empty($input['custom_text']) ? $input['custom_text'] : 'Keluhan dari wizard (Status Belum Terselesaikan)';

            // Check if already exists for this session to prevent duplicate entries
            $existingCheck = $db->prepare("SELECT id FROM unresolved_cases WHERE session_id = ? LIMIT 1");
            $existingCheck->execute([$sessionId]);
            $existingCase = $existingCheck->fetch();

            if (!$existingCase) {
                $ins = $db->prepare("INSERT INTO unresolved_cases (session_id, user_description, predicted_category, case_status, created_at) VALUES (?, ?, ?, 'open', NOW())");
                $ins->execute([$sessionId, $userDesc, $row['cat_name']]);
            } else {
                $upd = $db->prepare("UPDATE unresolved_cases SET case_status = 'open', updated_at = NOW() WHERE id = ?");
                $upd->execute([(int)$existingCase['id']]);
            }
        }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Status berhasil diperbarui.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menyimpan status: ' . $e->getMessage()
    ]);
}
