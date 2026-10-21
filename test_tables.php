<?php
$db = mysqli_connect('localhost', 'root', '', 'sql_presensi_pol');
$res = mysqli_query($db, 'SHOW TABLES;');
while ($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
