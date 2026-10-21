<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['bulan'] = '06';
$_POST['tahun'] = '2026';
$_POST['id_opd'] = '203';
$_POST['length'] = '10';
$_POST['start'] = '0';
$_POST['draw'] = '1';

// We must override the $_SERVER['REQUEST_URI'] to fake the routing
$_SERVER['REQUEST_URI'] = '/presensi-polman/public/laporan_tukin/ajax_list';
$_SERVER['SCRIPT_NAME'] = '/presensi-polman/public/index.php';

// Temporarily define a constant so LaporanTukin can bypass auth
define('CLI_DEBUG_TEST', true);

ob_start();
chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';
$output = ob_get_clean();
echo $output;
