<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

$controller = new \App\Controllers\LaporanTukin();
$response = $controller->ajax_list();
echo $response->getBody();
