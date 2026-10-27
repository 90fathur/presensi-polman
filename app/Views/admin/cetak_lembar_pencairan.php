<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar_Rekomendasi_Pencairan_TPP_<?= str_replace(' ', '_', $p['nama_opd']) ?>_<?= $p['bulan'] ?>_<?= $p['tahun'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Times New Roman', Times, serif;
            color: #000;
        }
        .page {
            background: white;
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 25mm 20mm 20mm 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .kop-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }
        .kop-title {
            text-align: center;
            line-height: 1.2;
        }
        .table-rincian td, .table-rincian th {
            padding: 6px 10px;
            font-size: 13px;
        }
        .table-border-dark td, .table-border-dark th {
            border: 1px solid #000 !important;
        }
        @media print {
            body {
                background: white !important;
            }
            .page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 15mm !important;
                width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOMBOL AKSI CETAK -->
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 shadow-sm rounded-pill fw-bold">
            <i class="fas fa-print me-2"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary px-3 py-2 shadow-sm rounded-pill ms-2">
            Tutup
        </button>
    </div>

    <div class="page">
        <!-- KOP SURAT PEMKAB POLMAN -->
        <div class="kop-surat d-flex align-items-center">
            <img src="https://polmankab.go.id//assets/uploads/summernote/SUMMER_0_1734504076.png" class="kop-logo me-3" alt="Logo Pemkab Polman">
            <div class="kop-title flex-grow-1">
                <h5 class="fw-bold mb-0" style="letter-spacing: 1px;">PEMERINTAH KABUPATEN POLEWALI MANDAR</h5>
                <h6 class="fw-bold mb-0">BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA</h6>
                <div class="small" style="font-size: 11px;">
                    Jl. Manunggal No. 11, Pekkabata, Kec. Polewali, Kabupaten Polewali Mandar, Sulawesi Barat 91315
                </div>
            </div>
        </div>

        <!-- JUDUL DOKUMEN -->
        <div class="text-center my-4">
            <h6 class="fw-bold text-decoration-underline mb-1" style="font-size: 15px; letter-spacing: 0.5px;">
                LEMBAR VERIFIKASI REKOMENDASI & PENCAIRAN TPP ASN
            </h6>
            <div style="font-size: 13px;">
                Nomor Rekomendasi: <b><?= esc($p['no_rekomendasi'] ?? 'Menunggu Penerbitan') ?></b>
            </div>
        </div>

        <?php
        $bln_indo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $nama_bln = $bln_indo[$p['bulan']] ?? $p['bulan'];
        ?>

        <!-- IDENTITAS PENGAJUAN -->
        <table class="table table-borderless table-sm mb-3" style="font-size: 13px;">
            <tr>
                <td style="width: 25%;">Unit Kerja / OPD</td>
                <td style="width: 2%;">:</td>
                <td style="width: 73%;" class="fw-bold"><?= esc($p['nama_opd']) ?></td>
            </tr>
            <tr>
                <td>Periode Pembayaran</td>
                <td>:</td>
                <td><b><?= $nama_bln ?> <?= $p['tahun'] ?></b></td>
            </tr>
            <tr>
                <td>Model Perhitungan</td>
                <td>:</td>
                <td>TPP Dinamis (Beban Kerja 60% SKP & Disiplin Kehadiran 40%)</td>
            </tr>
            <tr>
                <td>Status Saat Ini</td>
                <td>:</td>
                <td>
                    <?php if ($p['status'] == 'dicairkan'): ?>
                        <span class="badge bg-success">Telah Dicairkan BPKAD</span>
                    <?php elseif ($p['status'] == 'rekomendasi_bkpsdm'): ?>
                        <span class="badge bg-primary">Direkomendasikan BKPSDM (Siap Cair)</span>
                    <?php elseif ($p['status'] == 'diajukan'): ?>
                        <span class="badge bg-warning text-dark">Dalam Proses Verval BKPSDM</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Draf Pengajuan</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- TABEL RINCIAN ANGGARAN TPP -->
        <table class="table table-bordered table-border-dark table-rincian mb-4">
            <thead class="bg-light text-center">
                <tr>
                    <th style="width: 50px;">NO</th>
                    <th>URAIAN REKAPITULASI TPP</th>
                    <th style="width: 200px;">NILAI / JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>Jumlah ASN Penerima TPP</td>
                    <td class="text-center fw-bold"><?= number_format($p['total_pegawai']) ?> Orang</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>Total Estimasi TPP Kotor (Kinerja 60% + Disiplin 40%)</td>
                    <td class="text-end fw-bold">Rp <?= number_format($p['total_tpp_kotor'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>Total Pemotongan (Potongan Kehadiran & Pajak PPh 21)</td>
                    <td class="text-end fw-bold text-danger">( Rp <?= number_format($p['total_potongan'], 0, ',', '.') ?> )</td>
                </tr>
                <tr class="table-active">
                    <td class="text-center fw-bold">4</td>
                    <td class="fw-bold">TOTAL TPP BERSIH YANG DIBAYARKAN (SIAP CAIR)</td>
                    <td class="text-end fw-bold fs-6">Rp <?= number_format($p['total_tpp_bersih'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- JEJAK AUDIT & VERIFIKASI -->
        <h6 class="fw-bold mb-2" style="font-size: 13px;">RIWAYAT VERIFIKASI & PENERBITAN SP2D:</h6>
        <table class="table table-bordered table-border-dark table-sm mb-4" style="font-size: 12px;">
            <thead class="bg-light">
                <tr class="text-center">
                    <th>Tahapan</th>
                    <th>Penanggung Jawab</th>
                    <th>Tanggal & Waktu</th>
                    <th>Nomor Dokumen / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>1. Verifikasi OPD</b></td>
                    <td><?= esc($p['diajukan_oleh'] ?? 'Admin OPD') ?></td>
                    <td class="text-center"><?= !empty($p['tgl_diajukan']) ? date('d-m-Y H:i', strtotime($p['tgl_diajukan'])) : '-' ?></td>
                    <td><?= !empty($p['catatan_opd']) ? 'Catatan: ' . esc($p['catatan_opd']) : 'Usulan berkas presensi lengkap' ?></td>
                </tr>
                <tr>
                    <td><b>2. Verval & Rekomendasi BKPSDM</b></td>
                    <td><?= esc($p['verval_oleh_bkpsdm'] ?? 'Tim Verval BKPSDM') ?></td>
                    <td class="text-center"><?= !empty($p['tgl_verval_bkpsdm']) ? date('d-m-Y H:i', strtotime($p['tgl_verval_bkpsdm'])) : '-' ?></td>
                    <td>
                        No. Rekom: <b><?= esc($p['no_rekomendasi'] ?? '-') ?></b>
                        <?= !empty($p['catatan_bkpsdm']) ? '<br><small>(' . esc($p['catatan_bkpsdm']) . ')</small>' : '' ?>
                    </td>
                </tr>
                <tr>
                    <td><b>3. Pencairan BPKAD (SP2D)</b></td>
                    <td><?= esc($p['dicairkan_oleh_bpkad'] ?? 'Perbendaharaan BPKAD') ?></td>
                    <td class="text-center"><?= !empty($p['tgl_cair_bpkad']) ? date('d-m-Y', strtotime($p['tgl_cair_bpkad'])) : '-' ?></td>
                    <td>
                        No. SP2D: <b><?= esc($p['no_sp2d'] ?? 'Menunggu Penerbitan SP2D') ?></b>
                        <?= !empty($p['catatan_bpkad']) ? '<br><small>(' . esc($p['catatan_bpkad']) . ')</small>' : '' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- TANDA TANGAN / PARAF 3 PIHAK -->
        <div class="row text-center mt-5" style="font-size: 13px;">
            <div class="col-4">
                <div>Diajukan Oleh:</div>
                <div class="fw-bold">Pengelola Kepegawaian OPD</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold text-decoration-underline"><?= esc($p['diajukan_oleh'] ?? '..................................') ?></div>
                <div style="font-size: 11px;">Admin Instansi OPD</div>
            </div>

            <div class="col-4">
                <div>Diverifikasi & Direkomendasikan:</div>
                <div class="fw-bold">BKPSDM Kab. Polewali Mandar</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold text-decoration-underline"><?= esc($p['verval_oleh_bkpsdm'] ?? '..................................') ?></div>
                <div style="font-size: 11px;">Verifikator TPP BKPSDM</div>
            </div>

            <div class="col-4">
                <div>Disetujui untuk Dicairkan:</div>
                <div class="fw-bold">BPKAD Kab. Polewali Mandar</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold text-decoration-underline"><?= esc($p['dicairkan_oleh_bpkad'] ?? '..................................') ?></div>
                <div style="font-size: 11px;">Kuasa BUD / Pejabat Perbendaharaan</div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top text-muted small text-center" style="font-size: 10px;">
            Dicetak secara otomatis melalui Sistem Presensi AI Kabupaten Polewali Mandar pada <?= date('d/m/Y H:i:s') ?> WITA.
        </div>
    </div>

</body>
</html>

