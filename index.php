<?php
session_start();
require 'db_connect.php';

$layanan_list  = $pdo->query("SELECT * FROM layanan ORDER BY harga ASC")->fetchAll();
$total_layanan = count($layanan_list);
$total_mekanik = $pdo->query("SELECT COUNT(*) FROM users WHERE role='Mekanik'")->fetchColumn();
$total_booking = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status_pengerjaan='Selesai'")->fetchColumn();

// Sparepart untuk section ketersediaan
$spareparts_list = $pdo->query("SELECT * FROM spareparts ORDER BY kategori, nama_barang")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoPro Workshop - Bengkel Terpercaya</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
</head>
<body class="page-index">

<?php
$navbar_scrolled = false;
$navbar_links    = true;
require __DIR__ . '/partials/navbar.php';
?>

<!-- ── HERO ── -->
<section class="hero" id="home">
    <div class="hero-content">
        <div class="hero-badge">⚡ Layanan Servis Profesional</div>
        <h1>Bengkel Terpercaya<br>untuk <span>Kendaraan Anda</span></h1>
        <p>Sistem manajemen bengkel digital dengan teknisi bersertifikasi, booking online mudah, dan pemantauan status servis secara real-time. Kendaraan Anda, prioritas kami.</p>
        <div class="hero-actions">
            <a href="<?php echo isset($_SESSION['role']) ? 'booking.php' : 'register.php'; ?>" class="btn-hero">
                🔧 Booking Servis Sekarang
            </a>
            <a href="#layanan" class="btn-hero-ghost">Lihat Layanan</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <strong><?php echo $total_booking > 0 ? $total_booking.'+' : '500+'; ?></strong>
                <span>Servis Selesai</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <strong><?php echo $total_mekanik > 0 ? $total_mekanik : '10+'; ?></strong>
                <span>Teknisi Ahli</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <strong><?php echo $total_layanan > 0 ? $total_layanan : '15+'; ?></strong>
                <span>Jenis Layanan</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <strong>4.9★</strong>
                <span>Rating Pelanggan</span>
            </div>
        </div>
    </div>
</section>

<!-- ── LAYANAN ── -->
<section class="section-layanan" id="layanan">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag">Layanan Kami</span>
            <h2>Jasa Servis Lengkap & Transparan</h2>
            <p>Semua kebutuhan servis kendaraan Anda tersedia dengan harga yang jelas dan terjangkau.</p>
        </div>
        <div class="layanan-grid">
            <?php
            $icons = ['⚙️','🔧','🛞','❄️','🔩','🛡️','⚡','🪛','🔋','💧'];
            foreach ($layanan_list as $i => $l):
            ?>
            <div class="layanan-card">
                <div class="layanan-icon"><?php echo $icons[$i % count($icons)]; ?></div>
                <h3><?php echo htmlspecialchars($l['nama_servis']); ?></h3>
                <p><?php echo htmlspecialchars($l['deskripsi'] ?? 'Layanan profesional oleh teknisi berpengalaman dan bersertifikasi.'); ?></p>
                <div class="layanan-price">
                    Mulai <strong>Rp <?php echo number_format($l['harga'], 0, ',', '.'); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:40px;">
            <a href="<?php echo isset($_SESSION['role']) ? 'booking.php' : 'register.php'; ?>" class="btn-hero">
                Booking Layanan Sekarang
            </a>
        </div>
    </div>
</section>

<!-- ── SPAREPART ── -->
<section class="section-sparepart" id="sparepart">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag">Ketersediaan Stok</span>
            <h2>Sparepart Tersedia di Bengkel</h2>
            <p>Cek ketersediaan suku cadang secara langsung. Stok selalu diperbarui setiap transaksi.</p>
        </div>

        <!-- Grid Sparepart -->
        <div class="sparepart-grid" id="sparepart-grid">
            <?php foreach ($spareparts_list as $p):
                $stok      = (int)$p['stok'];
                $stok_class = $stok === 0 ? 'stok-habis' : ($stok <= 5 ? 'stok-kritis' : ($stok <= 15 ? 'stok-terbatas' : 'stok-tersedia'));
                $stok_label = $stok === 0 ? 'Habis' : ($stok <= 5 ? 'Hampir Habis' : ($stok <= 15 ? 'Terbatas' : 'Tersedia'));
            ?>
            <div class="sparepart-card" data-kategori="<?php echo htmlspecialchars($p['kategori']); ?>">
                <div class="sparepart-img-wrap">
                    <?php if (!empty($p['gambar']) && file_exists(__DIR__ . '/' . $p['gambar'])): ?>
                        <img src="<?php echo htmlspecialchars($p['gambar']); ?>"
                             alt="<?php echo htmlspecialchars($p['nama_barang']); ?>"
                             class="sparepart-img">
                    <?php else: ?>
                        <div class="sparepart-img-placeholder">🔩</div>
                    <?php endif; ?>
                </div>
                <div class="sparepart-body">
                    <h3 class="sparepart-nama"><?php echo htmlspecialchars($p['nama_barang']); ?></h3>
                    <div class="sparepart-harga">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></div>
                    <div class="sparepart-stok-row">
                        <span class="stok-dot <?php echo $stok_class; ?>"></span>
                        <span class="stok-text <?php echo $stok_class; ?>">
                            <?php echo $stok_label; ?>
                            <?php if ($stok > 0): ?>
                                <span class="stok-angka">(<?php echo $stok; ?> unit)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($spareparts_list)): ?>
                <div class="sparepart-empty">
                    <div style="font-size:3rem;margin-bottom:12px;">🔩</div>
                    <p>Belum ada data sparepart.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align:center;margin-top:40px;">
            <a href="<?php echo isset($_SESSION['role']) ? 'booking.php' : 'register.php'; ?>" class="btn-hero">
                🔧 Booking dengan Sparepart Ini
            </a>
        </div>
    </div>
