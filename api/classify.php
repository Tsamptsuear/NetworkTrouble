<?php
/**
 * NetworkTrouble - RESTful API: Classify Symptoms
 * Accepts JSON or POST form data and returns structured diagnosis JSON.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/classification_engine.php';

// Accept JSON payload or standard POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$connType = sanitize($data['connection_type'] ?? 'unknown');
$symptoms = array_map('intval', (array)($data['symptoms'] ?? []));
$adaptive = (array)($data['adaptive_answers'] ?? []);
$customText = sanitize($data['custom_text'] ?? '');
$source = sanitize($data['classification_source'] ?? 'Rule-Based');

$result = NetworkClassificationEngine::diagnose([
    'connection_type'       => $connType,
    'symptoms'              => $symptoms,
    'adaptive_answers'      => $adaptive,
    'custom_text'           => $customText,
    'classification_source' => $source
]);

echo json_encode([
    'status' => 'success',
    'data'   => $result
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
