<?php
$env = parse_ini_file('.env');
$db = new PDO('mysql:host='.$env['database.default.hostname'].';dbname='.$env['database.default.database'], $env['database.default.username'], $env['database.default.password']);
$stmt = $db->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
