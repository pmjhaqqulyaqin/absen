<?php
require_once '../includes/config.php';
cek_login();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_siswa.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($out, ['NIS', 'Nama', 'Kelas']);
fputcsv($out, ['2024001', 'Ahmad Fauzi', 'X-A']);
fputcsv($out, ['2024002', 'Budi Santoso', 'X-B']);
fputcsv($out, ['2024003', 'Citra Dewi', 'XI-IPA-1']);

fclose($out);
?>
