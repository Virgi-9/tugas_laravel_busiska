<?php
// partials/navbar.php
// Variabel opsional yang bisa di-set sebelum include:
//   $navbar_scrolled  = true   → navbar langsung solid (untuk halaman non-hero)
//   $navbar_links     = false  → sembunyikan menu anchor (untuk halaman non-index)
$navbar_scrolled = $navbar_scrolled ?? false;
$navbar_links    = $navbar_links    ?? true;
?>
<nav id="navbar" <?php echo $navbar_scrolled ? 'class="scrolled"' : ''; ?>>
    <a href="index.php" class="logo-container">
        <div class="logo-icon">⚙</div>
        <div class="logo-text">AutoPro</div>
    </a>

    <?php if ($navbar_links): ?>
    <ul class="nav-menu">
        <li><a href="index.php#layanan">Layanan</a></li>
        <li><a href="index.php#sparepart">Sparepart</a></li>
        <li><a href="index.php#cara-kerja">Cara Kerja</a></li>
        <li><a href="index.php#kenapa-kami">Kenapa Kami</a></li>
        <li><a href="index.php#kontak">Kontak</a></li>
    </ul>
    <?php endif; ?>

    <div class="nav-actions">
        <?php if (isset($_SESSION['nama'])): ?>
            <a href="booking.php" class="btn-nav">🔧 Booking Sekarang</a>
            <div class="nav-user-dropdown">
                <button class="nav-user-btn" id="userDropBtn">
                    <div class="nav-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars(explode(' ', $_SESSION['nama'])[0]); ?></span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="2,4 6,8 10,4"/></svg>
                </button>
                <div class="nav-dropdown-menu" id="userDropMenu">
                    <div class="nav-drop-header">
                        <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                        <span><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    </div>
                    <a href="booking.php" class="nav-drop-item">📋 Booking Servis</a>
                    <a href="booking.php" class="nav-drop-item">🕐 Riwayat Booking</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'User'): ?>
                        <a href="dashboard.php" class="nav-drop-item">⚙️ Dashboard Admin</a>
                    <?php endif; ?>
                    <div class="nav-drop-divider"></div>
                    <a href="logout.php" class="nav-drop-item nav-drop-logout">🚪 Keluar</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php"    class="btn-nav-ghost">Masuk</a>
            <a href="register.php" class="btn-nav">Daftar Gratis</a>
        <?php endif; ?>
    </div>

    <button class="nav-hamburger" id="hamburger">&#9776;</button>
</nav>

<script>
(function () {
    // Scroll effect — hanya aktif kalau navbar belum scrolled dari awal
    <?php if (!$navbar_scrolled): ?>
    window.addEventListener('scroll', function () {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
    });
    <?php endif; ?>

    // Hamburger
    var ham = document.getElementById('hamburger');
    if (ham) {
        ham.addEventListener('click', function () {
            document.querySelector('.nav-menu') && document.querySelector('.nav-menu').classList.toggle('open');
        });
    }

    // Dropdown user
    var dropBtn  = document.getElementById('userDropBtn');
    var dropMenu = document.getElementById('userDropMenu');
    if (dropBtn && dropMenu) {
        dropBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropMenu.classList.toggle('open');
            dropBtn.classList.toggle('active');
        });
        document.addEventListener('click', function () {
            dropMenu.classList.remove('open');
            dropBtn.classList.remove('active');
        });
    }
})();
</script>
