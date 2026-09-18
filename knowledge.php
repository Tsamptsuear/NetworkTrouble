<?php
/**
 * NetworkTrouble - Knowledge Base Page
 * Tampilan awal pusat pengetahuan jaringan dengan search bar, filter kategori,
 * dan 5 artikel contoh terstruktur (Mengenal OSI Layer, Apa itu DNS?, Apa itu DHCP?,
 * Cara menggunakan perintah ping, Penyebab internet lambat).
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Pusat Pengetahuan Jaringan (Knowledge Base)";
$pageDesc = "Kumpulan artikel edukatif, panduan protokol, dan analisis cara kerja teknologi jaringan komputer.";

$db = get_db();
$slug = sanitize($_GET['slug'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$categoryFilter = sanitize($_GET['cat'] ?? 'all');
$article = null;
$articlesList = [];

// Fallback demo articles matching the exact 5 specifications
$fallbackArticles = [
    [
        'id' => 1,
        'title' => 'Mengenal OSI Layer dalam Troubleshooting Jaringan',
        'slug' => 'mengenal-osi-layer',
        'summary' => 'Panduan memahami 7 lapisan Open Systems Interconnection (OSI) dan cara menggunakannya untuk mengisolasi kerusakan konektivitas secara bertahap.',
        'category_name' => 'Physical / Data Link',
        'category_slug' => 'osi',
        'read_time' => '6 min read',
        'views' => 245,
        'created_at' => '2026-03-01 10:00:00',
        'content' => '
            <h3>Mengapa OSI Model Sangat Penting untuk Teknisi?</h3>
            <p>Model OSI (Open Systems Interconnection) yang diperkenalkan oleh ISO adalah kerangka kerja konseptual yang membagi proses komunikasi jaringan menjadi 7 lapisan terpisah. Dalam dunia nyata, teknisi jaringan profesional menggunakan model ini sebagai panduan mental untuk melakukan troubleshooting dengan metode <em>Bottom-Up</em>.</p>
            <h4>Metode Troubleshooting Bottom-Up (Layer 1 ke Layer 7):</h4>
            <ol>
                <li><strong>Layer 1 (Physical):</strong> Pastikan kabel LAN tercolok erat, lampu port menyala, atau adaptor WiFi aktif.</li>
                <li><strong>Layer 2 (Data Link):</strong> Periksa link speed/duplex negotiation, status port switch, dan tabel MAC address.</li>
                <li><strong>Layer 3 (Network):</strong> Verifikasi konfigurasi IP address, subnet mask, dan jalur default gateway dengan <code>ipconfig</code> atau <code>ping</code>.</li>
                <li><strong>Layer 4 (Transport):</strong> Pastikan port TCP/UDP tidak terblokir firewall lokal atau router dengan <code>netstat</code> atau <code>Test-NetConnection</code>.</li>
                <li><strong>Layer 7 (Application):</strong> Periksa DNS resolver, service daemon web/email, serta status sertifikat SSL.</li>
            </ol>
            <div class="alert alert-info border-0 rounded-3">
                <i class="bi bi-lightbulb-fill me-1"></i> <strong>Tip Teknisi:</strong> 70% masalah jaringan skala kantor bermula dari Layer 1 (kabel lepas/rusak) dan Layer 3 (kegagalan sewa DHCP / IP conflict).
            </div>
        '
    ],
    [
        'id' => 2,
        'title' => 'Apa itu DNS? Panduan Lengkap dan Cara Kerjanya',
        'slug' => 'apa-itu-dns',
        'summary' => 'Mengapa Anda bisa ping ke IP 8.8.8.8 tetapi tidak bisa membuka website? Pelajari cara kerja Domain Name System dan solusi kendala resolver.',
        'category_name' => 'Application Layer',
        'category_slug' => 'dns',
        'read_time' => '5 min read',
        'views' => 198,
        'created_at' => '2026-03-05 11:30:00',
        'content' => '
            <h3>DNS: Buku Telepon Global Internet</h3>
            <p>Komputer dan router berkomunikasi menggunakan alamat biner yang direpresentasikan dalam angka IP address (seperti <code>142.250.190.46</code>). Namun manusia lebih mudah mengingat nama seperti <code>google.com</code>. DNS bertugas memetakan nama domain ke alamat IP numerik tersebut.</p>
            <h4>Gejala Khas Gangguan DNS:</h4>
            <ul>
                <li>Aplikasi berbasis IP langsung (seperti WhatsApp atau game online) tetap berfungsi normal, tetapi browser gagal membuka website apapun.</li>
                <li>Pesan kesalahan browser: <code>DNS_PROBE_FINISHED_NXDOMAIN</code> atau <code>ERR_NAME_NOT_RESOLVED</code>.</li>
                <li>Uji ping ke <code>8.8.8.8</code> sukses, namun ping ke <code>google.com</code> memunculkan <em>"Could not find host"</em>.</li>
            </ul>
            <h4>Langkah Penanganan Cepat:</h4>
            <p>1. Bersihkan cache resolver lokal pada Windows via Command Prompt:</p>
            <pre class="bg-dark text-white p-3 rounded font-monospace">ipconfig /flushdns</pre>
            <p>2. Uji resolusi domain menggunakan tool <code>nslookup</code>:</p>
            <pre class="bg-dark text-white p-3 rounded font-monospace">nslookup google.com 8.8.8.8</pre>
            <p>3. Ganti DNS adapter Anda ke resolver publik yang cepat dan aman seperti Cloudflare (<code>1.1.1.1</code>) atau Google (<code>8.8.8.8</code>).</p>
        '
    ],
    [
        'id' => 3,
        'title' => 'Apa itu DHCP dan Misteri IP 169.254.x.x (APIPA)',
        'slug' => 'apa-itu-dhcp',
        'summary' => 'Bagaimana perangkat Anda mendapatkan konfigurasi IP secara otomatis dan apa yang harus dilakukan ketika terjebak pada alamat APIPA.',
        'category_name' => 'Network Layer',
        'category_slug' => 'dhcp',
        'read_time' => '4 min read',
        'views' => 167,
        'created_at' => '2026-03-08 09:15:00',
        'content' => '
            <h3>Prinsip Kerja Protokol DHCP (Proses D.O.R.A)</h3>
            <p>DHCP (Dynamic Host Configuration Protocol) memungkinkan perangkat klien memperoleh IP address, subnet mask, default gateway, dan DNS server secara otomatis tanpa konfigurasi manual satu per satu.</p>
            <h4>Empat Langkah DORA:</h4>
            <ol>
                <li><strong>Discover:</strong> Klien menyiarkan (broadcast) permintaan IP ke seluruh jaringan lokal.</li>
                <li><strong>Offer:</strong> Server DHCP yang mendengar menawarkan alamat IP yang tersedia.</li>
                <li><strong>Request:</strong> Klien mengonfirmasi persetujuan menerima penawaran tersebut.</li>
                <li><strong>Acknowledge:</strong> Server mencatat sewa (lease) dan memberikan parameter konfigurasi penuh.</li>
            </ol>
            <h4>Mengapa Laptop Mendapatkan IP 169.254.x.x?</h4>
            <p>Alamat dalam rentang <code>169.254.0.1 s/d 169.254.255.254</code> disebut <strong>APIPA (Automatic Private IP Addressing)</strong>. Ini adalah mekanisme proteksi sistem operasi Windows saat permintaan DHCP Discover tidak mendapat balasan apapun dari router lokal.</p>
            <p><strong>Solusi:</strong> Jalankan perintah release dan renew sewa DHCP:</p>
            <pre class="bg-dark text-white p-3 rounded font-monospace">ipconfig /release
ipconfig /renew</pre>
        '
    ],
    [
        'id' => 4,
        'title' => 'Cara Menggunakan Perintah Ping untuk Uji Jaringan',
        'slug' => 'cara-menggunakan-perintah-ping',
        'summary' => 'Panduan komprehensif membaca parameter bytes, round-trip time, packet loss, dan TTL untuk mendiagnosis kualitas sambungan.',
        'category_name' => 'Network / Performance',
        'category_slug' => 'ping',
        'read_time' => '5 min read',
        'views' => 210,
        'created_at' => '2026-03-10 14:20:00',
        'content' => '
            <h3>Mengenal Utilitas ICMP Ping</h3>
            <p>Perintah <code>ping</code> bekerja dengan mengirimkan paket <em>ICMP Echo Request</em> ke alamat tujuan dan menunggu <em>Echo Reply</em>. Utilitas ini adalah instrumen nomor satu bagi teknisi jaringan untuk menguji ketersediaan host dan kualitas sambungan.</p>
            <h4>Membedah Parameter Hasil Ping Windows:</h4>
            <div class="p-3 bg-dark text-white rounded font-monospace mb-3">
                Pinging 8.8.8.8 with 32 bytes of data:<br>
                Reply from 8.8.8.8: bytes=32 time=14ms TTL=118
            </div>
            <ul>
                <li><strong>Bytes:</strong> Ukuran payload ICMP yang dikirim (default Windows: 32 bytes).</li>
                <li><strong>Time (Latency):</strong> Waktu bolak-balik (Round Trip Time). Di bawah 30ms tergolong sangat prima untuk koneksi fiber optik.</li>
                <li><strong>TTL (Time to Live):</strong> Sisa hop router sebelum paket dibuang. Nilai awal default Windows = 128, Linux = 64, Cisco = 255.</li>
            </ul>
            <h4>Diagnostik Status Pesan Kesalahan:</h4>
            <ul>
                <li><strong>Request Timed Out (RTO):</strong> Paket terputus di tengah jalan atau host tujuan memblokir protokol ICMP via firewall.</li>
                <li><strong>Destination Host Unreachable:</strong> Router gateway lokal Anda tidak mengetahui rute ke subnet tujuan.</li>
            </ul>
        '
    ],
    [
        'id' => 5,
        'title' => 'Penyebab Internet Lambat dan Cara Mengatasinya',
        'slug' => 'penyebab-internet-lambat',
        'summary' => 'Kenali faktor latensi tinggi, packet loss, interferensi WiFi, dan background congestion yang membuat koneksi terasa sangat lambat.',
        'category_name' => 'Performance / QoS',
        'category_slug' => 'performance',
        'read_time' => '6 min read',
        'views' => 280,
        'created_at' => '2026-03-12 16:45:00',
        'content' => '
            <h3>Memahami Perbedaan Bandwidth vs Latensi</h3>
            <p>Seringkali pengguna mengeluhkan "internet lambat" padahal paket langganan bandwidth-nya besar (100 Mbps). Lambat yang dirasakan saat browsing atau video conference biasanya bukan karena kapasitas bandwidth yang habis, melainkan <strong>latensi tinggi (high ping)</strong> dan <strong>packet loss</strong>.</p>
            <h4>5 Penyebab Paling Sering:</h4>
            <ol>
                <li><strong>Interferensi Sinyal WiFi:</strong> Kanal frekuensi 2.4 GHz yang terlalu padat oleh access point tetangga menyebabkan tabrakan frame transmisi.</li>
                <li><strong>Aplikasi Latar Belakang (Background Updates):</strong> Windows Update, sinkronisasi cloud (Google Drive/OneDrive), atau aplikasi torrent yang menyerap upload bandwidth secara maksimal.</li>
                <li><strong>Bufferbloat pada Router:</strong> Antrian paket pada router murah yang tidak menerapkan algoritma Smart Queue Management (SQM).</li>
                <li><strong>Kabel LAN Kategori Rendah / Pin Rusak:</strong> Kabel Cat5 lama yang mengalami degradasi dapat menurunkan link speed dari 1 Gbps ke 100 Mbps atau 10 Mbps.</li>
                <li><strong>Server DNS Lambat:</strong> Kueri domain butuh 500ms setiap kali membuka website baru.</li>
            </ol>
            <h4>Tindakan Perbaikan Rekomendasi:</h4>
            <p>Uji jalur hop jaringan menggunakan perintah <code>tracert</code> untuk melihat di hop router mana terjadi lonjakan ms:</p>
            <pre class="bg-dark text-white p-3 rounded font-monospace">tracert -d 8.8.8.8</pre>
        '
    ]
];

// Ambil artikel dari database jika tabel tersedia, gabungkan atau gunakan fallback
if ($db) {
    if (!empty($slug)) {
        try {
            $stmt = $db->prepare("SELECT a.*, c.name as category_name, c.slug as category_slug FROM knowledge_articles a LEFT JOIN network_categories c ON a.category_id = c.id WHERE a.slug = ? AND a.status = 'published'");
            $stmt->execute([$slug]);
            $article = $stmt->fetch();
            if ($article) {
                $db->prepare("UPDATE knowledge_articles SET views = views + 1 WHERE id = ?")->execute([$article['id']]);
                $pageTitle = $article['title'];
            }
        } catch (Exception $e) {}
    }

    if (!$article) {
        try {
            $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug FROM knowledge_articles a LEFT JOIN network_categories c ON a.category_id = c.id WHERE a.status = 'published'";
            $params = [];
            if (!empty($search)) {
                $sql .= " AND (a.title LIKE ? OR a.summary LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($categoryFilter !== 'all') {
                $sql .= " AND (c.slug = ? OR a.category_id = ?)";
                $params[] = $categoryFilter;
                $params[] = is_numeric($categoryFilter) ? (int)$categoryFilter : 0;
            }
            $sql .= " ORDER BY a.id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $articlesList = $stmt->fetchAll();
        } catch (Exception $e) {}
    }
}

// Fallback search / slug matching jika DB tidak mengembalikan artikel
if (!$article && !empty($slug)) {
    foreach ($fallbackArticles as $fb) {
        if ($fb['slug'] === $slug) {
            $article = $fb;
            $pageTitle = $article['title'];
            break;
        }
    }
}

if (empty($articlesList) && !$article) {
    $articlesList = $fallbackArticles;
    if (!empty($search)) {
        $articlesList = array_filter($articlesList, function($item) use ($search) {
            return stripos($item['title'], $search) !== false || stripos($item['summary'], $search) !== false;
        });
    }
    if ($categoryFilter !== 'all') {
        $articlesList = array_filter($articlesList, function($item) use ($categoryFilter) {
            return isset($item['category_slug']) && $item['category_slug'] === $categoryFilter;
        });
    }
}

// Daftar kategori untuk filter bar
$categories = [
    'all' => 'Semua Topik',
    'osi' => 'OSI Model',
    'dns' => 'DNS & Name Resolution',
    'dhcp' => 'DHCP & IP Addressing',
    'ping' => 'Ping & Tracert Tools',
    'performance' => 'Performance & Latency'
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<?php if ($article): ?>
<!-- TAMPILAN DETAIL ARTIKEL KNOWLEDGE -->
<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= base_url(); ?>" class="text-decoration-none text-primary">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('knowledge.php'); ?>" class="text-decoration-none text-primary">Knowledge Base</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($article['title']); ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-2"><?= htmlspecialchars($article['title']); ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
            <span><i class="bi bi-calendar3 me-1"></i><?= format_date($article['created_at'], 'd M Y'); ?></span>
            <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($article['read_time'] ?? '5 min read'); ?></span>
            <span><i class="bi bi-eye me-1"></i><?= number_format($article['views'] ?? 0); ?> kali dibaca</span>
            <?php if (!empty($article['category_name'])): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($article['category_name']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="nt-card p-4 p-md-5 bg-white border">
                    <div class="lead text-secondary mb-4 pb-3 border-bottom fs-6 fw-normal" style="line-height: 1.7;">
                        <?= htmlspecialchars($article['summary']); ?>
                    </div>
                    <div class="article-content" style="line-height: 1.8;">
                        <?= $article['content']; ?>
                    </div>
                    
                    <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <a href="<?= base_url('knowledge.php'); ?>" class="btn btn-nt-outline btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Artikel
                        </a>
                        <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary btn-sm">
                            <i class="bi bi-cpu me-1"></i> Uji Diagnosis Jaringan
                        </a>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR ARTIKEL -->
            <div class="col-lg-4">
                <div class="nt-card p-4 mb-4 bg-light border">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-cpu text-primary me-2"></i>Pusat Diagnosis</h5>
                    <p class="text-secondary small mb-3">
                        Sedang mengalami koneksi lambat, DNS error, atau kabel LAN tidak terdeteksi? Jalankan wizard investigasi terstruktur sekarang.
                    </p>
                    <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary btn-sm w-100">
                        <i class="bi bi-play-circle-fill me-1"></i> Buka Diagnostic Wizard
                    </a>
                </div>

                <div class="nt-card p-4 bg-white border">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-collection text-primary me-2"></i>Artikel Terkait</h6>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0 small">
                        <?php foreach ($fallbackArticles as $relArt): ?>
                            <?php if ($relArt['slug'] !== $article['slug']): ?>
                                <li class="pb-2 border-bottom">
                                    <a href="<?= base_url('knowledge.php?slug=' . $relArt['slug']); ?>" class="text-dark fw-semibold text-decoration-none hover-primary d-block mb-1">
                                        <?= htmlspecialchars($relArt['title']); ?>
                                    </a>
                                    <span class="text-muted small"><?= $relArt['read_time']; ?> &bull; <?= htmlspecialchars($relArt['category_name']); ?></span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- TAMPILAN DIREKTORI KNOWLEDGE BASE (SEARCH & FILTER) -->
<div class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-book me-1"></i> Pusat Edukasi & Teori Jaringan
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Network Knowledge Base</h1>
                <p class="text-secondary mb-4">
                    Kumpulan panduan protokol, arti perintah CLI, dan analisis teknis untuk memperdalam pemahaman praktis Anda dalam menyelesaikan insiden jaringan.
                </p>

                <!-- Search Bar Placeholder -->
                <form action="<?= base_url('knowledge.php'); ?>" method="GET" class="row justify-content-center g-2 mb-3">
                    <?php if ($categoryFilter !== 'all'): ?>
                        <input type="hidden" name="cat" value="<?= htmlspecialchars($categoryFilter); ?>">
                    <?php endif; ?>
                    <div class="col-md-8 col-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari topik (misal: OSI, DNS, DHCP, Ping, Internet Lambat)..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3 col-3">
                        <button type="submit" class="btn btn-nt-primary w-100">
                            <i class="bi bi-search me-1 d-none d-sm-inline"></i> Cari
                        </button>
                    </div>
                </form>

                <!-- Category Filter Pills Placeholder -->
                <div class="d-flex flex-wrap justify-content-center gap-2 pt-2">
                    <?php foreach ($categories as $key => $name): ?>
                        <a href="<?= base_url('knowledge.php?' . http_build_query(array_merge($_GET, ['cat' => $key]))); ?>" 
                           class="badge <?= ($categoryFilter === $key) ? 'bg-primary text-white' : 'bg-white text-secondary border'; ?> text-decoration-none px-3 py-2 rounded-pill small">
                            <?= htmlspecialchars($name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- DAFTAR KARTU ARTIKEL KNOWLEDGE -->
<div class="py-5">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">Artikel Tersedia</h4>
                <p class="text-muted small mb-0">Menampilkan <?= count($articlesList); ?> materi referensi jaringan terstruktur.</p>
            </div>
            <?php if (!empty($search) || $categoryFilter !== 'all'): ?>
                <a href="<?= base_url('knowledge.php'); ?>" class="btn btn-nt-outline btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($articlesList)): ?>
            <div class="row g-4">
                <?php foreach ($articlesList as $art): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="nt-card card-hover p-4 h-100 d-flex flex-column justify-content-between bg-white border">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-light text-secondary border font-monospace small">
                                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($art['read_time'] ?? '5 min read'); ?>
                                </span>
                                <span class="small text-muted">
                                    <i class="bi bi-eye me-1"></i><?= number_format($art['views'] ?? 0); ?>
                                </span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.15rem; line-height: 1.4;">
                                <a href="<?= base_url('knowledge.php?slug=' . $art['slug']); ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= htmlspecialchars($art['title']); ?>
                                </a>
                            </h5>
                            <p class="text-secondary small mb-3">
                                <?= htmlspecialchars(truncate_text($art['summary'], 120)); ?>
                            </p>
                        </div>
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <span class="badge bg-primary-subtle text-primary small">
                                <?= htmlspecialchars($art['category_name'] ?? 'Networking'); ?>
                            </span>
                            <a href="<?= base_url('knowledge.php?slug=' . $art['slug']); ?>" class="small fw-semibold text-primary text-decoration-none">
                                Baca Panduan <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="nt-card p-5 text-center bg-white border">
                <div class="nt-icon-box nt-icon-primary mx-auto mb-3">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Tidak Ada Artikel Ditemukan</h5>
                <p class="text-secondary small mb-4">Topik yang Anda cari belum tersedia dalam indeks pengetahuan kami.</p>
                <a href="<?= base_url('knowledge.php'); ?>" class="btn btn-nt-outline btn-sm">Tampilkan Semua Artikel</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
