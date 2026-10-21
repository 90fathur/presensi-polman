<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1/presensi-polman/public/laporan_tukin/ajax_list"); // maybe host issue
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "bulan=06&tahun=2026&id_opd=193&draw=1&start=0&length=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
print_r(curl_getinfo($ch));
echo "\nERROR: " . curl_error($ch) . "\n";
echo "OUTPUT: " . $server_output;
curl_close($ch);
