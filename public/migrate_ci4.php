<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(__DIR__ . '/../');
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$db = \Config\Database::connect();

try {
    $db->query("ALTER TABLE pegawai ADD COLUMN is_bercadar TINYINT(1) DEFAULT 0");
    echo "Column 'is_bercadar' added successfully.\n";
} catch (\Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
