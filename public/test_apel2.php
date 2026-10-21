<?php
$db = new mysqli('localhost', 'root', '', 'sql_presensi_pol');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT id_apel, nama_apel, latitude, longitude, radius FROM apel ORDER BY id_apel DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
