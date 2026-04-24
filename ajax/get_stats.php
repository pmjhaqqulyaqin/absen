<?php
require_once __DIR__.'/../includes/config.php';
header('Content-Type: application/json');
echo json_encode(get_stats_hari_ini());
