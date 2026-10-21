<?php
define('FCPATH', __DIR__ . '/public/');
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

// Fake the request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['bulan'] = '06';
$_POST['tahun'] = '2026';
$_POST['id_opd'] = '203';
$_POST['length'] = '10';
$_POST['start'] = '0';
$_POST['draw'] = '1';

// Setup request and session
$request = \Config\Services::request();
$session = \Config\Services::session();
$session->set('level', 'Superadmin');

$controller = new \App\Controllers\LaporanTukin();
$response = $controller->ajax_list();
echo $response->getBody();
