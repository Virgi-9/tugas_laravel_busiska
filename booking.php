<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$flash   = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $layanan_id   = (int)$_POST['layanan_id'];
    $sparepart_id = !empty($_POST['sparepart_id']) ? (int)$_POST['sparepart_id'] : null;
    $tanggal      = $_POST['tanggal'];
    $jam          = $_POST['jam'];
    $nama_kend    = trim($_POST['nama_kendaraan']);
    $plat         = strtoupper(trim($_POST['plat_nomor']));
    $keluhan      = trim($_POST['keluhan'] ?? '');

    $stmt = $pdo->prepare("SELECT harga FROM layanan WHERE id = ?");
    $stmt->execute([$layanan_id]);
    $total = (float)$stmt->fetchColumn();

    $ok = true;
    if ($sparepart_id) {
        $stmt = $pdo->prepare("SELECT harga, stok FROM spareparts WHERE id = ?");
        $stmt->execute([$sparepart_id]);
        $part = $stmt->fetch();
        if ($part && $part['stok'] > 0) {
            $total += (float)$part['harga'];
            $pdo->prepare("UPDATE spareparts SET stok = stok - 1 WHERE id = ?")->execute([$sparepart_id]);
        } else {
            $flash = ['type' => 'error', 'msg' => 'Stok sparepart habis, silakan pilih lain.'];
            $ok = false;
        }
    }

    if ($ok) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, layanan_id, sparepart_id, nama_kendaraan, plat_nomor, keluhan, tanggal, jam, total_harga)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $layanan_id, $sparepart_id, $nama_kend, $plat, $keluhan, $tanggal, $jam, $total]);
        $pdo->prepare("INSERT INTO transactions (booking_id) VALUES (?)")->execute([$pdo->lastInsertId()]);
        $flash = ['type' => 'success', 'msg' => 'Booking berhasil! Kami akan segera memproses jadwal servis Anda.'];
    }
}

$layanan_list = $pdo->query("SELECT * FROM layanan ORDER BY nama_servis")->fetchAll();
$parts_list   = $pdo->query("SELECT * FROM spareparts WHERE stok > 0 ORDER BY kategori, nama_barang")->fetchAll();

