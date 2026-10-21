<?php
$db = new mysqli('localhost', 'root', '', 'presensi_polman');
$res = $db->query("SELECT id_apel, nama_apel, tanggal, latitude, longitude, radius FROM apel ORDER BY id_apel DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
