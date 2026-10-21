<?php
require 'vendor/autoload.php';
$env = parse_ini_file('.env');
$db = new PDO('mysql:host=' . $env['database.default.hostname'] . ';dbname=' . $env['database.default.database'], $env['database.default.username'], $env['database.default.password']);
$stmt = $db->query("SELECT p.nama_lengkap, o.nama_opd, COUNT(pr.id_presensi) as jml FROM presensi pr JOIN pegawai p ON pr.nip = p.nip JOIN opd o ON p.id_opd = o.id_opd WHERE (pr.status_masuk LIKE '%Hadir%' OR pr.status_masuk LIKE '%Istimewa%') AND MONTH(pr.tanggal) = 6 AND YEAR(pr.tanggal) = 2026 GROUP BY p.nip ORDER BY jml DESC LIMIT 15");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
