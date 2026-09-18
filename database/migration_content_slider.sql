-- ==========================================================
-- Database Migration: Content & Slider Integration
-- Target: networktrouble_db
-- Safe to execute multiple times (IF NOT EXISTS & ON DUPLICATE KEY)
-- ==========================================================

USE `networktrouble_db`;

-- 1. Create table `slider_items`
CREATE TABLE IF NOT EXISTS `slider_items` (
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
  CONSTRAINT `fk_slider_media` FOREIGN KEY (`media_id`) REFERENCES `media_assets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ensure `icon` column exists in `page_contents`
SET @dbname = DATABASE();
SET @tablename = "page_contents";
SET @columnname = "icon";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE page_contents ADD COLUMN icon VARCHAR(60) NULL AFTER image_id"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ensure `display_order` column exists in `page_contents`
SET @columnname2 = "display_order";
SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname2)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE page_contents ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER icon"
));
PREPARE alterIfNotExists2 FROM @preparedStatement2;
EXECUTE alterIfNotExists2;
DEALLOCATE PREPARE alterIfNotExists2;

-- 3. Seed Default Content into `page_contents`
INSERT INTO `page_contents` (`page_slug`, `section_key`, `title`, `subtitle`, `content`, `icon`, `display_order`, `status`) VALUES
-- Navbar Section
('home', 'navbar', 'NetworkTrouble', 'v1.0 • Diagnostic AI', 'Start Diagnosis', 'bi-hdd-network-fill', 1, 'published'),

-- Hero Section
('home', 'hero', 'Diagnose Network Problems with Confidence', 'Sistem Diagnosis Jaringan Aktif & Siap Uji', 'Identify network issues, understand the OSI layer involved, and follow structured troubleshooting steps to restore connectivity swiftly.', 'bi-play-circle-fill', 2, 'published'),

-- Hero Buttons (stored in page_contents as well as site_settings for maximum compatibility)
('home', 'hero_buttons', 'Start Diagnosis', 'Explore Network Guide', 'diagnose.php|how-it-works.php', 'bi-play-circle-fill', 3, 'published'),

-- Feature Cards 1 to 6
('home', 'feature_1', 'Smart Classification', 'Mesin inferensi rule-based', 'Mesin inferensi rule-based menghitung bobot multi-gejala dan menguji kondisi logika majemuk untuk menentukan akar masalah konektivitas secara objektif.', 'bi-cpu', 1, 'published'),
('home', 'feature_2', 'OSI Layer Mapping', 'Pemetaan 7 Lapisan', 'Setiap gangguan langsung dipetakan ke 7 Lapisan OSI (Physical, Data Link, Network, Transport, Application) sehingga Anda tahu komponen mana yang harus diisolasi.', 'bi-layers', 2, 'published'),
('home', 'feature_3', 'Step-by-Step Roadmap', 'Panduan terstruktur', 'Instruksi penanganan kronologis mulai dari pemeriksaan fisik, rute IP, DNS flush, hingga perbaikan stack TCP/IP dengan contoh output perintah terminal.', 'bi-signpost-split', 3, 'published'),
('home', 'feature_4', 'AI-Assisted Symptom Input', 'Natural Language Interpreter', 'Sampaikan keluhan Anda dalam kalimat bebas bahasa Indonesia. Natural Language Interpreter mengekstrak entitas teknis ke dalam format data terstruktur.', 'bi-robot', 4, 'published'),
('home', 'feature_5', 'Diagnosis History', 'Penyimpanan sesi lokal', 'Simpan seluruh riwayat analisis gangguan jaringan di browser tanpa perlu registrasi rumit, pantau rasio masalah yang terselesaikan secara berkala.', 'bi-clock-history', 5, 'published'),
('home', 'feature_6', 'Admin Managed Knowledge Base', 'Manajemen aturan & panduan', 'Administrator laboratorium dan teknisi NOC dapat memperbarui aturan inferensi, menambah gejala baru, dan mengunggah artikel panduan melalui CMS internal.', 'bi-journal-code', 6, 'published'),

-- How It Works (Header & 4 Steps)
('home', 'how_it_works_header', 'Bagaimana NetworkTrouble Bekerja', 'Alur Diagnostik', 'Alur sistematis 4 langkah untuk mengubah ketidakpastian gangguan menjadi rencana aksi perbaikan yang terarah.', 'bi-diagram-3', 1, 'published'),
('home', 'step_1', 'Describe Your Problem', 'Langkah 1', 'Pilih jenis perangkat Anda (PC, Laptop, Smartphone) dan tentukan jenis koneksi yang bermasalah (Kabel LAN atau WiFi).', '1', 1, 'published'),
('home', 'step_2', 'Analyze Symptoms', 'Langkah 2', 'Pilih gejala-gejala spesifik yang Anda amati, atau ketikkan keluhan dalam bahasa sehari-hari untuk dianalisis mesin inferensi.', '2', 2, 'published'),
('home', 'step_3', 'Identify Network Layer', 'Langkah 3', 'Sistem mengklasifikasikan masalah dan memetakannya langsung ke model referensi OSI (Physical, Data Link, Network, Transport, atau Application).', '3', 3, 'published'),
('home', 'step_4', 'Follow Troubleshooting Steps', 'Langkah 4', 'Jalankan panduan perbaikan langkah demi langkah disertai perintah CLI terverifikasi untuk memulihkan koneksi dengan cepat.', '4', 4, 'published'),

-- Target Audience
('home', 'audience_header', 'Dibuat untuk Praktisi & Pembelajar Jaringan', 'Audiens & Pengguna', 'Menghilangkan kebiasaan asal cabut kabel atau restart sembarangan dengan memperkenalkan metodologi penelusuran gangguan berstandar sertifikasi (CompTIA Network+, CCNA, MTCNA).', 'bi-people', 1, 'published'),

-- CTA Section
('home', 'cta_section', 'Siap Menganalisis Gangguan Jaringan Anda?', 'Mulai Diagnosis Sekarang', 'Hanya butuh 1-2 menit untuk menjawab wizard gejala dan mendapatkan diagnosa akurat dengan panduan perbaikan terverifikasi.', 'diagnose.php', 1, 'published')

ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `subtitle` = VALUES(`subtitle`),
  `content` = VALUES(`content`),
  `icon` = VALUES(`icon`),
  `display_order` = VALUES(`display_order`),
  `updated_at` = NOW();

-- 4. Ensure `site_settings` has all key fields
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'NetworkTrouble', 'text'),
('site_tagline', 'Understand Your Network. Solve Problems Smarter.', 'text'),
('site_description', 'Sistem diagnosis gangguan jaringan berbasis klasifikasi gejala, pemetaan OSI Layer, rule-based engine, dan AI-assisted symptom interpretation.', 'textarea'),
('meta_keywords', 'network troubleshooting, diagnosis jaringan, osi layer, ping, dns, wifi trouble, ip conflict', 'text'),
('hero_badge', 'Sistem Diagnosis Jaringan Aktif & Siap Uji', 'text'),
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
('footer_copyright', '© 2026 NetworkTrouble. All rights reserved. Built for professional network engineers and learners.', 'text'),
('contact_email', 'support@networktrouble.local', 'text'),
('contact_phone', '+62 812-3456-7890', 'text'),
('footer_text', 'Solusi diagnosis masalah jaringan praktis untuk teknisi dan pemula.', 'text'),
('allow_feedback', '1', 'text'),
('enable_ai_assist', '1', 'text')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 5. Seed default slider if any media asset exists and slider is empty
INSERT INTO `slider_items` (`media_id`, `title`, `caption`, `button_text`, `button_url`, `display_order`, `is_active`)
SELECT id, 'Diagnosis Gangguan Jaringan Cerdas', 'Analisis sistematis berbasis 7 Lapisan OSI Model & Inferensi Rule-Based', 'Mulai Sekarang', 'diagnose.php', 1, 1
FROM `media_assets`
WHERE NOT EXISTS (SELECT 1 FROM `slider_items`)
LIMIT 1;
