-- ==========================================================
-- Database: networktrouble_db
-- Application: NetworkTrouble - Network Diagnostic & Classification System
-- Target: MySQL 5.7+ / 8.0+ / MariaDB 10.3+ (Compatible with XAMPP)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `networktrouble_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `networktrouble_db`;

-- --------------------------------------------------------
-- 1. Table `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'content_admin', 'technical_admin') NOT NULL DEFAULT 'technical_admin',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table `site_settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(60) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `setting_type` VARCHAR(30) NOT NULL DEFAULT 'text',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table `media_assets`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `media_assets`;
CREATE TABLE `media_assets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `alt_text` VARCHAR(255) NULL,
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table `page_contents`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `page_contents`;
CREATE TABLE `page_contents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `page_slug` VARCHAR(50) NOT NULL,
  `section_key` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NULL,
  `subtitle` VARCHAR(255) NULL,
  `content` TEXT NULL,
  `image_id` INT UNSIGNED NULL,
  `icon` VARCHAR(60) NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_page_section` (`page_slug`, `section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4b. Table `slider_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `slider_items`;
CREATE TABLE `slider_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `media_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `button_text` VARCHAR(100) NULL,
  `button_url` VARCHAR(255) NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_media_id` (`media_id`),
  CONSTRAINT `fk_slider_media_main` FOREIGN KEY (`media_id`) REFERENCES `media_assets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table `contact_settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `contact_settings`;
CREATE TABLE `contact_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `contact_name` VARCHAR(100) NOT NULL DEFAULT 'IT Support & Network NOC',
  `phone` VARCHAR(30) NOT NULL DEFAULT '+62 812-3456-7890',
  `email` VARCHAR(100) NOT NULL DEFAULT 'support@networktrouble.local',
  `address` TEXT NULL,
  `working_hours` VARCHAR(100) NOT NULL DEFAULT 'Senin - Jumat: 08:00 - 17:00 WIB',
  `whatsapp` VARCHAR(30) NOT NULL DEFAULT '6281234567890',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table `network_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `network_categories`;
CREATE TABLE `network_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NOT NULL,
  `osi_layer` VARCHAR(50) NOT NULL,
  `severity_default` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
  `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-hdd-network',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Table `network_symptoms`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `network_symptoms`;
CREATE TABLE `network_symptoms` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `symptom_name` VARCHAR(150) NOT NULL,
  `symptom_description` TEXT NULL,
  `input_type` ENUM('checkbox', 'radio', 'select', 'boolean') NOT NULL DEFAULT 'checkbox',
  `options_json` TEXT NULL,
  `weight` INT NOT NULL DEFAULT 10,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table `symptom_keywords`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `symptom_keywords`;
CREATE TABLE `symptom_keywords` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(100) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `weight` INT NOT NULL DEFAULT 15,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Table `classification_rules`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `classification_rules`;
CREATE TABLE `classification_rules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `rule_name` VARCHAR(150) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `conditions_json` TEXT NOT NULL,
  `score` INT NOT NULL DEFAULT 30,
  `priority` INT NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Table `troubleshooting_steps`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `troubleshooting_steps`;
CREATE TABLE `troubleshooting_steps` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `step_number` INT NOT NULL DEFAULT 1,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `command` VARCHAR(255) NULL,
  `platform` ENUM('both', 'windows', 'linux', 'gui') NOT NULL DEFAULT 'both',
  `verification_question` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Table `network_commands`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `network_commands`;
CREATE TABLE `network_commands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `command` VARCHAR(255) NOT NULL,
  `platform` ENUM('windows', 'linux', 'both') NOT NULL DEFAULT 'windows',
  `description` TEXT NOT NULL,
  `output_sample` TEXT NULL,
  `category_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Table `knowledge_articles`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `knowledge_articles`;
CREATE TABLE `knowledge_articles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `summary` TEXT NOT NULL,
  `content` LONGTEXT NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `read_time` VARCHAR(20) NOT NULL DEFAULT '5 min read',
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. Table `diagnosis_sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `diagnosis_sessions`;
CREATE TABLE `diagnosis_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_token` VARCHAR(64) NOT NULL,
  `connection_type` VARCHAR(50) NOT NULL DEFAULT 'unknown',
  `input_data_json` LONGTEXT NOT NULL,
  `classification_source` ENUM('Rule-Based', 'AI-Assisted', 'Hybrid') NOT NULL DEFAULT 'Rule-Based',
  `user_ip` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 14. Table `diagnosis_results`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `diagnosis_results`;
CREATE TABLE `diagnosis_results` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `osi_layer` VARCHAR(50) NOT NULL,
  `confidence_score` INT NOT NULL DEFAULT 0,
  `severity` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
  `possible_causes_json` TEXT NOT NULL,
  `status` ENUM('resolved', 'unresolved', 'pending', 'unsure') NOT NULL DEFAULT 'pending',
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `diagnosis_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 15. Table `unresolved_cases`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `unresolved_cases`;
CREATE TABLE `unresolved_cases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NULL,
  `user_description` TEXT NOT NULL,
  `predicted_category` VARCHAR(100) NULL,
  `admin_notes` TEXT NULL,
  `case_status` ENUM('open', 'investigating', 'resolved_rule_added', 'closed') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 16. Table `ai_settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `ai_settings`;
CREATE TABLE `ai_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'mock',
  `api_endpoint` VARCHAR(255) NOT NULL DEFAULT 'https://api.openai.com/v1/chat/completions',
  `api_key` VARCHAR(255) NULL,
  `model_name` VARCHAR(100) NOT NULL DEFAULT 'gpt-4o-mini',
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `timeout` INT NOT NULL DEFAULT 8,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 17. Table `activity_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_table` VARCHAR(50) NULL,
  `target_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- SEED DEMO DATA
-- ==========================================================

-- 1. Default Super Admin (Username: admin | Password: Admin@12345)
-- Verified Bcrypt hash for Admin@12345: $2y$10$zgahZo5HJkTgF8jXf2HxQO8g.vP97Zwzu4e5Mx6ta9bA8ivPtMLlW
INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 'Lead Systems Administrator', 'admin', 'admin@networktrouble.local', '$2y$10$zgahZo5HJkTgF8jXf2HxQO8g.vP97Zwzu4e5Mx6ta9bA8ivPtMLlW', 'super_admin', 'active');

-- 2. Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'NetworkTrouble', 'text'),
('site_tagline', 'Understand Your Network. Solve Problems Smarter.', 'text'),
('site_description', 'Sistem diagnosis gangguan jaringan berbasis klasifikasi gejala, pemetaan OSI Layer, rule-based engine, dan AI-assisted symptom interpretation.', 'textarea'),
('meta_keywords', 'network troubleshooting, diagnosis jaringan, osi layer, ping, dns, wifi trouble, ip conflict', 'text'),
('hero_title', 'Diagnose Network Problems with Confidence', 'text'),
('hero_subtitle', 'Identify network issues, understand the OSI layer involved, and follow structured troubleshooting steps to restore connectivity swiftly.', 'textarea'),
('cta_button_text', 'Start Diagnosis', 'text'),
('cta_button_url', 'diagnose.php', 'text'),
('secondary_button_text', 'Explore Network Guide', 'text'),
('secondary_button_url', 'how-it-works.php', 'text'),
('navbar_brand_text', 'NetworkTrouble', 'text'),
('navbar_badge_text', 'v1.0 • Diagnostic AI', 'text'),
('navbar_button_text', 'Start Diagnosis', 'text'),
('footer_about', 'NetworkTrouble adalah platform edukasi dan diagnostik awal untuk mempermudah teknisi, siswa, dan pengelola laboratorium dalam mendiagnosis anomali jaringan komputer secara terstruktur.', 'textarea'),
('footer_copyright', '© 2026 NetworkTrouble. All rights reserved. Built for professional network engineers and learners.', 'text');

-- 2b. Page Contents Seed Data
INSERT INTO `page_contents` (`page_slug`, `section_key`, `title`, `subtitle`, `content`, `icon`, `display_order`, `status`) VALUES
('home', 'navbar', 'NetworkTrouble', 'v1.0 • Diagnostic AI', 'Start Diagnosis', 'bi-hdd-network-fill', 1, 'published'),
('home', 'hero', 'Diagnose Network Problems with Confidence', 'Sistem Diagnosis Jaringan Aktif & Siap Uji', 'Identify network issues, understand the OSI layer involved, and follow structured troubleshooting steps to restore connectivity swiftly.', 'bi-play-circle-fill', 2, 'published'),
('home', 'feature_1', 'Smart Classification', 'Mesin inferensi rule-based', 'Mesin inferensi rule-based menghitung bobot multi-gejala dan menguji kondisi logika majemuk untuk menentukan akar masalah konektivitas secara objektif.', 'bi-cpu', 1, 'published'),
('home', 'feature_2', 'OSI Layer Mapping', 'Pemetaan 7 Lapisan', 'Setiap gangguan langsung dipetakan ke 7 Lapisan OSI (Physical, Data Link, Network, Transport, Application) sehingga Anda tahu komponen mana yang harus diisolasi.', 'bi-layers', 2, 'published'),
('home', 'feature_3', 'Step-by-Step Roadmap', 'Panduan terstruktur', 'Instruksi penanganan kronologis mulai dari pemeriksaan fisik, rute IP, DNS flush, hingga perbaikan stack TCP/IP dengan contoh output perintah terminal.', 'bi-signpost-split', 3, 'published'),
('home', 'feature_4', 'AI-Assisted Symptom Input', 'Natural Language Interpreter', 'Sampaikan keluhan Anda dalam kalimat bebas bahasa Indonesia. Natural Language Interpreter mengekstrak entitas teknis ke dalam format data terstruktur.', 'bi-robot', 4, 'published'),
('home', 'feature_5', 'Diagnosis History', 'Penyimpanan sesi lokal', 'Simpan seluruh riwayat analisis gangguan jaringan di browser tanpa perlu registrasi rumit, pantau rasio masalah yang terselesaikan secara berkala.', 'bi-clock-history', 5, 'published'),
('home', 'feature_6', 'Admin Managed Knowledge Base', 'Manajemen aturan & panduan', 'Administrator laboratorium dan teknisi NOC dapat memperbarui aturan inferensi, menambah gejala baru, dan mengunggah artikel panduan melalui CMS internal.', 'bi-journal-code', 6, 'published'),
('home', 'how_it_works_header', 'Bagaimana NetworkTrouble Bekerja', 'Alur Diagnostik', 'Alur sistematis 4 langkah untuk mengubah ketidakpastian gangguan menjadi rencana aksi perbaikan yang terarah.', 'bi-diagram-3', 1, 'published'),
('home', 'step_1', 'Describe Your Problem', 'Langkah 1', 'Pilih jenis perangkat Anda (PC, Laptop, Smartphone) dan tentukan jenis koneksi yang bermasalah (Kabel LAN atau WiFi).', '1', 1, 'published'),
('home', 'step_2', 'Analyze Symptoms', 'Langkah 2', 'Pilih gejala-gejala spesifik yang Anda amati, atau ketikkan keluhan dalam bahasa sehari-hari untuk dianalisis mesin inferensi.', '2', 2, 'published'),
('home', 'step_3', 'Identify Network Layer', 'Langkah 3', 'Sistem mengklasifikasikan masalah dan memetakannya langsung ke model referensi OSI (Physical, Data Link, Network, Transport, atau Application).', '3', 3, 'published'),
('home', 'step_4', 'Follow Troubleshooting Steps', 'Langkah 4', 'Jalankan panduan perbaikan langkah demi langkah disertai perintah CLI terverifikasi untuk memulihkan koneksi dengan cepat.', '4', 4, 'published'),
('home', 'audience_header', 'Dibuat untuk Praktisi & Pembelajar Jaringan', 'Audiens & Pengguna', 'Menghilangkan kebiasaan asal cabut kabel atau restart sembarangan dengan memperkenalkan metodologi penelusuran gangguan berstandar sertifikasi (CompTIA Network+, CCNA, MTCNA).', 'bi-people', 1, 'published'),
('home', 'cta_section', 'Siap Menganalisis Gangguan Jaringan Anda?', 'Mulai Diagnosis Sekarang', 'Hanya butuh 1-2 menit untuk menjawab wizard gejala dan mendapatkan diagnosa akurat dengan panduan perbaikan terverifikasi.', 'diagnose.php', 1, 'published');

-- 3. Contact Settings
INSERT INTO `contact_settings` (`id`, `contact_name`, `phone`, `email`, `address`, `working_hours`, `whatsapp`) VALUES
(1, 'Network Operations Center (NOC) Support', '+62 812-3456-7890', 'support@networktrouble.local', 'Gedung Laboratorium Komputer & Jaringan Lantai 3, IT Center Campus', 'Senin - Jumat: 08:00 - 17:00 WIB', '6281234567890');

-- 4. Network Categories (9 Categories)
INSERT INTO `network_categories` (`id`, `name`, `slug`, `description`, `osi_layer`, `severity_default`, `icon`, `status`) VALUES
(1, 'Physical Layer Issue', 'physical-layer-issue', 'Masalah pada media fisik jaringan seperti kabel LAN putus/rusak, port RJ45 longgar, perangkat mati, atau konektor bermasalah.', 'Layer 1 (Physical)', 'High', 'bi-ethernet', 'active'),
(2, 'Data Link Layer Issue', 'data-link-layer-issue', 'Masalah pada switch, framing data, konflik MAC address, port negotiation mismatch (Duplex/Speed), atau VLAN configuration.', 'Layer 2 (Data Link)', 'Medium', 'bi-diagram-2', 'active'),
(3, 'Network Layer Issue', 'network-layer-issue', 'Masalah pengalamatan IP, kegagalan DHCP server (IP APIPA 169.254.x.x), konflik IP statis, atau kesalahan rute gateway.', 'Layer 3 (Network)', 'High', 'bi-diagram-3', 'active'),
(4, 'Transport Layer Issue', 'transport-layer-issue', 'Gangguan pada protokol TCP/UDP, firewall port blocking, koneksi timeout, atau reset packet akibat security policy.', 'Layer 4 (Transport)', 'Medium', 'bi-shield-check', 'active'),
(5, 'Application Layer Issue', 'application-layer-issue', 'Masalah pada layanan aplikasi seperti DNS resolver gagal, proxy/VPN error, SSL certificate expired, atau web server target down.', 'Layer 7 (Application)', 'Medium', 'bi-globe2', 'active'),
(6, 'Wireless Issue', 'wireless-issue', 'Gangguan pada transmisi nirkabel, sinyal WiFi lemah, interferensi frekuensi 2.4/5GHz, password salah, atau rogue AP.', 'Layer 1-2 (Wireless/PHY)', 'Medium', 'bi-wifi', 'active'),
(7, 'Performance Issue', 'performance-issue', 'Jaringan lambat, packet loss tinggi, jitter, latency melonjak (high ping), atau bandwidth congestion akibat traffic berlebih.', 'Cross-Layer (QoS)', 'Medium', 'bi-speedometer2', 'active'),
(8, 'Security Issue', 'security-issue', 'Indikasi serangan ARP spoofing, DHCP rogue, DNS hijacking, flood attack, atau pembatasan firewall yang tidak semestinya.', 'Cross-Layer (Security)', 'Critical', 'bi-shield-exclamation', 'active'),
(9, 'Unknown Network Issue', 'unknown-network-issue', 'Gejala belum cukup spesifik untuk menentukan lapisan masalah. Memerlukan isolasi fisik dan log diagnostik lanjutan.', 'Unknown / Multi-Layer', 'Low', 'bi-question-circle', 'active');

-- 5. Network Symptoms (20+ Symptoms)
INSERT INTO `network_symptoms` (`id`, `category_id`, `symptom_name`, `symptom_description`, `input_type`, `weight`, `status`) VALUES
(1, 1, 'Kabel LAN tidak terdeteksi / Tidak ada lampu indikator', 'Lampu LED pada port Ethernet router/PC tidak menyala sama sekali.', 'checkbox', 35, 'active'),
(2, 6, 'WiFi tidak muncul dalam daftar SSID', 'Nama sinyal WiFi hilang dan tidak dapat ditemukan di perangkat.', 'checkbox', 30, 'active'),
(3, 3, 'Mendapatkan IP Address 169.254.x.x (APIPA)', 'Perangkat gagal menghubungi server DHCP sehingga memperoleh IP otomatis bawaan Windows.', 'checkbox', 35, 'active'),
(4, 3, 'Peringatan IP Address Conflict terdeteksi', 'Muncul notifikasi bahwa IP address yang digunakan telah dipakai perangkat lain.', 'checkbox', 30, 'active'),
(5, 5, 'Bisa ping 8.8.8.8 tetapi tidak bisa buka website dengan domain', 'Koneksi ke IP publik berhasil, namun resolving nama domain selalu gagal.', 'checkbox', 40, 'active'),
(6, 7, 'Koneksi internet lambat / Loading berputar lama', 'Kecepatan download/upload jauh di bawah bandwidth langganan.', 'checkbox', 25, 'active'),
(7, 7, 'Ping tinggi dan sering Request Timed Out (RTO)', 'Uji ping ke gateway atau internet mengalami packet loss signifikan.', 'checkbox', 25, 'active'),
(8, 6, 'WiFi sering terputus sendiri secara acak', 'Koneksi nirkabel disconnect setiap beberapa menit lalu reconnect.', 'checkbox', 25, 'active'),
(9, 3, 'Tidak bisa ping Default Gateway lokal', 'Ping ke alamat IP router lokal (misal 192.168.1.1) tidak merespons.', 'checkbox', 35, 'active'),
(10, 5, 'Hanya website tertentu yang tidak bisa dibuka', 'Layanan seperti YouTube atau Google bisa, namun website kampus/kantor tidak bisa.', 'checkbox', 30, 'active'),
(11, 4, 'Port aplikasi tertutup / Connection refused', 'Koneksi ke port spesifik (misal SSH 22, Web 80/443, MySQL 3306) ditolak.', 'checkbox', 30, 'active'),
(12, 1, 'Kabel terasa longgar dan klip konektor RJ45 patah', 'Konektor RJ45 tidak terkunci dengan erat pada port LAN.', 'checkbox', 30, 'active'),
(13, 2, 'Kecepatan port terkunci pada 10 Mbps (Negotiation Mismatch)', 'Link speed di adapter properties hanya 10 Mbps padahal port Gigabit.', 'checkbox', 30, 'active'),
(14, 8, 'Halaman dialihkan ke situs asing / Sertifikat SSL peringatan merah', 'DNS redirection mencurigakan atau peringatan man-in-the-middle.', 'checkbox', 40, 'active'),
(15, 6, 'Sinyal WiFi hanya 1 bar atau sangat lemah', 'Jarak terlalu jauh dari access point atau terhalang dinding tebal.', 'checkbox', 25, 'active'),
(16, 5, 'Pesan error DNS_PROBE_FINISHED_NXDOMAIN', 'Browser menampilkan kode kesalahan DNS gagal memetakan nama domain.', 'checkbox', 35, 'active'),
(17, 3, 'Status jaringan "No Internet Access" bertanda seru kuning', 'Adapter terhubung ke gateway tetapi routing ke WAN terputus.', 'checkbox', 25, 'active'),
(18, 7, 'Hanya satu perangkat yang lambat, perangkat lain normal', 'Penyebab terlokalisir pada adapter perangkat atau aplikasi latar belakang.', 'checkbox', 20, 'active'),
(19, 8, 'ARP cache poisoning terdeteksi / MAC gateway berubah', 'Alamat MAC gateway terduplikasi pada tabel ARP perangkat.', 'checkbox', 40, 'active'),
(20, 1, 'Perangkat router/switch panas berlebih (overheat)', 'Hardware jaringan terasa panas dan lampu LED berkedip abnormal.', 'checkbox', 30, 'active');

-- 6. Symptom Keywords (35+ Keywords for Fallback matching)
INSERT INTO `symptom_keywords` (`id`, `keyword`, `category_id`, `weight`) VALUES
('1', 'kabel', 1, 25),
('2', 'putus', 1, 20),
('3', 'rj45', 1, 30),
('4', 'lan tidak terdeteksi', 1, 35),
('5', 'lampu lan mati', 1, 35),
('6', 'switch mati', 1, 30),
('7', 'mac address', 2, 25),
('8', 'duplex', 2, 25),
('9', 'vlan', 2, 25),
('10', 'dhcp', 3, 30),
('11', '169.254', 3, 40),
('12', 'apipa', 3, 40),
('13', 'konflik ip', 3, 35),
('14', 'ip conflict', 3, 35),
('15', 'gateway rto', 3, 30),
('16', 'submask', 3, 20),
('17', 'subnet', 3, 20),
('18', 'port blocked', 4, 30),
('19', 'firewall port', 4, 30),
('20', 'tcp timeout', 4, 25),
('21', 'dns', 5, 35),
('22', 'domain', 5, 30),
('23', 'website tidak terbuka', 5, 30),
('24', 'nxdomain', 5, 40),
('25', 'ssl error', 5, 30),
('26', 'wifi hilang', 6, 35),
('27', 'ssid tidak muncul', 6, 35),
('28', 'sinyal lemah', 6, 25),
('29', 'wifi terputus', 6, 30),
('30', 'lambat', 7, 25),
('31', 'lemot', 7, 25),
('32', 'ping tinggi', 7, 30),
('33', 'latency', 7, 30),
('34', 'packet loss', 7, 35),
('35', 'rto', 7, 25),
('36', 'arp spoofing', 8, 40),
('37', 'serangan', 8, 30),
('38', 'akses ilegal', 8, 30);

-- 7. Classification Rules (20+ Rules)
INSERT INTO `classification_rules` (`id`, `rule_name`, `category_id`, `conditions_json`, `score`, `priority`) VALUES
(1, 'Rule Physical: Port Cable Disconnected', 1, '{"connection_type":["lan"],"symptoms":[1,12]}', 50, 1),
(2, 'Rule Wireless: Missing SSID & Drops', 6, '{"connection_type":["wifi"],"symptoms":[2,8]}', 45, 1),
(3, 'Rule Network: DHCP Lease Failure (APIPA)', 3, '{"symptoms":[3]}', 50, 1),
(4, 'Rule Network: IP Address Conflict Detected', 3, '{"symptoms":[4]}', 45, 1),
(5, 'Rule Application: Classic DNS Failure (Ping IP OK, Domain Fails)', 5, '{"symptoms":[5,16]}', 60, 1),
(6, 'Rule Performance: High Latency & RTO', 7, '{"symptoms":[6,7]}', 40, 2),
(7, 'Rule Network: Default Gateway Unreachable', 3, '{"symptoms":[9,17]}', 45, 1),
(8, 'Rule Application: Specific Domain/Service Block', 5, '{"symptoms":[10]}', 35, 2),
(9, 'Rule Transport: Port Access Refused/Filtered', 4, '{"symptoms":[11]}', 40, 2),
(10, 'Rule Security: ARP Poisoning & Suspicious Redirect', 8, '{"symptoms":[14,19]}', 55, 1),
(11, 'Rule Wireless: Low RSSI / Signal Degradation', 6, '{"connection_type":["wifi"],"symptoms":[8,15]}', 40, 2),
(12, 'Rule Data Link: Auto-Negotiation Speed Mismatch', 2, '{"connection_type":["lan"],"symptoms":[13]}', 45, 2),
(13, 'Rule Physical: Hardware Overheating / Failure', 1, '{"symptoms":[20]}', 35, 2),
(14, 'Rule Performance: Client Isolated Bottleneck', 7, '{"symptoms":[18]}', 35, 3),
(15, 'Rule Application: Domain NXDOMAIN Resolution Drop', 5, '{"symptoms":[16]}', 45, 1),
(16, 'Rule Network: Routing WAN Drop With Gateway Intact', 3, '{"symptoms":[17]}', 35, 2),
(17, 'Rule Wireless: WiFi Disconnect Loop', 6, '{"connection_type":["wifi"],"symptoms":[8]}', 35, 2),
(18, 'Rule Security: Rogue Gateway Infiltration', 8, '{"symptoms":[19]}', 45, 1),
(19, 'Rule Performance: General Congestion Spike', 7, '{"symptoms":[6]}', 30, 3),
(20, 'Rule Physical: LAN Media Disconnected Flag', 1, '{"symptoms":[1]}', 40, 1);

-- 8. Troubleshooting Steps (30+ Steps across Categories)
INSERT INTO `troubleshooting_steps` (`id`, `category_id`, `step_number`, `title`, `description`, `command`, `platform`, `verification_question`) VALUES
-- Physical
(1, 1, 1, 'Periksa Fisik Kabel UTP/STP', 'Pastikan kabel LAN terpasang erat di port PC dan router hingga berbunyi klik.', NULL, 'gui', 'Apakah lampu indikator port menyala?'),
(2, 1, 2, 'Uji dengan Kabel atau Port Alternatif', 'Pindahkan kabel ke port LAN switch lain atau gunakan kabel patch cord baru.', NULL, 'gui', 'Apakah terdeteksi di port baru?'),
(3, 1, 3, 'Cek Status Network Interface Controller (NIC)', 'Periksa Device Manager atau lspci untuk memastikan driver adapter LAN aktif.', 'ncpa.cpl', 'windows', 'Apakah adapter Ethernet bertatus Enabled?'),
-- Data Link
(4, 2, 1, 'Periksa Link Speed & Duplex Mode', 'Pastikan adapter diatur ke Auto Negotiation dan switch tidak terkunci pada half-duplex.', 'netsh interface ipv4 show subinterfaces', 'windows', 'Apakah kecepatan link terbaca 100/1000 Mbps?'),
(5, 2, 2, 'Verifikasi Tabel ARP dan MAC Address', 'Periksa tabel ARP untuk memastikan MAC address gateway valid dan tidak bermasalah.', 'arp -a', 'both', 'Apakah MAC address gateway valid?'),
(6, 2, 3, 'Restart Switch Jaringan', 'Lakukan restart pada switch unmanaged atau periksa konfigurasi VLAN di managed switch.', NULL, 'gui', 'Apakah lampu aktivitas port berkedip normal?'),
-- Network
(7, 3, 1, 'Periksa Konfigurasi IP & Gateway', 'Cek apakah komputer mendapatkan IP dari DHCP router atau terjebak di 169.254.x.x.', 'ipconfig /all', 'windows', 'Apakah IP yang didapat berada dalam subnet router?'),
(8, 3, 2, 'Lakukan Release & Renew DHCP Lease', 'Minta alokasi IP baru secara paksa dari DHCP server jaringan.', 'ipconfig /release && ipconfig /renew', 'windows', 'Apakah IP baru berhasil diterima?'),
(9, 3, 3, 'Uji Koneksi ke Default Gateway', 'Lakukan ping ke IP gateway lokal untuk memastikan jalur lokal berfungsi.', 'ping -n 4 192.168.1.1', 'windows', 'Apakah ada balasan Reply from...?'),
(10, 3, 4, 'Deteksi Konflik IP Address', 'Jika ada peringatan konflik, atur IP secara statis sementara ke host kosong.', NULL, 'gui', 'Apakah notifikasi konflik hilang?'),
-- Transport
(11, 4, 1, 'Uji Akses Port Layanan', 'Gunakan Test-NetConnection atau telnet/nc untuk menguji apakah port tujuan terbuka.', 'Test-NetConnection -ComputerName 8.8.8.8 -Port 53', 'windows', 'Apakah TcpTestSucceeded bernilai True?'),
(12, 4, 2, 'Periksa Firewall Lokal', 'Nonaktifkan sementara firewall lokal untuk memastikan port tidak diblokir aplikasi.', 'wf.msc', 'windows', 'Apakah koneksi berhasil setelah firewall diizinkan?'),
(13, 4, 3, 'Periksa Koneksi Aktif', 'Tinjau socket connection yang sedang open di komputer Anda.', 'netstat -ano', 'both', 'Apakah ada status TIME_WAIT atau CLOSE_WAIT berlebih?'),
-- Application / DNS
(14, 5, 1, 'Flush DNS Resolver Cache', 'Bersihkan cache DNS lokal komputer Anda untuk membuang entri rusak.', 'ipconfig /flushdns', 'windows', 'Apakah muncul Successfully flushed the DNS Resolver Cache?'),
(15, 5, 2, 'Uji Resolusi Nama dengan Nslookup', 'Uji apakah DNS server mampu menerjemahkan domain Google atau domain target.', 'nslookup google.com 8.8.8.8', 'both', 'Apakah muncul IP address dari domain tersebut?'),
(16, 5, 3, 'Ubah DNS ke Public Resolver', 'Gunakan Public DNS terpercaya seperti Cloudflare (1.1.1.1) atau Google (8.8.8.8).', NULL, 'gui', 'Apakah website sekarang bisa terbuka?'),
(17, 5, 4, 'Bypass Proxy & Reset Socket Jaringan', 'Reset stack TCP/IP dan konfigurasi Winsock ke kondisi awal.', 'netsh int ip reset && netsh winsock reset', 'windows', 'Apakah browser dapat mengakses web setelah reboot?'),
-- Wireless
(18, 6, 1, 'Forget & Reconnect Jaringan WiFi', 'Hapus profil WiFi yang tersimpan di perangkat lalu sambungkan kembali.', 'netsh wlan show interfaces', 'windows', 'Apakah berhasil autentikasi ulang?'),
(19, 6, 2, 'Periksa Kekuatan Sinyal & Kanal WiFi', 'Pastikan sinyal tidak terdistorsi dan kanal frekuensi tidak terlalu padat.', 'netsh wlan show networks mode=bssid', 'windows', 'Apakah kekuatan sinyal di atas 60%?'),
(20, 6, 3, 'Restart Access Point / Router WiFi', 'Cabut adaptor daya router selama 15 detik lalu nyalakan kembali.', NULL, 'gui', 'Apakah SSID muncul dan stabil?'),
-- Performance
(21, 7, 1, 'Lakukan Uji Latensi & Packet Loss', 'Kirim 20 paket ping untuk memeriksa kestabilan koneksi dan packet loss.', 'ping -n 20 8.8.8.8', 'windows', 'Apakah packet loss 0% dan latensi stabil?'),
(22, 7, 2, 'Traceroute Jalur Hop Jaringan', 'Lacak di hop (router) mana terjadi lonjakan latensi atau timeout.', 'tracert -d 8.8.8.8', 'windows', 'Di router manakah terjadi latensi tertinggi?'),
(23, 7, 3, 'Identifikasi Penggunaan Bandwidth', 'Buka Task Manager pada tab Performance/Network untuk memeriksa aplikasi rakus data.', 'resmon', 'windows', 'Apakah ada background download atau update berjalan?'),
-- Security
(24, 8, 1, 'Inspeksi Tabel ARP untuk Deteksi Spoofing', 'Pastikan tidak ada 2 IP berbeda yang menggunakan MAC address yang sama.', 'arp -a', 'both', 'Apakah semua entri MAC address terlihat wajar?'),
(25, 8, 2, 'Verifikasi Pengaturan File Hosts', 'Buka file hosts di system32 untuk memastikan tidak ada pengalihan domain ilegal.', 'notepad C:\\Windows\\System32\\drivers\\etc\\hosts', 'windows', 'Apakah file hosts bersih tanpa redirect mencurigakan?'),
(26, 8, 3, 'Scan Malware Jaringan & Rogue Adapter', 'Jalankan scan keamanan dan nonaktifkan adapter VPN/tunnel yang tidak dikenal.', NULL, 'gui', 'Apakah adapter asing berhasil dinonaktifkan?'),
-- Unknown Issue
(27, 9, 1, 'Uji Koneksi Bertingkat (Physical to WAN)', 'Mulai dari uji loopback (127.0.0.1) -> IP lokal -> Gateway -> IP WAN.', 'ping 127.0.0.1', 'both', 'Apakah TCP/IP stack lokal berfungsi normal?'),
(28, 9, 2, 'Restart Lengkap Komputer dan Modem', 'Matikan PC, router, dan modem, tunggu 30 detik lalu nyalakan secara berurutan.', NULL, 'gui', 'Apakah koneksi pulih setelah restart?'),
(29, 9, 3, 'Kumpulkan Diagnostik Lengkap untuk Tim IT', 'Simpan informasi konfigurasi jaringan untuk diserahkan ke teknisi support.', 'ipconfig /all > C:\\network_info.txt', 'windows', 'Apakah file network_info.txt berhasil dibuat?');

-- 9. Network Commands (15+ Command Toolkit)
INSERT INTO `network_commands` (`id`, `name`, `command`, `platform`, `description`, `output_sample`, `category_id`) VALUES
(1, 'IP Configuration Details', 'ipconfig /all', 'windows', 'Menampilkan seluruh konfigurasi adapter, MAC address, IP, subnet, gateway, DHCP & DNS server.', 'IPv4 Address: 192.168.1.15\nDefault Gateway: 192.168.1.1\nDHCP Server: 192.168.1.1\nDNS Servers: 8.8.8.8', 3),
(2, 'Release DHCP Lease', 'ipconfig /release', 'windows', 'Melepas alamat IP saat ini yang diberikan oleh DHCP server.', 'Ethernet adapter: IP address released.', 3),
(3, 'Renew DHCP Lease', 'ipconfig /renew', 'windows', 'Meminta alokasi alamat IP baru dari DHCP server.', 'IPv4 Address: 192.168.1.20\nSubnet Mask: 255.255.255.0', 3),
(4, 'Flush DNS Cache', 'ipconfig /flushdns', 'windows', 'Membersihkan cache DNS lokal untuk menghapus entri domain yang korup atau kedaluwarsa.', 'Successfully flushed the DNS Resolver Cache.', 5),
(5, 'Ping Gateway/Host', 'ping 8.8.8.8', 'both', 'Menguji konektivitas dasar ICMP dan mengukur latency bolak-balik (round trip time).', 'Reply from 8.8.8.8: bytes=32 time=18ms TTL=117', 7),
(6, 'Trace Route Path', 'tracert 8.8.8.8', 'windows', 'Melacak setiap lompatan router (hop) dari komputer lokal ke alamat tujuan di internet.', '1  <1 ms  <1 ms  192.168.1.1\n2  12 ms  14 ms  10.120.0.1\n3  18 ms  17 ms  8.8.8.8', 7),
(7, 'DNS Lookup Tool', 'nslookup google.com', 'both', 'Menguji apakah server DNS berfungsi dan melihat pemetaan domain ke alamat IP.', 'Server: dns.google\nAddress: 8.8.8.8\n\nName: google.com\nAddresses: 142.250.190.46', 5),
(8, 'Display ARP Table', 'arp -a', 'both', 'Menampilkan tabel pemetaan IP ke MAC address pada jaringan lokal.', 'Interface: 192.168.1.15\nInternet Address  Physical Address   Type\n192.168.1.1       00-14-22-01-23-45  dynamic', 2),
(9, 'Network Statistics', 'netstat -ano', 'both', 'Menampilkan semua port yang sedang listening atau established beserta Process ID (PID).', 'Proto  Local Address          Foreign Address        State        PID\nTCP    192.168.1.15:52412     142.250.190.46:443     ESTABLISHED  12480', 4),
(10, 'Test TCP Port Connection', 'Test-NetConnection 8.8.8.8 -Port 53', 'windows', 'Memeriksa apakah port TCP tertentu pada remote server dapat dihubungi.', 'ComputerName: 8.8.8.8\nRemotePort: 53\nTcpTestSucceeded: True', 4),
(11, 'Reset TCP/IP Stack', 'netsh int ip reset', 'windows', 'Menyetel ulang seluruh registry TCP/IP ke kondisi instalasi awal Windows.', 'Resetting Global, completed!\nRestart the computer to complete this action.', 3),
(12, 'Reset Winsock Catalog', 'netsh winsock reset', 'windows', 'Memperbaiki kerusakan pada layer Winsock yang sering diubah oleh malware/VPN.', 'Successfully reset the Winsock Catalog.\nYou must restart the computer.', 3),
(13, 'Show WiFi Status & RSSI', 'netsh wlan show interfaces', 'windows', 'Menampilkan status adapter WiFi, SSID terhubung, tipe enkripsi, dan kekuatan sinyal (%).', 'SSID: Office_NOC_5G\nSignal: 88%\nRadio type: 802.11ac', 6),
(14, 'Linux IP Address & Link', 'ip addr show', 'linux', 'Menampilkan daftar interface jaringan, status link UP/DOWN, dan konfigurasi IP di Linux.', '2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500\n    inet 192.168.1.15/24 brd 192.168.1.255', 3),
(15, 'Linux Tracepath', 'tracepath -n 8.8.8.8', 'linux', 'Alternatif traceroute modern di Linux untuk menguji rute dan mendeteksi MTU path.', '1?: [LOCALHOST] pmtu 1500\n1:  192.168.1.1     1.124ms', 7),
(16, 'Linux DNS Dig Diagnostic', 'dig google.com +short', 'linux', 'Tool kueri DNS paling komprehensif di Linux untuk inspeksi record DNS.', '142.250.190.46', 5);

-- 10. Knowledge Articles (5+ Articles)
INSERT INTO `knowledge_articles` (`id`, `title`, `slug`, `summary`, `content`, `category_id`, `read_time`, `views`, `status`) VALUES
(1, 'Memahami 7 Lapisan OSI Model dalam Troubleshooting Nyata', 'memahami-7-lapisan-osi-model', 'Pelajari bagaimana mengisolasi gangguan jaringan menggunakan pendekatan Bottom-Up dari Physical Layer hingga Application Layer.', '<h2>Mengapa OSI Model Penting untuk Teknisi?</h2><p>OSI (Open Systems Interconnection) Model bukan hanya teori ujian di kampus atau sertifikasi. Dalam dunia nyata, teknisi jaringan profesional menggunakan model ini sebagai kerangka mental untuk memecahkan masalah (troubleshooting) secara sistematis tanpa membuang waktu menduga-duga.</p><h3>Pendekatan Bottom-Up (Layer 1 ke Layer 7)</h3><p>Pendekatan Bottom-Up dimulai dari pemeriksaan kabel fisik, lampu port, radio wireless, lalu naik ke IP configuration, routing, port firewall, hingga software aplikasi. Pendekatan ini sangat efektif saat komputer sama sekali tidak memiliki koneksi.</p><h3>Pendekatan Top-Down (Layer 7 ke Layer 1)</h3><p>Jika pengguna mengeluh "aplikasi web lambat" padahal internet jalan, teknisi memulai dari aplikasi, browser, DNS, sebelum mencurigai kerusakan kabel fisik.</p>', 1, '6 min read', 124, 'published'),
(2, 'Cara Kerja DNS dan Mengapa Sering Menjadi Biang Kerok "No Internet"', 'cara-kerja-dns-no-internet', 'Mengapa Anda bisa ping ke 8.8.8.8 tetapi tidak bisa membuka Google? Pahami peran domain name system dan cara memperbaikinya.', '<h2>DNS: Buku Telepon Internet</h2><p>Komputer berkomunikasi menggunakan deretan angka IP Address, sedangkan manusia lebih mudah mengingat nama seperti <code>google.com</code> atau <code>kemdikbud.go.id</code>. DNS (Domain Name System) bertugas menerjemahkan nama domain tersebut menjadi IP address.</p><h3>Gejala Khas Masalah DNS</h3><ul><li>Aplikasi chatting (WhatsApp) tetap bisa menerima pesan, tetapi browser gagal membuka website apapun.</li><li>Pesan error browser: <code>DNS_PROBE_FINISHED_NXDOMAIN</code> atau <code>ERR_NAME_NOT_RESOLVED</code>.</li><li>Ping ke <code>8.8.8.8</code> sukses 100%, tetapi ping ke <code>google.com</code> menghasilkan pesan "Could not find host".</li></ul><h3>Solusi Cepat</h3><p>Lakukan pembersihan cache lokal melalui terminal menggunakan perintah <code>ipconfig /flushdns</code> atau ganti DNS adapter Anda ke resolver publik seperti <code>1.1.1.1</code> (Cloudflare) atau <code>8.8.8.8</code> (Google).</p>', 5, '5 min read', 98, 'published'),
(3, 'Misteri IP 169.254.x.x (APIPA): Mengapa Laptop Anda Mengalaminya?', 'misteri-ip-apipa-169-254', 'Apa itu Automatic Private IP Addressing (APIPA) dan bagaimana langkah memperbaikinya ketika DHCP server gagal merespons.', '<h2>Apa Itu Alamat APIPA?</h2><p>Ketika adapter jaringan disetel ke mode DHCP (otomatis memperoleh IP), ia akan mengirimkan paket DORA (Discover, Offer, Request, Acknowledge) ke router. Jika setelah beberapa saat router atau DHCP server tidak merespons sama sekali, sistem operasi Windows secara mandiri mengalokasikan IP dari rentang <strong>169.254.0.1 hingga 169.254.255.254</strong> dengan subnet mask 255.255.0.0.</p><h3>Penyebab Utama:</h3><ol><li>Router DHCP pool sudah habis (exhausted).</li><li>Kabel LAN tersambung ke port yang salah (port isolasi / switch mati).</li><li>Antivirus pihak ketiga memblokir paket UDP port 67/68.</li><li>Server DHCP lokal mengalami crash.</li></ol>', 3, '4 min read', 76, 'published'),
(4, 'Panduan Lengkap Membaca Hasil Perintah Ping dan Tracert', 'panduan-membaca-hasil-ping-tracert', 'Jangan hanya melihat "Reply" atau "RTO". Ketahui arti latensi, TTL (Time To Live), jitter, dan cara mendeteksi router yang bermasalah di tengah jalan.', '<h2>Membedah Hasil Ping</h2><p>Saat Anda menjalankan perintah <code>ping 8.8.8.8</code>, Anda akan melihat tiga parameter utama:</p><ul><li><strong>Bytes:</strong> Ukuran paket ICMP yang dikirim (default Windows: 32 bytes).</li><li><strong>Time:</strong> Waktu yang dibutuhkan paket untuk bolak-balik (Round Trip Time). Di bawah 30ms tergolong sangat cepat untuk jaringan fiber optik lokal.</li><li><strong>TTL (Time to Live):</strong> Penghitung sisa lompatan router untuk mencegah looping paket di internet. Windows defaultnya 128, Linux 64, Cisco router 255.</li></ul>', 7, '7 min read', 115, 'published'),
(5, 'WiFi Sering Putus Sendiri? Ini 5 Penyebab Utama dan Cara Mengatasinya', 'wifi-sering-putus-penyebab-solusi', 'Mulai dari interferensi sinyal 2.4GHz, setting power saving adapter, hingga DHCP lease time yang terlalu singkat.', '<h2>Mengapa Sinyal Penuh Tapi Koneksi Sering Putus?</h2><p>Banyak pengguna mengira sinyal WiFi 4 bar menjamin koneksi lancar. Faktanya, stabilitas nirkabel sangat dipengaruhi oleh rasio sinyal terhadap noise (Signal-to-Noise Ratio), tabrakan frekuensi kanal (Channel Overlap), dan manajemen daya pada laptop.</p><h3>5 Langkah Penanganan:</h3><ol><li>Ganti kanal WiFi router dari mode Auto ke kanal 1, 6, atau 11 (pada frekuensi 2.4 GHz) untuk menghindari tumpang tindih.</li><li>Gunakan frekuensi 5 GHz jika jarak dengan router tidak terhalang banyak dinding semen.</li><li>Nonaktifkan fitur "Allow the computer to turn off this device to save power" di Device Manager adapter WiFi.</li><li>Perbarui driver chipset wireless adapter Anda.</li><li>Periksa apakah masa sewa DHCP (Lease Time) di router diatur terlalu singkat (misal hanya 10 menit).</li></ol>', 6, '5 min read', 89, 'published');

-- 11. AI Settings (Default: Mock AI Provider)
INSERT INTO `ai_settings` (`id`, `provider`, `api_endpoint`, `api_key`, `model_name`, `enabled`, `timeout`) VALUES
(1, 'mock', 'https://api.openai.com/v1/chat/completions', '', 'gpt-4o-mini', 1, 8);

-- 12. Sample Diagnosis Sessions & Results (Demo History)
INSERT INTO `diagnosis_sessions` (`id`, `session_token`, `connection_type`, `input_data_json`, `classification_source`, `user_ip`, `created_at`) VALUES
(1, 'demo_session_token_1001', 'wifi', '{"connection_type":"wifi","symptoms":[5,16],"custom_text":"Bisa buka WA tapi ga bisa buka web kampus"}', 'Rule-Based', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'demo_session_token_1002', 'lan', '{"connection_type":"lan","symptoms":[1,12],"custom_text":"Lampu LAN ga nyala"}', 'Rule-Based', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'demo_session_token_1003', 'wifi', '{"connection_type":"wifi","symptoms":[6,7],"custom_text":"Internet lemot sekali buat zoom meeting"}', 'Hybrid', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 HOUR));

INSERT INTO `diagnosis_results` (`id`, `session_id`, `category_id`, `osi_layer`, `confidence_score`, `severity`, `possible_causes_json`, `status`, `resolved_at`, `created_at`) VALUES
(1, 1, 5, 'Layer 7 (Application)', 92, 'Medium', '["DNS Server Resolver Gagal Merespons","Cache DNS Lokal Korup","Konfigurasi DNS Adapter Tidak Tepat"]', 'resolved', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 1, 'Layer 1 (Physical)', 88, 'High', '["Kabel UTP Putus atau Tertekuk","Konektor RJ45 Longgar / Pin Kotor","Port Switch Rusak"]', 'resolved', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 3, 7, 'Cross-Layer (QoS)', 75, 'Medium', '["Bandwidth Throttling / Congestion Jaringan","High Latency / Jitter dari ISP","Background Download Berjalan"]', 'pending', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- 13. Sample Unresolved Case
INSERT INTO `unresolved_cases` (`id`, `session_id`, `user_description`, `predicted_category`, `admin_notes`, `case_status`, `created_at`) VALUES
(1, 3, 'Internet lemot sekali buat zoom meeting dan sering disconnect audio', 'Performance Issue', 'Perlu ditinjau apakah perlu menambahkan aturan khusus QoS Voice/Video buffer.', 'investigating', DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- 14. Sample Activity Log
INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `target_table`, `target_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'System Installed & Demo Data Seeded', 'system', 1, '127.0.0.1', NOW());
