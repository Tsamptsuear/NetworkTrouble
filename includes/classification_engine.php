<?php
/**
 * NetworkTrouble - Core Rule-Based Classification Engine
 * Evaluates connection type, symptom weights, compound rule conditions, and keyword fallbacks.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class NetworkClassificationEngine {

    /**
     * Main Classification Method
     * Accepts:
     * - connection_type: string ('wifi', 'lan', 'hotspot', 'fiber', 'unknown')
     * - symptoms: array of symptom IDs (integers)
     * - adaptive_answers: array of key => value (e.g. ['ping_gateway' => 'no', 'ping_ip_ok' => 'yes'])
     * - custom_text: string (optional free text from "Lainnya" or AI input)
     * - source: 'Rule-Based' | 'AI-Assisted' | 'Hybrid'
     */
    public static function diagnose(array $inputData): array {
        $db = get_db();
        
        $connectionType  = $inputData['connection_type'] ?? 'unknown';
        $selectedSymptoms = array_map('intval', $inputData['symptoms'] ?? []);
        $adaptiveAnswers  = $inputData['adaptive_answers'] ?? [];
        $customText       = trim($inputData['custom_text'] ?? '');
        $classificationSource = $inputData['classification_source'] ?? 'Rule-Based';

        // Load all active categories
        $categories = self::getCategories();
        $categoryScores = [];
        $matchedSymptomsList = [];
        $triggeredRulesList = [];

        foreach ($categories as $catId => $cat) {
            $categoryScores[$catId] = 0;
        }

        // 1. Connection Type Baseline Bias
        if ($connectionType === 'wifi') {
            $categoryScores[6] = ($categoryScores[6] ?? 0) + 15; // Wireless Issue
        } elseif ($connectionType === 'lan') {
            $categoryScores[1] = ($categoryScores[1] ?? 0) + 15; // Physical Layer
            $categoryScores[2] = ($categoryScores[2] ?? 0) + 10; // Data Link
        }

        // 2. Score Selected Symptoms
        if (!empty($selectedSymptoms) && $db) {
            $placeholders = implode(',', array_fill(0, count($selectedSymptoms), '?'));
            try {
                $stmt = $db->prepare("SELECT id, category_id, symptom_name, weight FROM network_symptoms WHERE id IN ($placeholders) AND status = 'active'");
                $stmt->execute($selectedSymptoms);
                $symptomRows = $stmt->fetchAll();

                foreach ($symptomRows as $s) {
                    $catId = (int)$s['category_id'];
                    $weight = (int)$s['weight'];
                    $categoryScores[$catId] = ($categoryScores[$catId] ?? 0) + $weight;
                    $matchedSymptomsList[] = $s['symptom_name'];
                }
            } catch (Exception $e) {}
        }

        // 3. Score Adaptive Questions (Domain logic)
        // Adaptive Q: ping_ip_ok (Bisa ping 8.8.8.8 tapi website ga bisa)
        if (!empty($adaptiveAnswers['ping_ip_ok']) && $adaptiveAnswers['ping_ip_ok'] === 'yes') {
            $categoryScores[5] = ($categoryScores[5] ?? 0) + 35; // Application/DNS Layer
            $matchedSymptomsList[] = 'Berhasil ping IP publik (8.8.8.8) tetapi gagal membuka nama domain';
        }

        // Adaptive Q: ping_gateway (Tidak bisa ping gateway lokal)
        if (!empty($adaptiveAnswers['ping_gateway']) && $adaptiveAnswers['ping_gateway'] === 'no') {
            $categoryScores[3] = ($categoryScores[3] ?? 0) + 30; // Network Layer / Routing
            $matchedSymptomsList[] = 'Gagal melakukan ping ke Default Gateway lokal';
        }

        // Adaptive Q: other_devices (Perangkat lain normal, hanya ini yang bermasalah)
        if (!empty($adaptiveAnswers['other_devices']) && $adaptiveAnswers['other_devices'] === 'yes') {
            $categoryScores[7] = ($categoryScores[7] ?? 0) + 15;
            $matchedSymptomsList[] = 'Perangkat lain normal (masalah terlokalisir pada host ini)';
        } elseif (!empty($adaptiveAnswers['other_devices']) && $adaptiveAnswers['other_devices'] === 'no') {
            $categoryScores[1] = ($categoryScores[1] ?? 0) + 20; // Physical / Infrastructure
            $categoryScores[3] = ($categoryScores[3] ?? 0) + 20; // Gateway / DHCP pool
            $matchedSymptomsList[] = 'Semua perangkat mengalami gangguan yang sama (infrastruktur)';
        }

        // Adaptive Q: lan_led (Lampu port LAN mati)
        if (!empty($adaptiveAnswers['lan_led']) && $adaptiveAnswers['lan_led'] === 'off') {
            $categoryScores[1] = ($categoryScores[1] ?? 0) + 40; // Physical Layer
            $matchedSymptomsList[] = 'Lampu LED pada port LAN mati / tidak ada sinyal link';
        }

        // Adaptive Q: wifi_status (SSID hilang / Sinyal lemah)
        if (!empty($adaptiveAnswers['wifi_status']) && $adaptiveAnswers['wifi_status'] === 'missing') {
            $categoryScores[6] = ($categoryScores[6] ?? 0) + 40; // Wireless Issue
            $matchedSymptomsList[] = 'SSID WiFi tidak terdeteksi atau sinyal sering putus secara acak';
        } elseif (!empty($adaptiveAnswers['wifi_status']) && $adaptiveAnswers['wifi_status'] === 'connected') {
            $categoryScores[3] = ($categoryScores[3] ?? 0) + 15; // Network/DHCP
            $categoryScores[5] = ($categoryScores[5] ?? 0) + 15; // DNS/App
            $matchedSymptomsList[] = 'Sinyal WiFi terhubung tetapi tidak memiliki akses keluar (No Internet)';
        }

        // 4. Compound Classification Rules
        $rules = self::getClassificationRules();
        foreach ($rules as $rule) {
            $conditions = json_decode($rule['conditions_json'], true) ?: [];
            $conditionMet = true;

            // Check connection_type condition
            if (!empty($conditions['connection_type'])) {
                if (!in_array($connectionType, $conditions['connection_type'])) {
                    $conditionMet = false;
                }
            }

            // Check required symptoms condition
            if ($conditionMet && !empty($conditions['symptoms'])) {
                $intersect = array_intersect($conditions['symptoms'], $selectedSymptoms);
                if (count($intersect) < count($conditions['symptoms'])) {
                    $conditionMet = false;
                }
            }

            if ($conditionMet) {
                $catId = (int)$rule['category_id'];
                $ruleScore = (int)$rule['score'];
                $categoryScores[$catId] = ($categoryScores[$catId] ?? 0) + $ruleScore;
                $triggeredRulesList[] = $rule['rule_name'];
            }
        }

        // 5. Keyword Matching Fallback (if user input custom free text)
        $matchedKeywords = [];
        if (!empty($customText)) {
            $kwMatches = self::matchKeywords($customText);
            foreach ($kwMatches as $kw) {
                $catId = (int)$kw['category_id'];
                $categoryScores[$catId] = ($categoryScores[$catId] ?? 0) + (int)$kw['weight'];
                $matchedKeywords[] = $kw['keyword'];
            }
            if (!empty($matchedKeywords)) {
                $matchedSymptomsList[] = 'Kata kunci teridentifikasi: ' . implode(', ', array_unique($matchedKeywords));
                if ($classificationSource === 'Rule-Based') {
                    $classificationSource = 'Hybrid';
                }
            }
        }

        // 6. Determine Top Category
        arsort($categoryScores);
        $topCategoryId = key($categoryScores);
        $topScore = current($categoryScores);

        // Calculate Confidence Score (Normalized to 0 - 100%)
        // High confidence threshold baseline is 60+ points
        $confidenceScore = 0;
        if ($topScore > 0) {
            if ($topScore >= 80) {
                $confidenceScore = min(98, 80 + (int)(($topScore - 80) * 0.4));
            } elseif ($topScore >= 50) {
                $confidenceScore = 60 + (int)(($topScore - 50) * (19 / 30));
            } elseif ($topScore >= 25) {
                $confidenceScore = 40 + (int)(($topScore - 25) * (19 / 25));
            } else {
                $confidenceScore = max(15, (int)($topScore * 1.5));
            }
        }

        // If confidence is lower than 40% or no symptoms matched, mark as Unknown Network Issue
        if ($confidenceScore < 40 || empty($topCategoryId) || $topScore <= 0) {
            $topCategoryId = 9; // Unknown Network Issue
            $confidenceScore = max(20, $confidenceScore);
        }

        $winningCategory = $categories[$topCategoryId] ?? $categories[9];

        // 7. Fetch Steps and Commands for Winning Category
        $troubleshootingSteps = self::getTroubleshootingSteps($topCategoryId);
        $commands = self::getCommandsForCategory($topCategoryId);
        $possibleCauses = self::generatePossibleCauses($topCategoryId, $matchedSymptomsList);

        return [
            'category_id'          => $topCategoryId,
            'category_name'        => $winningCategory['name'],
            'category_slug'        => $winningCategory['slug'],
            'osi_layer'            => $winningCategory['osi_layer'],
            'severity'             => $winningCategory['severity_default'],
            'icon'                 => $winningCategory['icon'],
            'description'          => $winningCategory['description'],
            'raw_score'            => $topScore,
            'confidence_score'     => $confidenceScore,
            'classification_source'=> $classificationSource,
            'matched_symptoms'     => $matchedSymptomsList,
            'triggered_rules'      => $triggeredRulesList,
            'matched_keywords'     => $matchedKeywords,
            'possible_causes'      => $possibleCauses,
            'troubleshooting_steps'=> $troubleshootingSteps,
            'commands'             => $commands,
            'all_scores'           => $categoryScores
        ];
    }

    /**
     * Fallback Keyword Matcher against `symptom_keywords`
     */
    public static function matchKeywords(string $text): array {
        $db = get_db();
        $matches = [];
        $lower = strtolower($text);

        if (!$db) {
            return $matches;
        }

        try {
            $stmt = $db->query("SELECT keyword, category_id, weight FROM symptom_keywords");
            $keywords = $stmt->fetchAll();

            foreach ($keywords as $kw) {
                $term = strtolower(trim($kw['keyword']));
                if ($term !== '' && strpos($lower, $term) !== false) {
                    $matches[] = $kw;
                }
            }
        } catch (Exception $e) {}

        return $matches;
    }

    /**
     * Cache & Retrieve Network Categories
     */
    public static function getCategories(): array {
        static $cached = null;
        if ($cached !== null) return $cached;

        $db = get_db();
        $categories = [];

        if ($db) {
            try {
                $stmt = $db->query("SELECT * FROM network_categories ORDER BY id ASC");
                while ($row = $stmt->fetch()) {
                    $categories[(int)$row['id']] = $row;
                }
                $cached = $categories;
                return $categories;
            } catch (Exception $e) {}
        }

        // Fallback static array if DB not loaded yet
        $fallback = [
            1 => ['id' => 1, 'name' => 'Physical Layer Issue', 'slug' => 'physical-layer-issue', 'osi_layer' => 'Layer 1 (Physical)', 'severity_default' => 'High', 'icon' => 'bi-ethernet', 'description' => 'Masalah pada media kabel fisik, konektor RJ45, atau port switch.'],
            2 => ['id' => 2, 'name' => 'Data Link Layer Issue', 'slug' => 'data-link-layer-issue', 'osi_layer' => 'Layer 2 (Data Link)', 'severity_default' => 'Medium', 'icon' => 'bi-diagram-2', 'description' => 'Masalah switch, framing, MAC address, duplex mismatch, atau VLAN.'],
            3 => ['id' => 3, 'name' => 'Network Layer Issue', 'slug' => 'network-layer-issue', 'osi_layer' => 'Layer 3 (Network)', 'severity_default' => 'High', 'icon' => 'bi-diagram-3', 'description' => 'Masalah pengalamatan IP, DHCP failure (APIPA 169.254), atau rute default gateway.'],
            4 => ['id' => 4, 'name' => 'Transport Layer Issue', 'slug' => 'transport-layer-issue', 'osi_layer' => 'Layer 4 (Transport)', 'severity_default' => 'Medium', 'icon' => 'bi-shield-check', 'description' => 'Masalah TCP/UDP handshake, port blocked, firewall timeout, atau socket reset.'],
            5 => ['id' => 5, 'name' => 'Application Layer Issue', 'slug' => 'application-layer-issue', 'osi_layer' => 'Layer 7 (Application)', 'severity_default' => 'Medium', 'icon' => 'bi-globe2', 'description' => 'Masalah DNS resolution gagal, HTTP/HTTPS error, web server down, atau proxy.'],
            6 => ['id' => 6, 'name' => 'Wireless Issue', 'slug' => 'wireless-issue', 'osi_layer' => 'Layer 1-2 (Wireless)', 'severity_default' => 'Medium', 'icon' => 'bi-wifi', 'description' => 'Masalah sinyal WiFi lemah, interferensi radio, SSID hilang, atau roaming failure.'],
            7 => ['id' => 7, 'name' => 'Performance Issue', 'slug' => 'performance-issue', 'osi_layer' => 'Cross-Layer (QoS)', 'severity_default' => 'Medium', 'icon' => 'bi-speedometer2', 'description' => 'Latensi tinggi, packet loss, bandwidth throttling, atau network congestion.'],
            8 => ['id' => 8, 'name' => 'Security Issue', 'slug' => 'security-issue', 'osi_layer' => 'Cross-Layer (Security)', 'severity_default' => 'Critical', 'icon' => 'bi-shield-exclamation', 'description' => 'Indikasi serangan ARP spoofing, rogue DHCP, DNS redirection, atau firewall blocking.'],
            9 => ['id' => 9, 'name' => 'Unknown Network Issue', 'slug' => 'unknown-network-issue', 'osi_layer' => 'Unknown / Multi-Layer', 'severity_default' => 'Low', 'icon' => 'bi-question-circle', 'description' => 'Gejala belum cukup spesifik. Memerlukan pemeriksaan fisik dasar dan isolasi menyeluruh.']
        ];
        $cached = $fallback;
        return $fallback;
    }

    private static function getClassificationRules(): array {
        $db = get_db();
        if (!$db) return [];
        try {
            return $db->query("SELECT * FROM classification_rules WHERE status = 'active' ORDER BY priority ASC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getTroubleshootingSteps(int $categoryId): array {
        $db = get_db();
        if (!$db) return [];
        try {
            $stmt = $db->prepare("SELECT * FROM troubleshooting_steps WHERE category_id = ? ORDER BY step_number ASC");
            $stmt->execute([$categoryId]);
            $steps = $stmt->fetchAll();
            if (!empty($steps)) return $steps;

            // Fallback to unknown steps
            $fallbackStmt = $db->prepare("SELECT * FROM troubleshooting_steps WHERE category_id = 9 ORDER BY step_number ASC");
            $fallbackStmt->execute();
            return $fallbackStmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getCommandsForCategory(int $categoryId): array {
        $db = get_db();
        if (!$db) return [];
        try {
            $stmt = $db->prepare("SELECT * FROM network_commands WHERE category_id = ? OR category_id IS NULL ORDER BY platform ASC, id ASC");
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Generate ranked possible causes with probability percentages
     */
    private static function generatePossibleCauses(int $categoryId, array $matchedSymptoms): array {
        $catalog = [
            1 => [ // Physical
                ['cause' => 'Kabel LAN terlepas, longgar, atau pin RJ45 tertekuk', 'prob' => 45],
                ['cause' => 'Port LAN pada switch atau router mengalami kerusakan fisik', 'prob' => 30],
                ['cause' => 'Network Interface Card (NIC) adapter mati atau driver korup', 'prob' => 25],
            ],
            2 => [ // Data Link
                ['cause' => 'Speed & Duplex auto-negotiation mismatch antara PC dan switch', 'prob' => 40],
                ['cause' => 'Konflik MAC Address atau kesalahan penugasan VLAN ID pada switch', 'prob' => 35],
                ['cause' => 'Looping pada switch unmanaged (Broadcast Storm)', 'prob' => 25],
            ],
            3 => [ // Network
                ['cause' => 'DHCP Server pool habis atau gagal memberikan IP (APIPA 169.254.x.x)', 'prob' => 45],
                ['cause' => 'Konflik IP Address statis dengan perangkat lain di satu subnet', 'prob' => 30],
                ['cause' => 'Kesalahan konfigurasi Default Gateway atau routing lokal terputus', 'prob' => 25],
            ],
            4 => [ // Transport
                ['cause' => 'Port TCP/UDP diblokir oleh Windows Firewall atau router ACL', 'prob' => 50],
                ['cause' => 'Koneksi TCP timed out akibat beban server tujuan tinggi', 'prob' => 30],
                ['cause' => 'Socket connection pool exhausted pada sistem operasi', 'prob' => 20],
            ],
            5 => [ // Application
                ['cause' => 'DNS Server resolver gagal memetakan nama domain ke alamat IP', 'prob' => 55],
                ['cause' => 'Cache DNS lokal pada komputer mengalami corrupt / kedaluwarsa', 'prob' => 25],
                ['cause' => 'Konfigurasi Proxy/VPN lokal membelokkan trafik web', 'prob' => 20],
            ],
            6 => [ // Wireless
                ['cause' => 'Sinyal WiFi melemah terhalang struktur fisik / jarak terlalu jauh', 'prob' => 40],
                ['cause' => 'Interferensi kanal radio frekuensi 2.4 GHz dari access point lain', 'prob' => 35],
                ['cause' => 'Fitur power saving adapter laptop memutus koneksi WiFi secara berkala', 'prob' => 25],
            ],
            7 => [ // Performance
                ['cause' => 'Congestion trafik lokal akibat download/update background pada perangkat lain', 'prob' => 45],
                ['cause' => 'Packet loss tinggi dan jitter dari sambungan upstream ISP', 'prob' => 35],
                ['cause' => 'Kabel LAN atau sinyal nirkabel berkualitas buruk menyebabkan retransmisi TCP', 'prob' => 20],
            ],
            8 => [ // Security
                ['cause' => 'ARP Spoofing / Man-in-the-Middle attack pada jaringan lokal', 'prob' => 45],
                ['cause' => 'Rogue DHCP Server membagikan IP dan gateway palsu', 'prob' => 30],
                ['cause' => 'Malware atau pengalihan file hosts sistem operasi', 'prob' => 25],
            ],
            9 => [ // Unknown
                ['cause' => 'Gejala bersifat majemuk / belum cukup spesifik untuk diidentifikasi', 'prob' => 50],
                ['cause' => 'Konektivitas ISP upstream mengalami pemadaman sementara', 'prob' => 30],
                ['cause' => 'Perangkat jaringan memerlukan cold restart (power cycle)', 'prob' => 20],
            ],
        ];

        return $catalog[$categoryId] ?? $catalog[9];
    }
}
