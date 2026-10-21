<?php
$db = new mysqli('localhost', 'root', '', 'presensi_polman');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "--- Pengajuan Absen --- \n";
$res = $db->query("SELECT status_approval, COUNT(*) as c FROM pengajuan_absen GROUP BY status_approval");
while ($row = $res->fetch_assoc()) {
    echo $row['status_approval'] . ": " . $row['c'] . "\n";
}

echo "\n--- Presensi where Kategori Izin/Sakit/TL but Pengajuan is Pending --- \n";
$res = $db->query("
    SELECT pr.nip, pr.tanggal, pr.kategori, pa.status_approval
    FROM presensi pr
    JOIN pengajuan_absen pa ON pr.nip = pa.nip AND pr.tanggal >= pa.tanggal_mulai AND pr.tanggal <= pa.tanggal_selesai
    WHERE pr.kategori IN ('Izin', 'Sakit', 'Tugas Luar')
    AND pa.status_approval = 'Pending'
");
while ($row = $res->fetch_assoc()) {
    echo "NIP: {$row['nip']}, Tgl: {$row['tanggal']}, Kategori: {$row['kategori']}, Status: {$row['status_approval']}\n";
}
