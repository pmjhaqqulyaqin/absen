<?php
require_once 'includes/config.php';
unset($_SESSION['kepsek_id'], $_SESSION['kepsek_nama']);
header('Location: '.BASE_URL.'portal_kepsek_login.php'); exit;
