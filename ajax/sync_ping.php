<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sync_config.php';
cek_login();

header('Content-Type: application/json');

$result = sync_ping();
echo json_encode($result);
?>
