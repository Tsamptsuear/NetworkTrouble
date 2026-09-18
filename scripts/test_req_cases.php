<?php
/**
 * NetworkTrouble - Test 5 kasus requirement user
 * CLI verification: setiap input menghasilkan hasil berbeda.
 */
$_SERVER['SCRIPT_NAME'] = '/networktrouble/scripts/test_req_cases.php';
$_SERVER['HTTP_HOST']   = 'localhost';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';

$cases = [
    'TEST 1' => 'WiFi terhubung tetapi internet tidak bisa digunakan.',
    'TEST 2' => 'Internet saya sangat lambat dan ping sangat tinggi.',
    'TEST 3' => 'Kabel LAN tidak terdeteksi oleh komputer.',
    'TEST 4' => 'Website tertentu tidak bisa dibuka tetapi website lain bisa.',
    'TEST 5' => 'Komputer saya tidak memperoleh IP dari DHCP.',
];

echo str_repeat('=', 68) . PHP_EOL;
echo ' Requirement Test: 5 Input Berbeda → 5 Hasil Berbeda' . PHP_EOL;
echo str_repeat('=', 68) . PHP_EOL . PHP_EOL;

$profiles = [];

foreach ($cases as $label => $input) {
    $r = AISymptomInterpreter::interpret($input);
    $d = $r['data'] ?? [];

    $profile = json_encode([
        'conn'     => $d['connection_type'] ?? '-',
        'linked'   => $d['connected'] ?? '-',
        'internet' => $d['internet_access'] ?? '-',
        'symptoms' => $d['possible_symptoms'] ?? [],
    ]);

    $profiles[$label] = $profile;

    echo "── {$label}" . PHP_EOL;
    echo "   Input    : \"{$input}\"" . PHP_EOL;
    echo "   ConnType : " . ($d['connection_type'] ?? '?') . PHP_EOL;
    echo "   Internet : " . (($d['internet_access'] ?? true) ? 'YES' : 'NO') . PHP_EOL;
    echo "   Symptoms : " . implode(', ', $d['possible_symptoms'] ?? ['(none)']) . PHP_EOL;
    echo "   Summary  : " . ($d['summary'] ?? '') . PHP_EOL . PHP_EOL;
}

// Check uniqueness
$unique = count(array_unique(array_values($profiles)));
echo str_repeat('-', 68) . PHP_EOL;
echo "Unique profiles: {$unique}/" . count($cases) . PHP_EOL;
if ($unique === count($cases)) {
    echo "✓ SEMUA TEST CASE MENGHASILKAN PROFIL BERBEDA — DYNAMIC INPUT = DYNAMIC RESULT" . PHP_EOL;
} else {
    echo "✗ ADA DUPLIKASI — perlu pengecekan lebih lanjut" . PHP_EOL;
}
echo str_repeat('=', 68) . PHP_EOL;
