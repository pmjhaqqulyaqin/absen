<?php
require_once 'includes/config.php';
unset($_SESSION['bk_id'], $_SESSION['bk_nama'], $_SESSION['bk_nip'], $_SESSION['bk_source']);
session_destroy();
header('Location: '.BASE_URL.'index.php');
exit;
