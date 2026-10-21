<?php
require 'vendor/autoload.php';
$db = \Config\Database::connect();
$tables = $db->listTables();
foreach ($tables as $table) {
    echo $table . "\n";
}
