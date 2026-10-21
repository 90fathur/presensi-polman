<?php
require 'system/bootstrap.php';
$db = \Config\Database::connect();
$res = $db->query("SELECT id_lokasi FROM pegawai WHERE nip='197502152005021002'")->getRowArray();
echo "RESULT: " . json_encode($res);
