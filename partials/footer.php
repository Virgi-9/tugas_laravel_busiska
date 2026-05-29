<?php
// partials/footer.php
// Variabel opsional:
//   $footer_minimal = true  → tampilkan footer ringkas (untuk halaman booking, dashboard, dll)
$footer_minimal = $footer_minimal ?? false;
?>
<?php if ($footer_minimal): ?>

<footer style="background:#080C12; border-top:1px solid var(--border); padding:20px 8%; text-align:center;">
    <p style="color:#52637A; font-size:.82rem;">&copy; <?php echo date('Y'); ?> AutoPro Workshop. All rights reserved.</p>
</footer>

<?php else: ?>

<footer id="kontak">
    <div class="footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="logo-container" style="margin-bottom:14px; text-decoration:none;">
                <div class="logo-icon">⚙</div>
                <div class="logo-text">AutoPro Workshop</div>
            </a>
            <p>Bengkel terpercaya dengan sistem manajemen digital untuk pengalaman servis yang lebih mudah dan transparan.</p>
        </div>
        <div class="footer-col">
            <h4>Layanan</h4>
            <ul>
                <li>Ganti Oli Mesin</li>
                <li>Tune Up</li>
                <li>Servis Rem</li>
                <li>Servis AC</li>
                <li>Ganti Ban</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Navigasi</h4>
            <ul>
                <li><a href="index.php#layanan">Layanan</a></li>
                <li><a href="index.php#cara-kerja">Cara Kerja</a></li>
                <li><a href="index.php#kenapa-kami">Kenapa Kami</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Daftar</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Kontak</h4>
            <ul>
                <li>📍 Jl. Raya Bengkel No. 99</li>
                <li>📞 (021) 1234-5678</li>
                <li>✉️ info@autopro.com</li>
                <li>🕐 Senin–Sabtu, 08.00–17.00</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> AutoPro Workshop. All rights reserved.</p>
    </div>
</footer>

<script>
// Smooth scroll untuk link anchor di footer
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
});
</script>

<?php endif; ?>
