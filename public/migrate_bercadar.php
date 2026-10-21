<?php
$mysqli = new mysqli("localhost", "root", "", "sql_presensi_pol");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Try to add column
$sql = "ALTER TABLE pegawai ADD COLUMN is_bercadar TINYINT(1) DEFAULT 0";
if ($mysqli->query($sql) === TRUE) {
    echo "Column 'is_bercadar' added successfully.\n";
} else {
    echo "Error adding column: " . $mysqli->error . "\n";
}

$mysqli->close();
