<?php
require_once 'includes/config.php';
$role = $_GET['role'] ?? 'siswa';
session_destroy();
header('Location: '.BASE_URL.'portal_login.php?role='.$role);
exit;