</section>

<!-- ── CARA KERJA ── -->
<section class="section-steps" id="cara-kerja">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag">Cara Kerja</span>
            <h2>Servis Mudah dalam 4 Langkah</h2>
            <p>Proses booking yang simpel dan transparan dari awal hingga kendaraan siap diambil.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <div class="step-icon">📝</div>
                <h3>Daftar & Login</h3>
                <p>Buat akun gratis dan masuk ke sistem AutoPro Workshop.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-icon">📅</div>
                <h3>Pilih Layanan</h3>
                <p>Pilih jenis servis, tanggal, dan jam yang sesuai jadwal Anda.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-icon">🔧</div>
                <h3>Servis Dikerjakan</h3>
                <p>Teknisi kami mengerjakan kendaraan Anda. Pantau status secara real-time.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-card">
                <div class="step-number">04</div>
                <div class="step-icon">✅</div>
                <h3>Kendaraan Siap</h3>
                <p>Kendaraan selesai diservis dan siap diambil. Bayar di tempat.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── KENAPA KAMI ── -->
<section class="section-why" id="kenapa-kami">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag">Keunggulan Kami</span>
            <h2>Kenapa Pilih AutoPro?</h2>
            <p>Kami berkomitmen memberikan layanan terbaik dengan standar kualitas tertinggi.</p>
        </div>
        <div class="why-grid">
            <div class="why-card"><div class="why-icon">🏆</div><h3>Teknisi Bersertifikasi</h3><p>Semua mekanik kami telah tersertifikasi dan berpengalaman lebih dari 5 tahun di bidangnya.</p></div>
            <div class="why-card"><div class="why-icon">💰</div><h3>Harga Transparan</h3><p>Tidak ada biaya tersembunyi. Estimasi harga ditampilkan sebelum Anda konfirmasi booking.</p></div>
            <div class="why-card"><div class="why-icon">⚡</div><h3>Pengerjaan Cepat</h3><p>Sistem antrian digital memastikan kendaraan Anda dikerjakan tepat waktu sesuai jadwal.</p></div>
            <div class="why-card"><div class="why-icon">🛡️</div><h3>Garansi Resmi</h3><p>Setiap penggantian sparepart orisinal dilindungi garansi mekanis hingga 3 bulan.</p></div>
            <div class="why-card"><div class="why-icon">📱</div><h3>Booking Online 24/7</h3><p>Jadwalkan servis kapan saja dan dari mana saja melalui platform digital kami.</p></div>
            <div class="why-card"><div class="why-icon">🔍</div><h3>Pantau Real-time</h3><p>Lacak status pengerjaan kendaraan Anda secara langsung tanpa perlu menelepon.</p></div>
        </div>
    </div>
</section>

<!-- ── TESTIMONI ── -->
<section class="section-testimoni">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag">Testimoni</span>
            <h2>Kata Pelanggan Kami</h2>
            <p>Ribuan pelanggan telah mempercayakan kendaraan mereka kepada AutoPro Workshop.</p>
        </div>
        <div class="testimoni-grid">
            <div class="testimoni-card">
                <div class="testimoni-stars">★★★★★</div>
                <p>"Booking online-nya sangat mudah. Teknisinya ramah dan profesional. Mobil saya selesai tepat waktu sesuai janji!"</p>
                <div class="testimoni-author"><div class="testimoni-avatar">A</div><div><strong>Ahmad Fauzi</strong><span>Toyota Avanza</span></div></div>
            </div>
            <div class="testimoni-card">
                <div class="testimoni-stars">★★★★★</div>
                <p>"Harganya transparan, tidak ada biaya kejutan. Bisa pantau status servis dari HP, sangat membantu!"</p>
                <div class="testimoni-author"><div class="testimoni-avatar">S</div><div><strong>Siti Rahayu</strong><span>Honda Brio</span></div></div>
            </div>
            <div class="testimoni-card">
                <div class="testimoni-stars">★★★★☆</div>
                <p>"Sudah 3x servis di sini. Hasilnya selalu memuaskan. Mekaniknya jujur dan menjelaskan masalah dengan detail."</p>
                <div class="testimoni-author"><div class="testimoni-avatar">B</div><div><strong>Budi Santoso</strong><span>Suzuki Ertiga</span></div></div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="section-cta">
    <div class="section-container">
        <div class="cta-box">
            <h2>Siap Booking Servis Sekarang?</h2>
            <p>Daftarkan akun gratis dan nikmati kemudahan booking servis kendaraan Anda.</p>
            <div class="hero-actions" style="justify-content:center;">
                <?php if (isset($_SESSION['role'])): ?>
                    <a href="booking.php" class="btn-hero">🔧 Booking Sekarang</a>
                <?php else: ?>
                    <a href="register.php" class="btn-hero">Buat Akun Gratis</a>
                    <a href="login.php" class="btn-hero-ghost">Sudah punya akun?</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
