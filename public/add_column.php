<?php
$mysqli = new mysqli("localhost", "root", "", "sql_presensi_pol");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$query = "ALTER TABLE pegawai ADD COLUMN is_bercadar TINYINT(1) DEFAULT 0";
if ($mysqli->query($query) === TRUE) {
    echo "Column is_bercadar added successfully!";
} else {
    echo "Error adding column: " . $mysqli->error;
}

$mysqli->close();
?>
