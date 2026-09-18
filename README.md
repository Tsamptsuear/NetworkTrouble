# NetworkTrouble 🌐🛠️
### Sistem Klasifikasi & Diagnosis Awal Gangguan Jaringan Komputer
*Implementasi Tahap 1 (Struktur Proyek & Frontend Dasar) dan Tahap 2 (Database MySQL & Demo Data)*

---

## 📌 Daftar Isi Dokumentasi
1. [Cara Membuat Database](#1-cara-membuat-database)
2. [Cara Import File SQL](#2-cara-import-file-sql)
3. [Cara Mengatur Koneksi Database](#3-cara-mengatur-koneksi-database)
4. [Cara Menjalankan Melalui XAMPP](#4-cara-menjalankan-melalui-xampp)
5. [URL Akses Website](#5-url-akses-website)
6. [Akun Admin Demo](#6-akun-admin-demo)
7. [Struktur Folder Proyek](#7-struktur-folder-proyek)
8. [Fitur yang Sudah Selesai (Tahap 1 & Tahap 2)](#8-fitur-yang-sudah-selesai-tahap-1--tahap-2)
9. [Fitur yang Belum Dibuat pada Tahap Ini (Rencana Tahap Berikutnya)](#9-fitur-yang-belum-dibuat-pada-tahap-ini)

---

## 1. Cara Membuat Database

1. Buka browser dan akses panel phpMyAdmin lokal di:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Pada panel navigasi sebelah kiri, klik menu **New** (atau **Basis data baru**).
3. Masukkan nama database:
   ```text
   networktrouble_db
   ```
4. Pilih collation / penyortiran:
   ```text
   utf8mb4_unicode_ci
   ```
5. Klik tombol **Create** (atau **Buat**).

> **Catatan:** Skrip file SQL `database/networktrouble_db.sql` juga telah dilengkapi instruksi `CREATE DATABASE IF NOT EXISTS networktrouble_db;`, sehingga database dapat dibuat secara otomatis saat file SQL dieksekusi.

---

## 2. Cara Import File SQL

File skema dan demo data tersimpan di direktori:
```text
/database/networktrouble_db.sql
```

### Langkah-langkah Import melalui phpMyAdmin:
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin/`).
2. Klik nama database **`networktrouble_db`** pada bilah menu sebelah kiri.
3. Klik tab **Import** pada menu navigasi bagian atas.
4. Pada opsi **File to import** (Berkas untuk diimpor), klik tombol **Choose File** / **Browse**.
5. Pilih file SQL dari folder proyek:
   ```text
   C:\xampp\htdocs\networktrouble\database\networktrouble_db.sql
   ```
6. Pastikan format terpilih adalah **SQL**.
7. Gulir ke bawah dan klik tombol **Import** (atau **Kirim / Go**).
8. Tunggu beberapa saat hingga muncul pesan konfirmasi sukses berwarna hijau:
   > *"Import has been successfully finished, queries executed."*

---

## 3. Cara Mengatur Koneksi Database

Konfigurasi koneksi database diatur pada file:
[`config/database.php`](file:///d:/BELAJAR%20WEBSITE/networktrouble/config/database.php)

File ini menggunakan driver **PDO (PHP Data Objects)** dengan penanganan error yang aman (tidak menampilkan pesan mentah database ke publik saat terjadi kegagalan):

```php
// Konfigurasi bawaan XAMPP:
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'networktrouble_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika menggunakan pengaturan default XAMPP
```

* Jika MySQL Anda menggunakan password khusus (misal `root` atau `12345`), cukup ubah konstanta `DB_PASS` pada file tersebut.
* Opsi PDO yang diaktifkan:
  * `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
  * `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
  * `PDO::ATTR_EMULATE_PREPARES => false`
  * `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"`

---

## 4. Cara Menjalankan Melalui XAMPP

1. **Pastikan Folder Berada di `htdocs`:**
   Salin atau buat symlink direktori proyek ke dalam folder `htdocs` XAMPP:
   ```text
   C:\xampp\htdocs\networktrouble
   ```
2. **Jalankan Service Apache dan MySQL:**
   - Buka **XAMPP Control Panel**.
   - Pada baris **Apache**, klik tombol **Start** (hingga lampu berwarna hijau, default port 80/443).
   - Pada baris **MySQL**, klik tombol **Start** (hingga lampu berwarna hijau, default port 3306).
3. **Buka Web Browser:**
   Gunakan browser modern seperti Google Chrome, Microsoft Edge, atau Mozilla Firefox.

---

## 5. URL Akses Website

Setelah Apache dan MySQL berjalan di XAMPP, buka alamat-alamat berikut:

| Halaman | URL Akses Lokal | Keterangan |
| :--- | :--- | :--- |
| **Homepage** | `http://localhost/networktrouble/` | Beranda modern dengan hero SVG, 6 feature cards, 4 langkah alur, dan statistik. |
| **Diagnose (Placeholder)** | `http://localhost/networktrouble/diagnose.php` | Antarmuka wizard diagnostik jaringan. |
| **Knowledge Base** | `http://localhost/networktrouble/knowledge.php` | Pusat edukasi dengan search bar, category pills, & 5 artikel contoh. |
| **About** | `http://localhost/networktrouble/about.php` | Penjelasan tujuan, masalah, manfaat, klasifikasi, OSI layer, & basis pengetahuan. |
| **Contact** | `http://localhost/networktrouble/contact.php` | Informasi operasional NOC, alamat, jam operasional, email, telepon, dan form kontak. |
| **Admin Login** | `http://localhost/networktrouble/admin/login.php` | Halaman otentikasi login administrator. |

---

## 6. Akun Admin Demo

Demo data akun administrator telah di-generate menggunakan enkripsi `password_hash()` bawaan PHP dengan algoritma standard industri **Bcrypt**:

* **Username:** `admin`
* **Password:** `Admin@12345`
* **Role:** `super_admin`
* **Status:** `active`

> Hash bcrypt tersimpan di database: `$2y$10$zgahZo5HJkTgF8jXf2HxQO8g.vP97Zwzu4e5Mx6ta9bA8ivPtMLlW`.

---

## 7. Struktur Folder Proyek

```text
networktrouble/
├── admin/                     # Dashboard & antarmuka administrator
│   ├── includes/              # Template parsial admin (header, footer, sidebar)
│   ├── activity-logs.php      # Audit log aktivitas
│   ├── admins.php             # Kelola admin & RBAC
│   ├── ai-settings.php        # Konfigurasi penyedia AI
│   ├── categories.php         # Manajemen kategori jaringan
│   ├── contact.php            # Manajemen informasi kontak NOC
│   ├── content.php            # Manajemen konten halaman CMS
│   ├── index.php              # Dashboard utama admin
│   ├── login.php              # Halaman login administrator
│   ├── logout.php             # Logout session handler
│   ├── media.php              # Pengelola file media & gambar
│   ├── reports.php            # Laporan rekapitulasi sesi
│   ├── rules.php              # Manajemen aturan klasifikasi
│   ├── settings.php           # Pengaturan umum website
│   ├── symptoms.php           # Manajemen gejala & bobot skor
│   ├── troubleshooting.php    # Manajemen panduan & katalog CLI
│   └── unresolved.php         # Triage keluhan belum terpecahkan
├── api/                       # API endpoint modular
│   ├── ai-interpret.php       # Modul interpreter gejala
│   ├── classify.php           # Modul eksekusi klasifikasi
│   └── save-diagnosis.php     # Penyimpanan umpan balik diagnosis
├── assets/                    # Aset statis frontend
│   ├── css/
│   │   ├── admin.css          # Styling dashboard admin
│   │   └── style.css          # Styling kustom modern SaaS platform
│   ├── js/
│   │   ├── diagnosis-wizard.js # Logika wizard diagnosis
│   │   └── main.js            # Skrip interaktivitas UI
│   └── images/                # File gambar dan ikon lokal
├── config/                    # Konfigurasi inti
│   ├── ai.php                 # Driver integrasi AI
│   ├── app.php                # Konstanta aplikasi, URL base, & CSRF
│   └── database.php           # Koneksi PDO MySQL aman
├── database/                  # Skema database & seeder
│   └── networktrouble_db.sql  # Skrip SQL lengkap (15 tabel + demo data)
├── includes/                  # Komponen publik reusable
│   ├── auth.php               # Middleware autentikasi
│   ├── classification_engine.php # Inti mesin inferensi rule-based
│   ├── footer.php             # Komponen footer publik
│   ├── functions.php          # Utilitas global, format, & sanitasi
│   ├── header.php             # Komponen header HTML & meta tags
│   └── navbar.php             # Komponen navigasi atas publik
├── uploads/                   # Direktori berkas media unggahan
├── about.php                  # Halaman Tentang NetworkTrouble
├── contact.php                # Halaman Kontak & Form Dukungan
├── diagnose.php               # Halaman Wizard Diagnosis (Placeholder)
├── history.php                # Halaman Pelacakan Riwayat Sesi
├── how-it-works.php           # Halaman Penjelasan Alur & Model OSI
├── index.php                  # Halaman Beranda (Homepage)
├── knowledge.php              # Pusat Edukasi (Knowledge Base)
├── result.php                 # Halaman Hasil Diagnostik
├── troubleshooting.php        # Halaman Panduan Manual Mandiri
└── README.md                  # Dokumentasi resmi proyek
```

---

## 8. Fitur yang Sudah Selesai (Tahap 1, Tahap 2, & Tahap 3)

### ✅ Tahap 1: Struktur Proyek & Frontend Dasar
1. **Arsitektur Modular PHP Native:**
   - Penyusunan layout menggunakan sistem `includes/header.php`, `includes/navbar.php`, `includes/footer.php`, dan `includes/functions.php`.
   - Deteksi otomatis path base URL dan penanganan CSRF token di `config/app.php`.
2. **Desain Visual SaaS Modern:**
   - Palet warna profesional: Dark Indigo/Navy (`#0f172a`), Tech Blue (`#2563eb`), Slate Gray, soft shadows, rounded corners, dan white space seimbang.
   - Tipografi modern menggunakan font Google (*Inter*).
   - Seluruh styling kustom terorganisir rapi di `assets/css/style.css` didukung Bootstrap 5 dan Bootstrap Icons.
3. **Homepage Lengkap (`index.php`):**
   - **Hero Section:** Judul dan subjudul sesuai spesifikasi, tombol CTA ganda, dan ilustrasi topologi jaringan interaktif berbasis SVG/HTML murni.
   - **Real-Time Stats Bar:** Menampilkan jumlah gejala, kategori OSI, panduan troubleshooting, dan sesi diagnosis (otomatis mengambil dari database dengan fallback dinamis).
   - **6 Feature Cards:** Smart Classification, OSI Layer Mapping, Step-by-Step Roadmap, AI-Assisted Symptom Input, Diagnosis History, dan Admin Managed Knowledge Base.
   - **How It Works (4 Langkah):** Describe Your Problem, Analyze Symptoms, Identify Network Layer, dan Follow Troubleshooting Steps.
   - **Target Audience Section:** Penjelasan manfaat bagi mahasiswa TI, siswa SMK TKJ, laboran, dan teknisi pemula.
   - **CTA Section & Footer:** Ajakan bertindak menuju `diagnose.php`, informasi kontak dinamis, dan navigasi lengkap.
4. **Halaman About (`about.php`):**
   - Menjelaskan secara tuntas 6 poin spesifikasi: (1) Tujuan aplikasi, (2) Masalah yang diselesaikan, (3) Manfaat bagi mahasiswa dan teknisi pemula, (4) Sistem klasifikasi inferensi berbobot, (5) Pemetaan 9 kategori dalam model OSI, dan (6) Troubleshooting berbasis pengetahuan.
5. **Halaman Contact (`contact.php`):**
   - Menampilkan placeholder identitas NOC, email, nomor telepon, alamat laboratorium, jam operasional, tombol direct WhatsApp, dan formulir kontak sederhana.
6. **Halaman Knowledge Base (`knowledge.php`):**
   - Dilengkapi search bar placeholder, filter kategori (OSI Model, DNS, DHCP, Ping Tools, Performance), dan 5 artikel contoh lengkap:
     1. *Mengenal OSI Layer dalam Troubleshooting Jaringan*
     2. *Apa itu DNS? Panduan Lengkap dan Cara Kerjanya*
     3. *Apa itu DHCP dan Misteri IP 169.254.x.x (APIPA)*
     4. *Cara Menggunakan Perintah Ping untuk Uji Jaringan*
     5. *Penyebab Internet Lambat dan Cara Mengatasinya*
   - Tersedia tampilan baca detail artikel (*single article view*) dengan navigasi breadcrumb.

### ✅ Tahap 2: Database MySQL & Data Demo
1. **Database `networktrouble_db` Berisi 15 Tabel Terstruktur Normal:**
   `admins`, `site_settings`, `page_contents`, `media_assets`, `contact_settings`, `network_categories`, `network_symptoms`, `symptom_keywords`, `classification_rules`, `troubleshooting_steps`, `network_commands`, `knowledge_articles`, `diagnosis_sessions`, `diagnosis_results`, `unresolved_cases`, `ai_settings`, `activity_logs`.
2. **Demo Data Lengkap & Valid:**
   - Akun Super Admin dengan password hash Bcrypt valid untuk `Admin@12345`.
   - 9 Kategori Jaringan (Physical, Data Link, Network, Transport, Application, Wireless, Performance, Security, Unknown).
   - 20 Gejala Jaringan berbobot prioritas.
   - 38 Kata Kunci Gejala.
   - 20 Aturan Klasifikasi logika JSON.
   - 29 Langkah Troubleshooting lintas platform (Windows/Linux/GUI).
   - Data pengaturan situs dan kontak bawaan.
3. **Koneksi PDO Aman (`config/database.php`):**
   - Mengaktifkan `ERRMODE_EXCEPTION`, `utf8mb4`, dan proteksi pesan error mentah ke publik.

### ✅ Tahap 3: Halaman Diagnosis Interaktif & Rule-Based Classification Engine
1. **Wizard Diagnosis Interaktif (`diagnose.php` & `diagnosis-wizard.js`):**
   - **Langkah 1 (Media):** Pilihan tipe koneksi (WiFi, LAN Ethernet, Fiber ONT, Hotspot, Unknown) dengan kartu aktif.
   - **Langkah 2 (Gejala):** Filter kategori pil dan pencarian langsung (*live search*) terhadap 20 gejala jaringan.
   - **Langkah 3 (Adaptif):** Pertanyaan lanjutan dinamis sesuai media (indikator LAN LED untuk kabel, status SSID untuk WiFi, dan uji ping IP publik vs domain).
   - **Langkah 4 (Keluhan Bebas):** Input teks bebas dengan *Live Keyword Detection Preview* murni berbasis aturan kamus kata kunci lokal.
2. **Mesin Klasifikasi Berbasis Aturan (`includes/classification_engine.php`):**
   - Perhitungan bobot gejala, bias tipe media, uji adaptif, dan evaluasi kondisi aturan majemuk (`classification_rules`).
   - Normalisasi persentase keyakinan (*Confidence Score* 0 - 100%).
   - Pemetaan lapisan OSI otomatis dan identifikasi akar masalah (*Possible Causes*).
3. **Laporan Dossier Diagnostik Lengkap (`result.php`):**
   - Ringkasan kategori, badge lapisan OSI, tingkat keparahan (*Severity*), dan meteran keyakinan (*Confidence Meter*).
   - Daftar gejala terdeteksi dan aturan klasifikasi yang terpicu.
   - Roadmap troubleshooting bertahap dengan *checkbox* interaktif langkah yang telah dicoba.
   - *Network Command Toolkit* dengan tombol salin satu-klik untuk platform Windows dan Linux.
   - Umpan balik resolusi interaktif (*Resolved* vs *Unresolved*) yang memperbarui database via AJAX `api/save-diagnosis.php`.
4. **Endpoint RESTful API & Riwayat Sesi:**
   - `api/classify.php`: Endpoint inferensi gejala berbasis JSON/POST.
   - `api/save-diagnosis.php`: Penyimpan status resolusi dan pencatatan otomatis ke `unresolved_cases`.
   - `history.php`: Halaman riwayat sesi diagnosis pengguna.

---

## 9. Fitur yang Belum Dibuat pada Tahap Ini (Rencana Tahap 4)

Fitur-fitur berikut direncanakan untuk dikembangkan pada tahap selanjutnya:

* **Tahap 4 — Modul AI Eksternal & Dashboard Admin CMS:**
  - Integrasi API AI pihak ketiga (OpenAI / Gemini API) sebagai interpreter opsional untuk keluhan bahasa alami tingkat lanjut.
  - CRUD Admin Panel lengkap untuk manajemen Kategori, Gejala, Aturan Inferensi, dan Langkah Panduan (`admin/`).
  - Grafik tren dan visualisasi analitik diagnosis menggunakan Chart.js di `admin/index.php`.
  - Triage kasus belum terpecahkan (*Convert to Keyword*) di `admin/unresolved.php`.
  - Media manager untuk pengelolaan berkas unggahan gambar topologi dan artikel edukasi.
  - Sistem pencatatan umpan balik resolusi (*Resolved* vs *Unresolved*).
  - Integrasi modul NLP / AI Interpreter API untuk masukan bahasa alami.
* **Tahap 4 — Dashboard Admin CMS & Analisis Triage:**
  - Fitur CRUD lengkap untuk Kategori, Gejala, Aturan Inferensi, dan Langkah Panduan via panel admin.
  - Grafik visualisasi metrik dan tren diagnosis menggunakan Chart.js di `admin/index.php`.
  - Modul Triage Kasus Belum Terpecahkan (*Convert to Keyword*) di `admin/unresolved.php`.
  - Pengelolaan media gambar, artikel knowledge base, dan konfigurasi API key AI melalui antarmuka admin.
