<?php
require 'db_connect.php';
$message = '';
$old = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $old      = ['nama' => htmlspecialchars($nama), 'email' => htmlspecialchars($email)];

    if (!empty($nama) && !empty($email) && !empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'User')");
            $stmt->execute([$nama, $email, $hashed]);
            $message = ['type' => 'success', 'text' => 'Registrasi berhasil! Silakan <a href="login.php">Login</a>.'];
            $old = ['nama' => '', 'email' => ''];
        } catch (PDOException $e) {
            $message = ['type' => 'error', 'text' => 'Email sudah terdaftar, gunakan email lain.'];
        }
    } else {
        $message = ['type' => 'error', 'text' => 'Semua kolom wajib diisi!'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoPro - Daftar Akun</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
</head>
<body class="page-register">

    <!-- Left panel -->
    <div class="left-panel">
        <div class="brand">
            <div class="brand-icon">⚙</div>
            <span class="brand-name">AutoPro Workshop</span>
        </div>
        <div class="feature-item">
            <div class="feature-icon">⚙</div>
            <div class="feature-text">
                <h3>Teknisi Berpengalaman</h3>
                <p>Kendaraan Anda ditangani langsung oleh mekanik bersertifikasi pabrikan.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-text">
                <h3>Layanan Cepat</h3>
                <p>Sistem manajemen digital terintegrasi memastikan pengerjaan tepat waktu.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-text">
                <h3>Garansi Resmi</h3>
                <p>Setiap penggantian suku cadang orisinal dilindungi garansi mekanis.</p>
            </div>
        </div>
    </div>

    <!-- Right panel -->
    <div class="right-panel">
        <div class="register-card">
            <h2>Buat Akun</h2>
            <p class="subtitle">Daftarkan diri Anda untuk menikmati kemudahan booking servis.</p>

            <?php if ($message): ?>
                <div class="alert <?php echo $message['type']; ?>">
                    <?php echo $message['text']; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-wrap">
                    <input type="text" id="nama" name="nama" placeholder=" " required
                           value="<?php echo $old['nama']; ?>">
                    <label for="nama">Nama Lengkap</label>
                </div>

                <div class="input-wrap">
                    <input type="email" id="email" name="email" placeholder=" " required
                           value="<?php echo $old['email']; ?>">
                    <label for="email">Alamat Email</label>
                </div>

                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>

                <label class="show-pass-row">
                    <input type="checkbox" id="show-pass"> Tampilkan password
                </label>

                <button type="submit" class="btn-submit">Daftar Sekarang</button>
            </form>

            <div class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
        </div>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const passInput = document.getElementById('password');
        const showPass  = document.getElementById('show-pass');
        showPass.addEventListener('change', () => {
            passInput.type = showPass.checked ? 'text' : 'password';
        });
    });
</script>
</body>
</html>
