<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo "<h3>Simpan Kunci Ini Baik-Baik:</h3>";
echo "<b>Public Key:</b><br><textarea style='width:100%;'> " . $keys['publicKey'] . "</textarea><br><br>";
echo "<b>Private Key:</b><br><textarea style='width:100%;'> " . $keys['privateKey'] . "</textarea>";