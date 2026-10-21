<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://presensi-polman.test/laporan_tukin/ajax_list?cli_debug_test=1");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "bulan=06&tahun=2026&id_opd=203&draw=1&start=0&length=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);
print_r($output);
