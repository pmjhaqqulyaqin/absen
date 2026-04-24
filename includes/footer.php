    </div><!-- end content-wrapper -->
</div><!-- end main-content -->

<!-- BOTTOM NAV ADMIN -->
<div class="bottom-nav">
    <a href="<?= BASE_URL ?>dashboard.php" class="bnav-item <?= basename($_SERVER['PHP_SELF'], '.php')=='dashboard'?'active':'' ?>">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="<?= BASE_URL ?>manual.php" class="bnav-item <?= basename($_SERVER['PHP_SELF'], '.php')=='manual'?'active':'' ?>">
        <i class="fas fa-keyboard"></i>
        <span>Manual</span>
    </a>
    <a href="<?= BASE_URL ?>scan.php" class="bnav-item scan-center">
        <div class="scan-circle">
            <i class="fas fa-qrcode"></i>
        </div>
        <span>Scan QR</span>
    </a>
    <a href="<?= BASE_URL ?>rekap_harian.php" class="bnav-item <?= basename($_SERVER['PHP_SELF'], '.php')=='rekap_harian'?'active':'' ?>">
        <i class="fas fa-chart-bar"></i>
        <span>Rekap</span>
    </a>
    <a href="javascript:void(0)" class="bnav-item" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </a>
</div>

<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
