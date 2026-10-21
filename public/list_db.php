<?php
$m = new mysqli('localhost', 'root', '');
$r = $m->query('SHOW DATABASES;');
while ($row = $r->fetch_assoc()) echo $row['Database'] . "\n";
