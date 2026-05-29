<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role'])) { header("Location: login.php"); exit; }

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$flash   = ['type' => '', 'msg' => ''];

// ── POST HANDLER ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // USER: Tambah Booking
    if ($action === 'add_booking' && $role === 'User') {
        $layanan_id   = (int)$_POST['layanan_id'];
        $sparepart_id = !empty($_POST['sparepart_id']) ? (int)$_POST['sparepart_id'] : null;
        $tanggal      = $_POST['tanggal'];
        $jam          = $_POST['jam'];
        $nama_kend    = trim($_POST['nama_kendaraan'] ?? '');
        $plat         = strtoupper(trim($_POST['plat_nomor'] ?? ''));
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
                $flash = ['type' => 'error', 'msg' => 'Stok sparepart habis.'];
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
            $flash = ['type' => 'success', 'msg' => 'Booking berhasil! Silakan pantau status di menu Riwayat Booking.'];
        }
    }

    // MEKANIK / SUPER ADMIN: Update status + catatan
    if ($action === 'update_status' && in_array($role, ['Mekanik', 'Super Admin'])) {
        $pdo->prepare("UPDATE bookings SET status_pengerjaan = ?, catatan_mekanik = ? WHERE id = ?")
            ->execute([$_POST['status_pengerjaan'], trim($_POST['catatan_mekanik'] ?? ''), (int)$_POST['booking_id']]);
        $flash = ['type' => 'success', 'msg' => 'Status pengerjaan diperbarui.'];
    }

    // ADMIN / SUPER ADMIN
    if (in_array($role, ['Admin', 'Super Admin'])) {

        // Kelola Layanan
        if ($action === 'add_layanan') {
            $pdo->prepare("INSERT INTO layanan (nama_servis, deskripsi, harga) VALUES (?, ?, ?)")
                ->execute([trim($_POST['nama_servis']), trim($_POST['deskripsi'] ?? ''), (float)$_POST['harga']]);
            $flash = ['type' => 'success', 'msg' => 'Layanan berhasil ditambahkan.'];
        }
        if ($action === 'edit_layanan') {
            $pdo->prepare("UPDATE layanan SET nama_servis = ?, deskripsi = ?, harga = ? WHERE id = ?")
                ->execute([trim($_POST['nama_servis']), trim($_POST['deskripsi'] ?? ''), (float)$_POST['harga'], (int)$_POST['id']]);
            $flash = ['type' => 'success', 'msg' => 'Layanan berhasil diperbarui.'];
        }
        if ($action === 'delete_layanan') {
            $pdo->prepare("DELETE FROM layanan WHERE id = ?")->execute([(int)$_POST['id']]);
            $flash = ['type' => 'success', 'msg' => 'Layanan dihapus.'];
        }

        // Kelola Sparepart
        if ($action === 'add_part') {
            $gambar = null;
            if (!empty($_FILES['gambar']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed) && $_FILES['gambar']['size'] <= 2 * 1024 * 1024) {
                    $gambar = 'uploads/spareparts/' . uniqid('part_') . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], __DIR__ . '/' . $gambar);
                } else {
                    $flash = ['type' => 'error', 'msg' => 'Gambar tidak valid. Gunakan JPG/PNG/WEBP maks 2MB.'];
                }
            }
            if ($flash['type'] !== 'error') {
                $pdo->prepare("INSERT INTO spareparts (nama_barang, kategori, stok, harga, gambar) VALUES (?, ?, ?, ?, ?)")
                    ->execute([trim($_POST['nama_barang']), trim($_POST['kategori'] ?? 'Umum'), (int)$_POST['stok'], (float)$_POST['harga'], $gambar]);
                $flash = ['type' => 'success', 'msg' => 'Sparepart berhasil ditambahkan.'];
            }
        }
        if ($action === 'edit_part') {
            $id = (int)$_POST['id'];
            // Ambil gambar lama
            $old = $pdo->prepare("SELECT gambar FROM spareparts WHERE id = ?");
            $old->execute([$id]);
            $gambar = $old->fetchColumn();

            if (!empty($_FILES['gambar']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed) && $_FILES['gambar']['size'] <= 2 * 1024 * 1024) {
                    // Hapus gambar lama
                    if ($gambar && file_exists(__DIR__ . '/' . $gambar)) {
                        unlink(__DIR__ . '/' . $gambar);
                    }
                    $gambar = 'uploads/spareparts/' . uniqid('part_') . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], __DIR__ . '/' . $gambar);
                } else {
                    $flash = ['type' => 'error', 'msg' => 'Gambar tidak valid. Gunakan JPG/PNG/WEBP maks 2MB.'];
                }
            }
            if ($flash['type'] !== 'error') {
                $pdo->prepare("UPDATE spareparts SET nama_barang = ?, kategori = ?, stok = ?, harga = ?, gambar = ? WHERE id = ?")
                    ->execute([trim($_POST['nama_barang']), trim($_POST['kategori'] ?? 'Umum'), (int)$_POST['stok'], (float)$_POST['harga'], $gambar, $id]);
                $flash = ['type' => 'success', 'msg' => 'Sparepart berhasil diperbarui.'];
            }
        }
        if ($action === 'delete_part') {
            $id  = (int)$_POST['id'];
            $row = $pdo->prepare("SELECT gambar FROM spareparts WHERE id = ?");
            $row->execute([$id]);
            $gambar = $row->fetchColumn();
            if ($gambar && file_exists(__DIR__ . '/' . $gambar)) {
                unlink(__DIR__ . '/' . $gambar);
            }
            $pdo->prepare("DELETE FROM spareparts WHERE id = ?")->execute([$id]);
            $flash = ['type' => 'success', 'msg' => 'Sparepart dihapus.'];
        }

        // Konfirmasi Pembayaran
        if ($action === 'konfirmasi_bayar') {
            $pdo->prepare("UPDATE transactions SET status_pembayaran = 'Lunas', metode_bayar = ? WHERE id = ?")
                ->execute([$_POST['metode_bayar'] ?? 'Tunai', (int)$_POST['tx_id']]);
            $flash = ['type' => 'success', 'msg' => 'Pembayaran dikonfirmasi lunas.'];
        }
    }

    // SUPER ADMIN: RBAC
    if ($action === 'change_role' && $role === 'Super Admin') {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$_POST['new_role'], (int)$_POST['user_id']]);
        $flash = ['type' => 'success', 'msg' => 'Role pengguna berhasil diperbarui.'];
    }
}

