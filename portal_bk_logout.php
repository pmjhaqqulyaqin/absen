<?php
require_once 'includes/config.php';
unset($_SESSION['bk_id'], $_SESSION['bk_nama'], $_SESSION['bk_nip']);
session_destroy();
header('Location: '.BASE_URL.'portal_bk_login.php');
exit;