$my_bookings = $pdo->prepare("
    SELECT b.*, l.nama_servis, s.nama_barang, t.status_pembayaran
    FROM bookings b
    JOIN layanan l ON b.layanan_id = l.id
    LEFT JOIN spareparts s ON b.sparepart_id = s.id
    JOIN transactions t ON t.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$my_bookings->execute([$user_id]);
$my_bookings = $my_bookings->fetchAll();

function statusBadge(string $s): string {
    return match($s) { 'Selesai' => 'badge-done', 'Sedang Diproses' => 'badge-process', default => 'badge-waiting' };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Servis - AutoPro Workshop</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
</head>
<body class="page-booking">
<?php
$navbar_scrolled = true;
$navbar_links    = false;
$footer_minimal  = true;
require __DIR__ . '/partials/navbar.php';
?>

<div class="booking-page">
    <div class="booking-container">

        <!-- Form Booking -->
        <div class="booking-form-wrap">
            <div class="booking-header">
                <h1>Booking Servis</h1>
                <p>Isi form di bawah untuk menjadwalkan servis kendaraan Anda.</p>
            </div>

            <?php if ($flash['msg']): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['msg']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form action="" method="POST">
                    <p class="card-title">📋 Detail Kendaraan</p>
                    <div class="form-grid">
                        <div class="input-wrap">
                            <input type="text" name="nama_kendaraan" placeholder=" " required>
                            <label>Nama / Tipe Kendaraan *</label>
                        </div>
                        <div class="input-wrap">
                            <input type="text" name="plat_nomor" placeholder=" " required style="text-transform:uppercase;">
                            <label>Plat Nomor *</label>
                        </div>
                    </div>
                    <div class="input-wrap">
                        <textarea name="keluhan" placeholder=" "></textarea>
                        <label>Keluhan / Deskripsi Masalah</label>
                    </div>

                    <p class="card-title" style="margin-top:24px;">🔧 Pilih Layanan</p>
                    <div class="form-grid">
                        <div class="input-wrap">
                            <select name="layanan_id" required id="sel-layanan">
                                <option value="" disabled selected></option>
                                <?php foreach ($layanan_list as $l): ?>
                                    <option value="<?php echo $l['id']; ?>" data-harga="<?php echo $l['harga']; ?>">
                                        <?php echo htmlspecialchars($l['nama_servis']); ?> — Rp <?php echo number_format($l['harga'], 0, ',', '.'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Jenis Layanan *</label>
                        </div>
                        <div class="input-wrap">
                            <select name="sparepart_id" id="sel-part">
                                <option value="" data-harga="0" data-gambar="" data-nama="">Tanpa Sparepart</option>
                                <?php foreach ($parts_list as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"
                                            data-harga="<?php echo $p['harga']; ?>"
                                            data-gambar="<?php echo htmlspecialchars($p['gambar'] ?? ''); ?>"
                                            data-nama="<?php echo htmlspecialchars($p['nama_barang']); ?>"
                                            data-stok="<?php echo $p['stok']; ?>">
                                        <?php echo htmlspecialchars($p['nama_barang']); ?> (Stok: <?php echo $p['stok']; ?>) — Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Sparepart Tambahan (Opsional)</label>
                            <!-- Preview sparepart yang dipilih -->
                            <div id="part-preview" class="part-preview-box" style="display:none;">
                                <img id="part-preview-img" src="" alt="">
                                <div class="part-preview-info">
                                    <strong id="part-preview-nama"></strong>
                                    <span id="part-preview-stok"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="card-title" style="margin-top:24px;">📅 Jadwal Servis</p>
                    <div class="form-grid">
                        <div class="input-wrap">
                            <input type="date" name="tanggal" placeholder=" " required min="<?php echo date('Y-m-d'); ?>">
                            <label>Tanggal Servis *</label>
                        </div>
                        <div class="input-wrap">
                            <input type="time" name="jam" placeholder=" " required min="08:00" max="17:00">
                            <label>Jam Servis * (08.00–17.00)</label>
                        </div>
                    </div>

                    <!-- Estimasi Harga -->
                    <div class="estimasi-box">
                        <span>Estimasi Total Biaya</span>
                        <strong id="estimasi-harga">Rp 0</strong>
                    </div>

                    <button type="submit" class="btn-login">Konfirmasi Booking</button>
                </form>
            </div>
        </div>

        <!-- Riwayat Booking -->
        <div class="booking-history">
            <div class="card">
                <p class="card-title">🕐 Riwayat Booking Saya</p>
                <?php if (empty($my_bookings)): ?>
                    <div style="text-align:center;padding:30px;color:var(--muted);">
                        <div style="font-size:2.5rem;margin-bottom:12px;">📋</div>
                        <p>Belum ada booking.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Layanan</th><th>Kendaraan</th><th>Jadwal</th><th>Total</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_bookings as $b): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['nama_servis']); ?>
                                        <?php if ($b['nama_barang']): ?>
                                            <br><small style="color:var(--muted)">+ <?php echo htmlspecialchars($b['nama_barang']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['nama_kendaraan'] ?? '-'); ?>
                                        <br><small style="color:var(--muted)"><?php echo htmlspecialchars($b['plat_nomor'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo $b['tanggal']; ?>
                                        <br><small style="color:var(--muted)"><?php echo substr($b['jam'], 0, 5); ?></small>
                                    </td>
                                    <td>Rp <?php echo number_format($b['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?php echo statusBadge($b['status_pengerjaan']); ?>">
                                            <?php echo $b['status_pengerjaan']; ?>
                                        </span>
                                        <br>
                                        <span class="badge <?php echo $b['status_pembayaran'] === 'Lunas' ? 'badge-done' : 'badge-waiting'; ?>" style="margin-top:4px;">
                                            <?php echo $b['status_pembayaran']; ?>
                                        </span>
                                        <?php if (!empty($b['catatan_mekanik'])): ?>
                                            <br><small style="color:var(--muted);font-size:.75rem;display:block;margin-top:4px;">
                                                💬 <?php echo htmlspecialchars($b['catatan_mekanik']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
    // Estimasi harga otomatis + preview sparepart
    const selLayanan    = document.getElementById('sel-layanan');
    const selPart       = document.getElementById('sel-part');
    const estimasi      = document.getElementById('estimasi-harga');
    const partPreview   = document.getElementById('part-preview');
    const partPrevImg   = document.getElementById('part-preview-img');
    const partPrevNama  = document.getElementById('part-preview-nama');
    const partPrevStok  = document.getElementById('part-preview-stok');

    function updateEstimasi() {
        const hLayanan = parseFloat(selLayanan.selectedOptions[0]?.dataset.harga || 0);
        const hPart    = parseFloat(selPart.selectedOptions[0]?.dataset.harga || 0);
        const total    = hLayanan + hPart;
        estimasi.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    function updatePartPreview() {
        const opt    = selPart.selectedOptions[0];
        const gambar = opt?.dataset.gambar || '';
        const nama   = opt?.dataset.nama   || '';
        const stok   = opt?.dataset.stok   || '';
        const val    = selPart.value;

        if (val && gambar) {
            partPrevImg.src          = gambar;
            partPrevImg.style.display = 'block';
        } else if (val) {
            partPrevImg.style.display = 'none';
        }

        if (val) {
            partPrevNama.textContent = nama;
            partPrevStok.textContent = 'Stok tersedia: ' + stok + ' unit';
            partPreview.style.display = 'flex';
        } else {
            partPreview.style.display = 'none';
        }
    }

    selLayanan.addEventListener('change', updateEstimasi);
    selPart.addEventListener('change', () => { updateEstimasi(); updatePartPreview(); });
</script>
</body>
</html>
