<?php
require 'vendor/autoload.php';

// Bootstrap CI4 minimally
$pathsConfig = new \Config\Paths();
require rtrim($pathsConfig->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = \CodeIgniter\Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

try {
    $db->query("ALTER TABLE `opd` ADD COLUMN `is_koreksi_open` TINYINT(1) NOT NULL DEFAULT 0 AFTER `radius`");
    echo "Added is_koreksi_open to opd table.\n";
} catch (\Exception $e) {
    echo "Warning (opd): " . $e->getMessage() . "\n";
}

try {
    $db->query("CREATE TABLE `koreksi_presensi` (
      `id_koreksi` INT(11) NOT NULL AUTO_INCREMENT,
      `id_pegawai` INT(11) NOT NULL,
      `id_opd` INT(11) NOT NULL,
      `tanggal` DATE NOT NULL,
      `keterangan` VARCHAR(255) DEFAULT NULL,
      `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
      `created_by` INT(11) NOT NULL COMMENT 'ID Admin OPD yang mengajukan',
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_koreksi`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table koreksi_presensi.\n";
} catch (\Exception $e) {
    echo "Warning (koreksi_presensi): " . $e->getMessage() . "\n";
}