// ── FETCH DATA ────────────────────────────────────────────────
$layanan_list = $pdo->query("SELECT * FROM layanan ORDER BY nama_servis")->fetchAll();
$parts_list   = $pdo->query("SELECT * FROM spareparts ORDER BY kategori, nama_barang")->fetchAll();

$stmt = $pdo->prepare("
    SELECT b.*, l.nama_servis, s.nama_barang, t.status_pembayaran
    FROM bookings b
    JOIN layanan l ON b.layanan_id = l.id
    LEFT JOIN spareparts s ON b.sparepart_id = s.id
    JOIN transactions t ON t.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll();

$all_bookings = $pdo->query("
    SELECT b.*, u.nama AS customer, l.nama_servis
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN layanan l ON b.layanan_id = l.id
    ORDER BY b.tanggal ASC, b.jam ASC
")->fetchAll();

$all_txs = $pdo->query("
    SELECT t.id, t.status_pembayaran, t.metode_bayar, b.total_harga, b.id AS b_id, u.nama
    FROM transactions t
    JOIN bookings b ON t.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    ORDER BY t.id DESC
")->fetchAll();

$revenue     = $pdo->query("SELECT COALESCE(SUM(b.total_harga), 0) FROM transactions t JOIN bookings b ON t.booking_id = b.id WHERE t.status_pembayaran = 'Lunas'")->fetchColumn();
$total_book  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending_pay = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status_pembayaran = 'Belum Bayar'")->fetchColumn();
$total_parts = $pdo->query("SELECT COUNT(*) FROM spareparts")->fetchColumn();

$stmt = $pdo->prepare("SELECT id, nama, email, role, created_at FROM users WHERE id != ? ORDER BY role, nama");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll();

// Helper: badge class untuk status pengerjaan
function statusBadge(string $status): string {
    return match($status) {
        'Selesai'         => 'badge-done',
        'Sedang Diproses' => 'badge-process',
        default           => 'badge-waiting',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AutoPro Workshop</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
</head>
<body class="page-dashboard">

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">⚙</div>
        <span class="brand-name">AutoPro</span>
    </div>

    <?php if ($role === 'User'): ?>
        <span class="sidebar-section">Pelanggan</span>
        <ul class="sidebar-menu">
            <li><a href="#" class="nav-link active" data-target="sec-booking"><span class="icon">📋</span> Form Booking</a></li>
            <li><a href="#" class="nav-link" data-target="sec-riwayat"><span class="icon">🕐</span> Riwayat Booking</a></li>
        </ul>
    <?php endif; ?>

    <?php if (in_array($role, ['Mekanik', 'Super Admin'])): ?>
        <span class="sidebar-section">Mekanik</span>
        <ul class="sidebar-menu">
            <li><a href="#" class="nav-link <?php echo $role === 'Mekanik' ? 'active' : ''; ?>" data-target="sec-antrean">
                <span class="icon">🔧</span> Antrean Servis
            </a></li>
        </ul>
    <?php endif; ?>

    <?php if (in_array($role, ['Admin', 'Super Admin'])): ?>
        <span class="sidebar-section"><?php echo $role === 'Admin' ? 'Admin' : 'Administrasi'; ?></span>
        <ul class="sidebar-menu">
            <li><a href="#" class="nav-link <?php echo $role === 'Admin' ? 'active' : ''; ?>" data-target="sec-layanan">
                <span class="icon">📋</span> Kelola Layanan
            </a></li>
            <li><a href="#" class="nav-link" data-target="sec-sparepart">
                <span class="icon">🔩</span> Kelola Sparepart
            </a></li>
            <li><a href="#" class="nav-link" data-target="sec-transaksi">
                <span class="icon">💰</span> Transaksi & Keuangan
            </a></li>
        </ul>
    <?php endif; ?>

    <?php if ($role === 'Super Admin'): ?>
        <span class="sidebar-section">Super Admin</span>
        <ul class="sidebar-menu">
            <li><a href="#" class="nav-link" data-target="sec-rbac">
                <span class="icon">👥</span> Manajemen Staf
            </a></li>
        </ul>
    <?php endif; ?>

    <div class="sidebar-footer">
        <a href="index.php">🏠 Halaman Utama</a>
        <a href="logout.php">🚪 Keluar</a>
    </div>
</aside>

<!-- ── MAIN ── -->
<main class="main-content">
    <div class="page-header">
        <div>
            <h1><?php echo $role === 'Super Admin' ? 'Dashboard Super Admin' : ($role === 'Admin' ? 'Dashboard Admin' : 'Dashboard Operasional'); ?></h1>
            <p>Halo, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong> — selamat datang kembali.</p>
        </div>
        <span class="user-chip">Role: <?php echo $role; ?></span>
    </div>

    <?php if ($flash['msg']): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($flash['msg']); ?>
        </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════
         USER: FORM BOOKING
    ════════════════════════════════════════ -->
    <?php if ($role === 'User'): ?>
    <div id="sec-booking" class="section-view active-view">
        <div class="card">
            <p class="card-title">📋 Ajukan Booking Servis</p>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_booking">
                <div class="form-grid">
                    <div class="input-wrap">
                        <select name="layanan_id" required>
                            <option value="" disabled selected></option>
                            <?php foreach ($layanan_list as $l): ?>
                                <option value="<?php echo $l['id']; ?>">
                                    <?php echo htmlspecialchars($l['nama_servis']); ?> — Rp <?php echo number_format($l['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Pilih Layanan *</label>
                    </div>
                    <div class="input-wrap">
                        <select name="sparepart_id">
                            <option value="">Tanpa Sparepart</option>
                            <?php foreach ($parts_list as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $p['stok'] <= 0 ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nama_barang']); ?> (Stok: <?php echo $p['stok']; ?>) — Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Sparepart (Opsional)</label>
                    </div>
                </div>
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
                <div class="form-grid">
                    <div class="input-wrap">
                        <input type="date" name="tanggal" placeholder=" " required min="<?php echo date('Y-m-d'); ?>">
                        <label>Tanggal Servis *</label>
                    </div>
                    <div class="input-wrap">
                        <input type="time" name="jam" placeholder=" " required>
                        <label>Jam Servis *</label>
                    </div>
                </div>
                <div class="input-wrap">
                    <textarea name="keluhan" placeholder=" "></textarea>
                    <label>Keluhan / Deskripsi Masalah</label>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Konfirmasi Booking</button>
            </form>
        </div>
    </div>

    <!-- USER: RIWAYAT BOOKING -->
    <div id="sec-riwayat" class="section-view">
        <div class="card">
            <p class="card-title">🕐 Riwayat Booking Saya</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Kendaraan</th><th>Layanan</th><th>Sparepart</th>
                            <th>Jadwal</th><th>Total</th><th>Pengerjaan</th><th>Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_bookings)): ?>
                            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">Belum ada booking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_bookings as $b): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($b['nama_kendaraan'] ?? '-'); ?>
                                    <br><small style="color:var(--muted)"><?php echo htmlspecialchars($b['plat_nomor'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($b['nama_servis']); ?></td>
                                <td><?php echo htmlspecialchars($b['nama_barang'] ?? '-'); ?></td>
                                <td>
                                    <?php echo $b['tanggal']; ?>
                                    <br><small style="color:var(--muted)"><?php echo substr($b['jam'], 0, 5); ?></small>
                                </td>
                                <td>Rp <?php echo number_format($b['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php echo statusBadge($b['status_pengerjaan']); ?>">
                                        <?php echo $b['status_pengerjaan']; ?>
                                    </span>
                                    <?php if (!empty($b['catatan_mekanik'])): ?>
                                        <br><small style="color:var(--muted);font-size:.75rem;">
                                            <?php echo htmlspecialchars($b['catatan_mekanik']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $b['status_pembayaran'] === 'Lunas' ? 'badge-done' : 'badge-waiting'; ?>">
                                        <?php echo $b['status_pembayaran']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════
         MEKANIK: ANTREAN SERVIS
    ════════════════════════════════════════ -->
    <?php if (in_array($role, ['Mekanik', 'Super Admin'])): ?>
    <div id="sec-antrean" class="section-view <?php echo $role === 'Mekanik' ? 'active-view' : ''; ?>">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Booking</div>
                <div class="value"><?php echo $total_book; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Menunggu</div>
                <div class="value"><?php echo count(array_filter($all_bookings, fn($b) => $b['status_pengerjaan'] === 'Menunggu Antrian')); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Sedang Diproses</div>
                <div class="value"><?php echo count(array_filter($all_bookings, fn($b) => $b['status_pengerjaan'] === 'Sedang Diproses')); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Selesai</div>
                <div class="value"><?php echo count(array_filter($all_bookings, fn($b) => $b['status_pengerjaan'] === 'Selesai')); ?></div>
            </div>
        </div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
                <div>
                    <p class="card-title">🔧 Daftar Antrean Servis</p>
                    <p style="color:var(--muted);margin-top:6px;max-width:620px;">
                        Lihat semua booking yang menunggu pengerjaan, keluhan servis, sparepart pilihan pelanggan, dan update status kerja dengan mudah.
                    </p>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="document.querySelector('#sec-antrean .table-wrap').scrollIntoView({behavior:'smooth'})">
                    Lihat Antrian
                </button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Pelanggan</th><th>Kendaraan</th><th>Layanan</th>
                            <th>Sparepart</th><th>Jadwal</th><th>Keluhan</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_bookings)): ?>
                            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px;">Belum ada booking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_bookings as $b): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($b['nama_kendaraan'] ?? '-'); ?>
                                    <br><small style="color:var(--muted)"><?php echo htmlspecialchars($b['plat_nomor'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($b['nama_servis']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($b['nama_barang'] ?? '-'); ?>
                                </td>
                                <td>
                                    <?php echo $b['tanggal']; ?>
                                    <br><small style="color:var(--muted)"><?php echo substr($b['jam'], 0, 5); ?></small>
                                </td>
                                <td style="max-width:180px;font-size:.82rem;color:var(--muted);">
                                    <?php echo htmlspecialchars($b['keluhan'] ?? '-'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo statusBadge($b['status_pengerjaan']); ?>">
                                        <?php echo $b['status_pengerjaan']; ?>
                                    </span>
                                    <?php if (!empty($b['catatan_mekanik'])): ?>
                                        <br><small style="color:var(--muted);font-size:.75rem;">
                                            <?php echo htmlspecialchars($b['catatan_mekanik']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-ghost btn-sm"
                                        onclick="openUpdateModal(
                                            <?php echo $b['id']; ?>,
                                            '<?php echo addslashes($b['status_pengerjaan']); ?>',
                                            '<?php echo addslashes($b['catatan_mekanik'] ?? ''); ?>'
                                        )">Ubah Status</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════
         ADMIN: KELOLA LAYANAN
    ════════════════════════════════════════ -->
    <?php if (in_array($role, ['Admin', 'Super Admin'])): ?>
    <div id="sec-layanan" class="section-view <?php echo $role === 'Admin' ? 'active-view' : ''; ?>">
        <div class="card" style="margin-bottom:24px;">
            <p class="card-title">➕ Tambah Layanan Baru</p>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_layanan">
                <div class="form-grid-3">
                    <div class="input-wrap">
                        <input type="text" name="nama_servis" placeholder=" " required>
                        <label>Nama Layanan *</label>
                    </div>
                    <div class="input-wrap">
                        <input type="number" name="harga" placeholder=" " required min="0">
                        <label>Harga (Rp) *</label>
                    </div>
                    <div class="input-wrap">
                        <input type="text" name="deskripsi" placeholder=" ">
                        <label>Deskripsi</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Tambah Layanan</button>
            </form>
        </div>
        <div class="card">
            <p class="card-title">📋 Daftar Layanan</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>#</th><th>Nama Layanan</th><th>Deskripsi</th><th>Harga</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($layanan_list as $l): ?>
                        <tr>
                            <td><?php echo $l['id']; ?></td>
                            <td><?php echo htmlspecialchars($l['nama_servis']); ?></td>
                            <td style="color:var(--muted);font-size:.85rem"><?php echo htmlspecialchars($l['deskripsi'] ?? '-'); ?></td>
                            <td>Rp <?php echo number_format($l['harga'], 0, ',', '.'); ?></td>
                            <td style="display:flex;gap:6px;">
                                <button class="btn btn-ghost btn-sm"
                                    onclick="openEditLayanan(
                                        <?php echo $l['id']; ?>,
                                        '<?php echo addslashes($l['nama_servis']); ?>',
                                        '<?php echo addslashes($l['deskripsi'] ?? ''); ?>',
                                        <?php echo $l['harga']; ?>
                                    )">Edit</button>
                                <form action="" method="POST" onsubmit="return confirm('Hapus layanan ini?')">
                                    <input type="hidden" name="action" value="delete_layanan">
                                    <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         ADMIN: KELOLA SPAREPART (CRUD)
    ════════════════════════════════════════ -->
    <div id="sec-sparepart" class="section-view">
        <div class="card" style="margin-bottom:24px;">
            <p class="card-title">➕ Tambah Sparepart Baru</p>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_part">
                <div class="form-grid">
                    <div class="input-wrap">
                        <input type="text" name="nama_barang" placeholder=" " required>
                        <label>Nama Sparepart *</label>
                    </div>
                    <div class="input-wrap">
                        <select name="kategori">
                            <option value="Motor Matic">Motor Matic</option>
                            <option value="Motor Manual">Motor Manual</option>
                            <option value="Motor Sport">Motor Sport</option>
                            <option value="Universal">Universal</option>
                        </select>
                        <label>Jenis Kendaraan</label>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="input-wrap">
                        <input type="number" name="stok" placeholder=" " required min="0">
                        <label>Stok *</label>
                    </div>
                    <div class="input-wrap">
                        <input type="number" name="harga" placeholder=" " required min="0">
                        <label>Harga (Rp) *</label>
                    </div>
                </div>
                <div class="upload-wrap">
                    <label class="upload-label">
                        <span class="upload-icon">🖼️</span>
                        <span class="upload-text">Pilih Gambar Sparepart</span>
                        <small class="upload-hint">JPG, PNG, WEBP — maks 2MB (opsional)</small>
                        <input type="file" name="gambar" accept="image/*" onchange="previewImg(this,'prev-add')">
                    </label>
                    <img id="prev-add" class="img-preview" src="" alt="" style="display:none;">
                </div>
                <button type="submit" class="btn btn-primary">Tambah Sparepart</button>
            </form>
        </div>
        <div class="card">
            <p class="card-title">🔩 Daftar Sparepart</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Foto</th><th>#</th><th>Nama Barang</th><th>Kategori</th><th>Stok</th><th>Harga</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parts_list as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p['gambar']) && file_exists(__DIR__ . '/' . $p['gambar'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['gambar']); ?>?v=<?php echo filemtime(__DIR__.'/'.$p['gambar']); ?>"
                                         alt="<?php echo htmlspecialchars($p['nama_barang']); ?>"
                                         class="part-thumb">
                                <?php else: ?>
                                    <div class="part-thumb-placeholder">🔩</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['nama_barang']); ?></td>
                            <td><span class="badge badge-process"><?php echo htmlspecialchars($p['kategori']); ?></span></td>
                            <td>
                                <span class="badge <?php echo $p['stok'] <= 5 ? 'badge-danger' : ($p['stok'] <= 15 ? 'badge-waiting' : 'badge-done'); ?>">
                                    <?php echo $p['stok']; ?> unit
                                </span>
                            </td>
                            <td>Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                            <td style="display:flex;gap:6px;">
                                <button class="btn btn-ghost btn-sm"
                                    onclick="openEditPart(
                                        <?php echo $p['id']; ?>,
                                        '<?php echo addslashes($p['nama_barang']); ?>',
                                        '<?php echo addslashes($p['kategori']); ?>',
                                        <?php echo $p['stok']; ?>,
                                        <?php echo $p['harga']; ?>,
                                        '<?php echo addslashes($p['gambar'] ?? ''); ?>'
                                    )">Edit</button>
                                <form action="" method="POST" onsubmit="return confirm('Hapus sparepart ini?')">
                                    <input type="hidden" name="action" value="delete_part">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         ADMIN: TRANSAKSI & KEUANGAN
    ════════════════════════════════════════ -->
    <div id="sec-transaksi" class="section-view">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Pendapatan</div>
                <div class="value" style="font-size:1.35rem">Rp <?php echo number_format($revenue, 0, ',', '.'); ?></div>
                <div class="sub">dari transaksi lunas</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Booking</div>
                <div class="value"><?php echo $total_book; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Belum Bayar</div>
                <div class="value" style="color:var(--warning)"><?php echo $pending_pay; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Sparepart</div>
                <div class="value"><?php echo $total_parts; ?></div>
            </div>
        </div>
        <div class="card">
            <p class="card-title">💰 Data Transaksi</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>TX ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Metode</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_txs as $tx): ?>
                        <tr>
                            <td>TX-<?php echo $tx['id']; ?></td>
                            <td><?php echo htmlspecialchars($tx['nama']); ?></td>
                            <td>Rp <?php echo number_format($tx['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?php echo $tx['status_pembayaran'] === 'Lunas' ? 'badge-done' : 'badge-waiting'; ?>">
                                    <?php echo $tx['status_pembayaran']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tx['metode_bayar'] ?? '-'); ?></td>
                            <td>
                                <?php if ($tx['status_pembayaran'] === 'Belum Bayar'): ?>
                                    <button class="btn btn-success btn-sm" onclick="openBayarModal(<?php echo $tx['id']; ?>)">
                                        Konfirmasi Lunas
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--muted)">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════
         SUPER ADMIN: MANAJEMEN STAF
    ════════════════════════════════════════ -->
    <?php if ($role === 'Super Admin'): ?>
    <div id="sec-rbac" class="section-view">
        <div class="card">
            <p class="card-title">👥 Manajemen Akun & Role</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Ubah Role</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['nama']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge badge-process"><?php echo $u['role']; ?></span></td>
                            <td style="color:var(--muted);font-size:.82rem"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <form action="" method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="new_role" style="background:var(--bg-input);color:#FFF;border:1px solid var(--border-input);padding:6px 10px;border-radius:6px;font-size:.85rem;">
                                        <?php foreach (['User', 'Mekanik', 'Admin', 'Super Admin'] as $r): ?>
                                            <option value="<?php echo $r; ?>" <?php echo $u['role'] === $r ? 'selected' : ''; ?>>
                                                <?php echo $r; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- ════════════════════════════════════════
     MODALS
════════════════════════════════════════ -->

<!-- Modal: Update Status Mekanik -->
<div class="modal-overlay" id="modal-update">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Status Pengerjaan</h3>
            <button class="modal-close" onclick="closeModal('modal-update')">✕</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="booking_id" id="upd-booking-id">
            <div class="input-wrap">
                <select name="status_pengerjaan" id="upd-status">
                    <option value="Menunggu Antrian">Menunggu Antrian</option>
                    <option value="Sedang Diproses">Sedang Diproses</option>
                    <option value="Selesai">Selesai</option>
                </select>
                <label>Status Pengerjaan</label>
            </div>
            <div class="input-wrap">
                <textarea name="catatan_mekanik" id="upd-catatan" placeholder=" "></textarea>
                <label>Catatan Mekanik (opsional)</label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-update')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Layanan -->
<div class="modal-overlay" id="modal-edit-layanan">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Layanan</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-layanan')">✕</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="edit_layanan">
            <input type="hidden" name="id" id="el-id">
            <div class="input-wrap">
                <input type="text" name="nama_servis" id="el-nama" placeholder=" " required>
                <label>Nama Layanan *</label>
            </div>
            <div class="input-wrap">
                <input type="text" name="deskripsi" id="el-deskripsi" placeholder=" ">
                <label>Deskripsi</label>
            </div>
            <div class="input-wrap">
                <input type="number" name="harga" id="el-harga" placeholder=" " required min="0">
                <label>Harga (Rp) *</label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-layanan')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Sparepart -->
<div class="modal-overlay" id="modal-edit-part">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Sparepart</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-part')">✕</button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_part">
            <input type="hidden" name="id" id="ep-id">
            <div class="form-grid">
                <div class="input-wrap">
                    <input type="text" name="nama_barang" id="ep-nama" placeholder=" " required>
                    <label>Nama Sparepart *</label>
                </div>
                <div class="input-wrap">
                    <select name="kategori" id="ep-kategori">
                        <option value="Motor Matic">Motor Matic</option>
                        <option value="Motor Manual">Motor Manual</option>
                        <option value="Motor Sport">Motor Sport</option>
                        <option value="Universal">Universal</option>
                    </select>
                    <label>Jenis Kendaraan</label>
                </div>
            </div>
            <div class="form-grid">
                <div class="input-wrap">
                    <input type="number" name="stok" id="ep-stok" placeholder=" " required min="0">
                    <label>Stok *</label>
                </div>
                <div class="input-wrap">
                    <input type="number" name="harga" id="ep-harga" placeholder=" " required min="0">
                    <label>Harga (Rp) *</label>
                </div>
            </div>
            <div class="upload-wrap">
                <div id="ep-current-img" style="margin-bottom:10px;display:none;">
                    <p style="font-size:.78rem;color:var(--muted);margin-bottom:6px;">Gambar saat ini:</p>
                    <img id="ep-img-preview" src="" alt="" class="img-preview">
                </div>
                <label class="upload-label">
                    <span class="upload-icon">🖼️</span>
                    <span class="upload-text">Ganti Gambar (opsional)</span>
                    <small class="upload-hint">JPG, PNG, WEBP — maks 2MB</small>
                    <input type="file" name="gambar" accept="image/*" onchange="previewImg(this,'ep-img-preview');document.getElementById('ep-current-img').style.display='block'">
                </label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-part')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Konfirmasi Bayar -->
<div class="modal-overlay" id="modal-bayar">
    <div class="modal">
        <div class="modal-header">
            <h3>Konfirmasi Pembayaran</h3>
            <button class="modal-close" onclick="closeModal('modal-bayar')">✕</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="konfirmasi_bayar">
            <input type="hidden" name="tx_id" id="bayar-tx-id">
            <div class="input-wrap">
                <select name="metode_bayar">
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="QRIS">QRIS</option>
                    <option value="Debit">Kartu Debit</option>
                </select>
                <label>Metode Pembayaran</label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-bayar')">Batal</button>
                <button type="submit" class="btn btn-success">Konfirmasi Lunas</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Navigation ──
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.section-view').forEach(s => s.classList.remove('active-view'));
        const target = document.getElementById(this.dataset.target);
        if (target) target.classList.add('active-view');
    });
});

// ── Modals ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

function openUpdateModal(id, status, catatan) {
    document.getElementById('upd-booking-id').value = id;
    document.getElementById('upd-status').value     = status;
    document.getElementById('upd-catatan').value    = catatan;
    openModal('modal-update');
}

function openEditLayanan(id, nama, deskripsi, harga) {
    document.getElementById('el-id').value       = id;
    document.getElementById('el-nama').value     = nama;
    document.getElementById('el-deskripsi').value = deskripsi;
    document.getElementById('el-harga').value    = harga;
    openModal('modal-edit-layanan');
}

function openEditPart(id, nama, kategori, stok, harga, gambar) {
    document.getElementById('ep-id').value       = id;
    document.getElementById('ep-nama').value     = nama;
    document.getElementById('ep-kategori').value = kategori;
    document.getElementById('ep-stok').value     = stok;
    document.getElementById('ep-harga').value    = harga;

    const imgWrap = document.getElementById('ep-current-img');
    const imgEl   = document.getElementById('ep-img-preview');
    if (gambar) {
        imgEl.src = gambar;
        imgWrap.style.display = 'block';
    } else {
        imgEl.src = '';
        imgWrap.style.display = 'none';
    }
    openModal('modal-edit-part');
}

function openBayarModal(txId) {
    document.getElementById('bayar-tx-id').value = txId;
    openModal('modal-bayar');
}

// ── Preview gambar upload ──
function previewImg(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Auto-dismiss flash ──
const flash = document.querySelector('.alert');
if (flash) {
    setTimeout(() => {
        flash.style.transition = 'opacity .5s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 500);
    }, 4000);
}
</script>
</body>
</html>
