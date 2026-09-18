<?php
/**
 * NetworkTrouble - CLI Functional Test: AI Mock Interpreter
 * Run: C:\xampp\php\php.exe d:\BELAJAR WEBSITE\networktrouble\scripts\test_mock_nlp.php
 *
 * Verifies that different inputs produce semantically different outputs.
 */

// Bootstrap without HTTP context
$_SERVER['SCRIPT_NAME'] = '/networktrouble/scripts/test_mock_nlp.php';
$_SERVER['HTTP_HOST']   = 'localhost';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';

// Force mock mode (no DB dependency needed for mock)
// We call interpret() directly — it will auto-use mock since no API key

$testCases = [
    'WiFi no internet (tanda seru)'     => 'wifi terhubung tapi tanda seru kuning tidak bisa internet sama sekali',
    'Performa / ping tinggi'            => 'internet saya sangat lambat dan ping sangat tinggi setiap malam',
    'Kabel LAN tidak terdeteksi'        => 'kabel LAN tidak terdeteksi oleh komputer saya',
    'Website tertentu tidak bisa buka'  => 'website tertentu tidak bisa dibuka tetapi website lain bisa',
    'DHCP tidak dapat IP'               => 'komputer saya tidak memperoleh IP dari DHCP server',
    'SSID WiFi hilang'                  => 'sinyal wifi tiba-tiba hilang, SSID tidak muncul di daftar jaringan',
    'DNS error (ping ok tapi web gagal)'=> 'bisa ping 8.8.8.8 tapi tidak bisa buka website apapun, DNS error',
    'IP conflict'                       => 'ada konflik IP address dengan perangkat lain di jaringan',
    'Semua perangkat down'              => 'semua perangkat di kantor tidak bisa internet, router menyala tapi jaringan mati semua',
    'Teks tidak dikenal'                => 'saya tidak tahu apa masalah jaringan saya',
];

$passCount = 0;
$total     = count($testCases);

echo str_repeat('=', 72) . PHP_EOL;
echo ' AI Mock NLP Interpreter — Functional Test Suite' . PHP_EOL;
echo str_repeat('=', 72) . PHP_EOL . PHP_EOL;

$allSymptoms = [];

foreach ($testCases as $label => $input) {
    $result = AISymptomInterpreter::interpret($input, false);

    echo "▶ {$label}" . PHP_EOL;
    echo "  Input: \"{$input}\"" . PHP_EOL;

    if ($result['status'] === 'success') {
        $d = $result['data'];
        $symptoms = implode(', ', $d['possible_symptoms'] ?: ['(none)']);
        $hints    = implode(', ', $d['adaptive_hints'] ?? ['(none)']);

        echo "  Status    : ✓ SUCCESS" . PHP_EOL;
        echo "  Source    : {$result['source']}" . PHP_EOL;
        echo "  Conn Type : {$d['connection_type']}" . PHP_EOL;
        echo "  Connected : " . ($d['connected'] ? 'true' : 'false') . PHP_EOL;
        echo "  Internet  : " . ($d['internet_access'] ? 'true' : 'false') . PHP_EOL;
        echo "  Symptoms  : {$symptoms}" . PHP_EOL;
        echo "  Hints     : {$hints}" . PHP_EOL;
        echo "  Summary   : {$d['summary']}" . PHP_EOL;

        $allSymptoms[] = implode('|', $d['possible_symptoms']);
        $passCount++;
    } else {
        echo "  Status    : ✗ ERROR — {$result['error_message']}" . PHP_EOL;
        $allSymptoms[] = 'ERROR';
    }

    echo PHP_EOL;
}

// ── Diversity check: are outputs sufficiently different? ──────────
$uniqueOutputs = count(array_unique($allSymptoms));
$diversityOk   = $uniqueOutputs >= ($total * 0.6); // At least 60% unique symptom profiles

echo str_repeat('-', 72) . PHP_EOL;
echo "RESULTS: {$passCount}/{$total} interpreter calls succeeded" . PHP_EOL;
echo "DIVERSITY: {$uniqueOutputs}/{$total} unique symptom profiles — " . ($diversityOk ? '✓ PASS' : '✗ FAIL (too many identical outputs)') . PHP_EOL;
echo str_repeat('=', 72) . PHP_EOL;
exit($diversityOk && $passCount === $total ? 0 : 1);
