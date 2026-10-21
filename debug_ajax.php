<?php
// Simulate the ajax request using the same environment without going through CI4 HTTP routing
// Actually we can just run CI4 via CLI request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['bulan'] = '06';
$_POST['tahun'] = '2026';
$_POST['id_opd'] = '193';
$_POST['draw'] = '1';
$_POST['start'] = '0';
$_POST['length'] = '10';

// Change dir to public
chdir(__DIR__ . '/public');
// require index.php but capture output
ob_start();
require 'index.php';
$out = ob_get_clean();
echo "--- OUTPUT BEGIN ---\n";
echo $out;
echo "\n--- OUTPUT END ---\n";
