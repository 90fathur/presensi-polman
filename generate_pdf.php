<?php
require 'vendor/setasign/fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();

// Kop Surat (Header Placeholder)
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 7, 'PEMERINTAH KABUPATEN POLEWALI MANDAR', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 7, 'DINAS KOMUNIKASI, INFORMATIKA, STATISTIK DAN PERSANDIAN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Jl. Manunggal No. 11, Pekkabata, Kec. Polewali, Kab. Polewali Mandar, Sulawesi Barat 91315', 0, 1, 'C');
$pdf->Line(10, 32, 200, 32);
$pdf->Line(10, 33, 200, 33);
$pdf->Ln(10);

// Judul
$pdf->SetFont('Arial', 'BU', 14);
$pdf->Cell(0, 10, 'SURAT KUASA / LETTER OF AUTHORIZATION', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 5, 'Nomor: 800/      /DISKOMINFO-SP/VII/2026', 0, 1, 'C');
$pdf->Ln(10);

// Konten
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, "Yang bertanda tangan di bawah ini / The undersigned below:");
$pdf->Ln(2);

$pdf->Cell(40, 6, 'Nama / Name'); $pdf->Cell(5, 6, ':'); $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(0, 6, 'ANDI HIMAWAN JASIN ST.M.Si, ST', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 6, 'NIP / Employee ID'); $pdf->Cell(5, 6, ':'); $pdf->Cell(0, 6, '19750215 200502 1 002', 0, 1);
$pdf->Cell(40, 6, 'Pangkat / Rank'); $pdf->Cell(5, 6, ':'); $pdf->Cell(0, 6, 'Pembina Tingkat I, IV/b', 0, 1);
$pdf->Cell(40, 6, 'Jabatan / Position'); $pdf->Cell(5, 6, ':'); $pdf->MultiCell(0, 6, 'Kepala Dinas Komunikasi, Informatika, Statistik dan Persandian (Kominfo SP) Kabupaten Polewali Mandar');
$pdf->Cell(40, 6, 'Instansi / Agency'); $pdf->Cell(5, 6, ':'); $pdf->Cell(0, 6, 'Pemerintah Kabupaten Polewali Mandar', 0, 1);
$pdf->Ln(5);

$pdf->MultiCell(0, 6, "Bertindak atas nama Pemerintah Kabupaten Polewali Mandar, dengan ini memberikan izin, wewenang, dan persetujuan resmi kepada:\nActing on behalf of the Government of Polewali Mandar Regency, hereby grants official permission, authority, and consent to:");
$pdf->Ln(2);

$pdf->Cell(50, 6, 'Nama Dev / Dev Name'); $pdf->Cell(5, 6, ':'); $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(0, 6, 'Mandarlink Group', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(50, 6, 'Email Dev / Dev Email'); $pdf->Cell(5, 6, ':'); $pdf->Cell(0, 6, 'cv.mandarlinkgroup@gmail.com', 0, 1);
$pdf->Ln(5);

$pdf->MultiCell(0, 6, "Untuk menggunakan nama instansi kami dan mempublikasikan, mengelola, serta mendistribusikan aplikasi resmi pemerintah kami di platform Google Play Store dengan rincian aplikasi sebagai berikut:\nTo use our agency's name and to publish, manage, and distribute our official government application on the Google Play Store platform with the following application details:");
$pdf->Ln(2);

$pdf->Cell(50, 6, 'Aplikasi / App Name'); $pdf->Cell(5, 6, ':'); $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(0, 6, 'Presensi Polman 2.0', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(50, 6, 'App ID / Package'); $pdf->Cell(5, 6, ':'); $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(0, 6, 'com.mandarlink.presensi', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(5);

$pdf->MultiCell(0, 6, "Kami mengonfirmasi bahwa aplikasi ini adalah aplikasi resmi milik Pemerintah Kabupaten Polewali Mandar dan bukan merupakan peniruan identitas (impersonation). Akun pengembang yang disebutkan di atas bertindak sebagai mitra/pengembang resmi kami.\nWe confirm that this application is the official property of the Government of Polewali Mandar Regency and does not constitute impersonation. The developer account mentioned above acts as our official developer/partner.");
$pdf->Ln(2);

$pdf->MultiCell(0, 6, "Demikian surat kuasa ini dibuat dengan sebenar-benarnya untuk digunakan sebagaimana mestinya, khususnya untuk keperluan verifikasi kelayakan (Advance Notice) pada kebijakan Google Play.\nThis letter of authorization is made truthfully to be used properly, especially for the purpose of Advance Notice verification on Google Play policies.");
$pdf->Ln(10);

// Tanda Tangan
$pdf->Cell(110, 6, ''); $pdf->Cell(0, 6, 'Polewali Mandar, 11 Juli 2026', 0, 1, 'C');
$pdf->Cell(110, 6, ''); $pdf->Cell(0, 6, 'Kepala Dinas Kominfo SP,', 0, 1, 'C');
$pdf->Ln(25);
$pdf->SetFont('Arial', 'BU', 11);
$pdf->Cell(110, 6, ''); $pdf->Cell(0, 6, 'ANDI HIMAWAN JASIN ST.M.Si, ST', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(110, 6, ''); $pdf->Cell(0, 6, 'NIP. 19750215 200502 1 002', 0, 1, 'C');

$pdf->Output('F', 'Surat_Kuasa_Google_Play_Polman.pdf');
echo "PDF Generated successfully.";
