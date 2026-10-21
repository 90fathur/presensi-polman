<?php
$pdo = new PDO('mysql:host=localhost;dbname=presensi_polman', 'root', '');
$stmt = $pdo->query("SELECT id_lokasi FROM pegawai WHERE nip='197502152005021002'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "ID_LOKASI_RAW: '" . $row['id_lokasi'] . "'\n";
