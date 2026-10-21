<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['bulan'] = '06';
$_POST['tahun'] = '2026';
$_POST['id_opd'] = '193';
$_POST['draw'] = '1';
$_POST['start'] = '0';
$_POST['length'] = '10';

// Bootstrap CI4
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . 'public/index.php';
