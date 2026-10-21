<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://presensi-polman.test/laporan_tukin/ajax_list");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "bulan=06&tahun=2026&id_opd=193&draw=1&start=0&length=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
curl_close($ch);
echo $server_output;
