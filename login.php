<?php
require 'db_connect.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            // User biasa ke homepage, staff ke dashboard
            if ($user['role'] === 'User') {
                header("Location: index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Email atau password salah!";
        }
    } else {
        $error = "Harap isi seluruh field!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoPro - Login</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
</head>
<body class="page-register">

    <!-- Panel Kiri -->
    <div class="left-panel">
        <div class="brand">
            <div class="brand-icon">⚙</div>
            <span class="brand-name">AutoPro Workshop</span>
        </div>
        <div class="feature-item">
            <div class="feature-icon">📋</div>
            <div class="feature-text">
                <h3>Booking Online Mudah</h3>
                <p>Jadwalkan servis kendaraan Anda kapan saja tanpa perlu antri langsung di bengkel.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">🔧</div>
            <div class="feature-text">
                <h3>Teknisi Bersertifikasi</h3>
                <p>Kendaraan Anda ditangani langsung oleh mekanik berpengalaman dan bersertifikasi pabrikan.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">📊</div>
            <div class="feature-text">
                <h3>Pantau Status Real-time</h3>
                <p>Lacak progres pengerjaan kendaraan Anda secara langsung dari dashboard.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon">🛡️</div>
            <div class="feature-text">
                <h3>Garansi Suku Cadang</h3>
                <p>Setiap penggantian sparepart orisinal dilindungi garansi resmi mekanis.</p>
            </div>
        </div>
    </div>

    <!-- Panel Kanan: Form Login -->
    <div class="right-panel">
        <div class="register-card">
            <h2>Selamat Datang</h2>
            <p class="subtitle">Masukkan kredensial akun AutoPro Anda untuk melanjutkan.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-wrap">
                    <input type="email" id="email" name="email" placeholder=" " required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <label for="email">Alamat Email</label>
                </div>

                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>

                <label class="show-pass-row">
                    <input type="checkbox" id="show-pass"> Tampilkan password
                </label>

                <button type="submit" class="btn-login">Masuk Aplikasi</button>
            </form>

            <div class="login-link">Belum punya akun? <a href="register.php">Registrasi Baru</a></div>
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
