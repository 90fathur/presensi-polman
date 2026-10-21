<?php
$mysqli = new mysqli("localhost", "sql_presensi_pol", "29485ccdeaf7e", "sql_presensi_pol");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$query = "SELECT nip, fcm_token FROM pegawai WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 10;";
$result = $mysqli->query($query);
$count = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo $row['nip'] . " -> " . substr($row['fcm_token'], 0, 20) . "...\n";
    }
} else {
    echo "Query error: " . $mysqli->error . "\n";
}
if ($count == 0) echo "NO FCM TOKENS FOUND IN DATABASE!\n";

$mysqli->close();
?>
