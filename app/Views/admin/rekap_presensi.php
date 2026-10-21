<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    /* KELOMPOK WARNA BADGE STATUS MURNI CSS */
    .bg-st-istimewa {
        background-color: #155724 !important;
        color: white !important;
    }

    .bg-st-baik {
        background-color: #28a745 !important;
        color: white !important;
    }

    .bg-st-cukup {
        background-color: #8ce196 !important;
        color: #212529 !important;
    }

    .bg-st-telat {
        background-color: #ffda6a !important;
        color: #212529 !important;
    }

    .bg-st-bina {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .bg-st-teguran {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .bg-st-izin {
        background-color: #6f42c1 !important;
        color: white !important;
    }

    .bg-st-apel {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .bg-st-cepul {
        background-color: #fd7e14 !important;
        color: white !important;
    }

    .bg-st-selesai {
        background-color: #0d6efd !important;
        color: white !important;
    }
    
    /* [FITUR BARU]: WARNA KHUSUS UNTUK STATUS STANDBY / CITO */
    .bg-st-standby {
        background-color: #0dcaf0 !important;
        color: #212529 !important;
    }

    .bg-st-default {
        background-color: #6c757d !important;
        color: white !important;
    }

    .custom-table th,
    .custom-table td {
        border: 1px solid #dee2e6;
        padding: 5px 8px;
    }

    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="card shadow-sm border-0 mb-4 no-print" id="form-filter-area">
    <div class="card-body">
        <form action="" method="GET" class="row g-2 align-items-end">

            <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin'): ?>
                <div class="col-md-2">
                    <label class="small fw-bold">Instansi/Kategori</label>
                    <select name="id_opd" class="form-select form-select-sm shadow-none" required>
                        <option value="">-- Pilih --</option>

                        <optgroup label="REKAP GABUNGAN KHUSUS">
                            <?php if (!empty($list_gabungan)): foreach ($list_gabungan as $gabungan): ?>
                                    <option value="gabungan||<?= $gabungan ?>" <?= ($selected_opd == 'gabungan||' . $gabungan) ? 'selected' : '' ?>>
                                        GABUNGAN <?= $gabungan ?>
                                    </option>
                            <?php endforeach;
                            endif; ?>
                        </optgroup>

                        <optgroup label="REKAP SELURUH KABUPATEN">
                            <option value="rekap_eselon_2" <?= ($selected_opd == 'rekap_eselon_2') ? 'selected' : '' ?>>--- ESELON II (KEPALA DINAS/BADAN) ---</option>
                            <option value="rekap_eselon_3" <?= ($selected_opd == 'rekap_eselon_3') ? 'selected' : '' ?>>--- ESELON III (SEKRETARIS/KABID) ---</option>
                            <option value="rekap_eselon_4" <?= ($selected_opd == 'rekap_eselon_4') ? 'selected' : '' ?>>--- ESELON IV (KASI/KASUBAG) ---</option>
                            <option value="rekap_eselon_gabungan" <?= ($selected_opd == 'rekap_eselon_gabungan') ? 'selected' : '' ?>>GABUNGAN ESELON II, III, & IV</option>
                        </optgroup>

                        <optgroup label="PER UNIT KERJA (OPD)">
                            <?php foreach ($list_opd as $o): ?>
                                <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>>
                                    <?= $o['nama_opd'] ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Shift / Jadwal</label>
                <select name="id_jam_kerja" class="form-select form-select-sm shadow-none">
                    <option value="">-- Semua Shift --</option>
                    <option value="lepas_jaga" <?= (isset($selected_shift) && $selected_shift == 'lepas_jaga') ? 'selected' : '' ?>>Lepas Jaga (Libur Shift)</option>
                    <?php if (isset($list_jam_kerja)): foreach ($list_jam_kerja as $jk): ?>
                            <option value="<?= $jk['id_jam_kerja'] ?>" <?= (isset($selected_shift) && $selected_shift == $jk['id_jam_kerja']) ? 'selected' : '' ?>>
                                <?= $jk['nama_jadwal'] ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold">Status ASN</label>
                <select name="status_asn" class="form-select border-secondary">
                    <option value="">-- Semua Status ASN --</option>
                    <option value="PNS" <?= (isset($selected_status_asn) && $selected_status_asn == 'PNS') ? 'selected' : '' ?>>PNS</option>
                    <option value="PPPK" <?= (isset($selected_status_asn) && $selected_status_asn == 'PPPK') ? 'selected' : '' ?>>PPPK</option>
                    <option value="PPPKPW" <?= (isset($selected_status_asn) && $selected_status_asn == 'PPPKPW') ? 'selected' : '' ?>>PPPKPW</option>
                    <option value="TENAGA KONTRAK" <?= (isset($selected_status_asn) && $selected_status_asn == 'TENAGA KONTRAK') ? 'selected' : '' ?>>TENAGA KONTRAK</option>
                    <option value="KONTRAK BLUD" <?= (isset($selected_status_asn) && $selected_status_asn == 'KONTRAK BLUD') ? 'selected' : '' ?>>KONTRAK BLUD</option>
                    <option value="NON ASN LAINNYA" <?= (isset($selected_status_asn) && $selected_status_asn == 'NON ASN LAINNYA') ? 'selected' : '' ?>>NON ASN LAINNYA</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold">Bulan</label>
                <select name="bulan" class="form-select form-select-sm shadow-none">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($bulan == str_pad($m, 2, '0', STR_PAD_LEFT)) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label class="small fw-bold">Tahun</label>
                <input type="number" name="tahun" class="form-control form-control-sm shadow-none" value="<?= $tahun ?>">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
                <button type="button" id="btn-download-pdf" onclick="generateDirectPDF()" class="btn btn-danger btn-sm w-100 rounded-pill shadow-sm" <?= empty($rekap) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
            </div>

        </form>
    </div>
</div>

<?php if (!empty($rekap)): ?>
    <div class="laporan-container" id="area-cetak-pdf">
        <?php foreach ($rekap as $pegawai): ?>
            <div class="card card-pegawai mb-5 shadow-sm">
                <div class="header-pegawai bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold d-block text-uppercase text-dark"><?= $pegawai['nama'] ?></span>
                        <span class="small text-muted">
                            NIP. <?= $pegawai['nip'] ?> | <?= $pegawai['jabatan'] ?>
                            <?php if (strpos($selected_opd, 'rekap_eselon') !== false || strpos($selected_opd, 'gabungan||') === 0): ?>
                                <span class="badge bg-dark ms-1 fw-normal text-white" style="font-size: 0.6rem;"><?= $pegawai['nama_opd'] ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <?php
                        $total_hadir = 0;
                        $total_telat = 0;
                        $total_cuti = 0;
                        $total_standby = 0;
                        
                        foreach ($pegawai['harian'] as $h) {
                            $st_cek = strtolower($h['status']);
                            if (strpos($st_cek, 'baik') !== false || strpos($st_cek, 'istimewa') !== false || strpos($st_cek, 'apel') !== false || strpos($st_cek, 'telat') !== false || strpos($st_cek, 'bina') !== false || strpos($st_cek, 'tegur') !== false) {
                                $total_hadir++;
                            }
                            if ($h['telat'] > 0) $total_telat++;
                            if (strpos($st_cek, 'cuti') !== false) $total_cuti++;
                            if (strpos($st_cek, 'standby') !== false) $total_standby++;
                        }
                        
                        // Cek apakah ada data CITO yang dikirim dari controller
                        $cito_kali = $pegawai['cito_kali'] ?? 0;
                        $cito_menit = $pegawai['cito_menit'] ?? 0;
                        $cito_jam = floor($cito_menit / 60);
                        $sisa_menit = $cito_menit % 60;
                        $cito_format = $cito_jam . 'j ' . $sisa_menit . 'm';
                        ?>
                        <div class="d-flex gap-1 print-hide-badges flex-wrap justify-content-end mt-1">
                            <span class="badge bg-white text-dark border shadow-xs small">Hadir: <?= $total_hadir ?></span>
                            <span class="badge bg-white text-primary border shadow-xs small">Cuti: <?= $total_cuti ?></span>
                            <span class="badge bg-white text-danger border shadow-xs small">Telat: <?= $total_telat ?>x</span>
                            
                            <?php if($total_standby > 0 || $cito_kali > 0): ?>
                                <span class="badge bg-info text-dark border shadow-xs small">Siaga: <?= $total_standby ?> hari</span>
                                <span class="badge bg-danger text-white border border-danger shadow-xs small"><i class="fas fa-ambulance me-1"></i> Cito: <?= $cito_kali ?>x (<?= $cito_format ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-0 m-0">
                    <table class="table table-bordered table-sm small align-middle mb-0 text-center custom-table" style="font-size: 0.75rem;">
                        <thead class="bg-secondary text-white small text-uppercase">
                            <tr>
                                <th width="12%">Tgl</th>
                                <th width="15%">Masuk</th>
                                <th width="15%">Siang</th>
                                <th width="15%">Pulang</th>
                                <th width="10%">Telat</th>
                                <th width="33%">Keterangan / Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pegawai['harian'] as $h): ?>
                                <?php
                                $st_lower = strtolower($h['status']);
                                $is_libur = (strpos($st_lower, 'libur') !== false || strpos($st_lower, 'minggu') !== false);
                                $is_cuti  = (strpos($st_lower, 'cuti') !== false);

                                $row_style = '';
                                if ($is_libur) $row_style = 'background-color: #f8f9fa;';
                                if ($is_cuti)  $row_style = 'background-color: #f0f7ff;';

                                $cls_m = 'bg-st-default';
                                if (strpos($st_lower, 'istimewa') !== false || $st_lower == 'sangat baik') $cls_m = 'bg-st-istimewa';
                                elseif ($st_lower == 'baik') $cls_m = 'bg-st-baik';
                                elseif (strpos($st_lower, 'cukup') !== false) $cls_m = 'bg-st-cukup';
                                elseif (strpos($st_lower, 'terlambat') !== false) $cls_m = 'bg-st-telat';
                                elseif (strpos($st_lower, 'pembinaan') !== false) $cls_m = 'bg-st-bina';
                                elseif (strpos($st_lower, 'teguran') !== false) $cls_m = 'bg-st-teguran';
                                elseif (strpos($st_lower, 'apel') !== false) $cls_m = 'bg-st-apel';
                                elseif (strpos($st_lower, 'izin') !== false || strpos($st_lower, 'sakit') !== false || strpos($st_lower, 'luar') !== false) $cls_m = 'bg-st-izin';
                                elseif (strpos($st_lower, 'standby') !== false) $cls_m = 'bg-st-standby border border-info'; // WARNA BARU CITO/STANDBY
                                elseif ($is_libur) $cls_m = 'text-muted border';
                                elseif ($is_cuti) $cls_m = 'text-primary border border-primary';

                                $lbl_p = strtolower($h['status_pulang_label'] ?? '');
                                $cls_p = 'bg-st-default';
                                if ($lbl_p == 'cepat pulang') $cls_p = 'bg-st-cepul';
                                elseif ($lbl_p == 'selesai') $cls_p = 'bg-st-selesai';
                                ?>
                                <tr style="<?= $row_style ?>">
                                    <td class="text-start ps-2 fw-bold">
                                        <?= date('d', strtotime($h['tanggal'])) ?>
                                        <span class="text-muted fw-normal ms-1" style="font-size: 0.65rem;"><?= substr($h['hari'], 0, 3) ?></span>

                                        <div class="mt-1 d-block">
                                            <span class="badge bg-light text-dark border border-secondary border-opacity-25" style="font-size: 0.55rem; padding: 3px 6px;">
                                                <?= $h['nama_shift'] ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="<?= ($h['masuk'] != '-') ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $h['masuk'] ?></td>
                                    <td class="<?= ($h['siang'] != '-') ? 'fw-bold text-dark' : 'text-muted' ?>" style="<?= ($h['siang'] != '-') ? 'background-color: rgba(255, 251, 239, 0.5);' : '' ?>">
                                        <?= $h['siang'] ?>
                                    </td>
                                    <td class="<?= ($h['pulang'] != '-') ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $h['pulang'] ?></td>

                                    <td>
                                        <?php if ($h['telat'] > 0): ?>
                                            <span class="text-danger fw-bold small">+<?= $h['telat'] ?>m</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (empty($h['status_pulang_label']) || $is_libur || $is_cuti || strpos($st_lower, 'standby') !== false): ?>
                                            <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 6px 0; white-space: normal;">
                                                <?= strtoupper($h['status']) ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">
                                                    M: <?= $h['status'] ?>
                                                </span>
                                                <span class="badge <?= $cls_p ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">
                                                    P: <?= $h['status_pulang_label'] ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm text-center p-5 mt-4">
        <div class="card-body">
            <i class="fas fa-file-invoice text-light mb-3" style="font-size: 4rem;"></i>
            <h6 class="fw-bold">Belum Ada Data Rekap</h6>
            <p class="text-muted small">Silakan pilih Kategori atau OPD dan Periode Bulan untuk menarik laporan.</p>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function generateDirectPDF() {
        const btn = document.getElementById('btn-download-pdf');
        const originalContent = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sedang Membuat PDF...';
        btn.disabled = true;

        setTimeout(() => {
            try {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                let finalY = 15;

                // 1. KOP SURAT
                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("PEMERINTAH KABUPATEN POLEWALI MANDAR", 105, finalY, {
                    align: "center"
                });
                finalY += 6;

                let judul = "LAPORAN PRESENSI & CUTI PEGAWAI";
                <?php if (strpos($selected_opd, 'rekap_eselon') !== false): ?>
                    judul = "LAPORAN PRESENSI PEJABAT <?= strtoupper(str_replace('rekap_', '', $selected_opd)) ?>";
                <?php elseif (strpos($selected_opd, 'gabungan||') === 0): ?>
                    judul = "LAPORAN GABUNGAN <?= strtoupper(str_replace('gabungan||', '', $selected_opd)) ?>";
                <?php endif; ?>

                doc.setFontSize(12);
                doc.text(judul, 105, finalY, {
                    align: "center"
                });
                finalY += 5;

                doc.setFontSize(10);
                doc.setFont("helvetica", "normal");

                // Tambahkan teks filter Status ASN & Shift ke PDF jika dipilih
                let periodeTxt = "Periode: <?= date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)) ?>";
                <?php if (!empty($selected_status_asn)): ?>
                    periodeTxt += " | Status ASN: <?= strtoupper($selected_status_asn) ?>";
                <?php endif; ?>

                <?php
                $shift_name = "";
                if (!empty($selected_shift)) {
                    if ($selected_shift == 'lepas_jaga') $shift_name = "LEPAS JAGA";
                    else {
                        foreach ($list_jam_kerja ?? [] as $jk) {
                            if ($jk['id_jam_kerja'] == $selected_shift) {
                                $shift_name = strtoupper($jk['nama_jadwal']);
                                break;
                            }
                        }
                    }
                }
                ?>
                <?php if (!empty($shift_name)): ?>
                    periodeTxt += " | Shift: <?= $shift_name ?>";
                <?php endif; ?>

                doc.text(periodeTxt, 105, finalY, {
                    align: "center"
                });
                finalY += 10;

                doc.setLineWidth(0.5);
                doc.line(14, finalY, 196, finalY);
                finalY += 10;

                // 2. DATA PHP -> JAVASCRIPT
                const rekapData = <?= json_encode($rekap ?? []) ?>;

                // 3. LOOPING TABEL PER PEGAWAI
                rekapData.forEach((pegawai) => {
                    // Jika ruang tidak cukup untuk header pegawai, buat halaman baru
                    if (finalY > 250) {
                        doc.addPage();
                        finalY = 15;
                    }

                    // Tulis Header Nama Pegawai
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "bold");
                    doc.text(pegawai.nama.toUpperCase(), 14, finalY);
                    finalY += 5;

                    doc.setFontSize(9);
                    doc.setFont("helvetica", "normal");
                    doc.text(`NIP. ${pegawai.nip} | ${pegawai.jabatan} | ${pegawai.nama_opd}`, 14, finalY);

                    // Hitung Rekap Singkat (Hadir, Cuti, Telat, CITO)
                    let hadir = 0,
                        telat = 0,
                        cuti = 0,
                        standby = 0;
                    pegawai.harian.forEach(h => {
                        let st = h.status.toLowerCase();
                        if (st.includes('baik') || st.includes('istimewa') || st.includes('apel') || st.includes('telat') || st.includes('bina') || st.includes('tegur')) hadir++;
                        if (h.telat > 0) telat++;
                        if (st.includes('cuti')) cuti++;
                        if (st.includes('standby')) standby++;
                    });
                    
                    let citoTxt = "";
                    if (pegawai.cito_kali && pegawai.cito_kali > 0) {
                        let cito_jam = Math.floor(pegawai.cito_menit / 60);
                        let sisa_menit = pegawai.cito_menit % 60;
                        citoTxt = ` | CITO: ${pegawai.cito_kali}x (${cito_jam}j ${sisa_menit}m)`;
                    }

                    doc.setFont("helvetica", "bold");
                    doc.text(`Hadir: ${hadir} | Cuti: ${cuti} | Telat: ${telat}x${citoTxt}`, 196, finalY, {
                        align: "right"
                    });
                    finalY += 4;

                    // Siapkan Data Baris (Rows)
                    let tableBody = [];
                    pegawai.harian.forEach(h => {
                        let tglCol = h.tanggal.split('-')[2] + ' ' + h.hari.substring(0, 3) + (h.nama_shift ? `\n(${h.nama_shift})` : '');
                        let telatTxt = h.telat > 0 ? `+${h.telat}m` : '-';

                        let ketStatus = h.status.toUpperCase();
                        if (h.status_pulang_label && h.status_pulang_label !== 'Belum Absen' && h.status_pulang_label !== '-' && !ketStatus.includes('LIBUR') && !ketStatus.includes('CUTI') && !ketStatus.includes('IZIN') && !ketStatus.includes('SAKIT') && !ketStatus.includes('STANDBY')) {
                            ketStatus = `M: ${h.status}\nP: ${h.status_pulang_label}`;
                        }

                        tableBody.push([tglCol, h.masuk, h.siang, h.pulang, telatTxt, ketStatus]);
                    });

                    // Gambar Tabel
                    doc.autoTable({
                        startY: finalY,
                        head: [
                            ['Tgl', 'Masuk', 'Siang', 'Pulang', 'Telat', 'Keterangan / Status']
                        ],
                        body: tableBody,
                        theme: 'grid',
                        styles: {
                            fontSize: 7,
                            halign: 'center',
                            valign: 'middle'
                        },
                        headStyles: {
                            fillColor: [108, 117, 125],
                            textColor: [255, 255, 255]
                        },
                        columnStyles: {
                            0: {
                                halign: 'left',
                                cellWidth: 22
                            },
                            5: {
                                halign: 'left'
                            }
                        },
                        didDrawPage: function(data) {
                            finalY = data.cursor.y + 12; // Jarak antar pegawai
                        }
                    });
                });

                // 4. TANDA TANGAN (Halaman Terakhir)
                if (finalY > 230) {
                    doc.addPage();
                    finalY = 20;
                }

                doc.setFontSize(10);
                doc.setFont("helvetica", "normal");
                doc.text("Polewali, <?= date('d F Y') ?>", 145, finalY);
                finalY += 5;
                doc.text("Mengetahui,", 145, finalY);
                finalY += 5;

                doc.setFont("helvetica", "bold");
                <?php
                $ttd_jabatan = "Kepala OPD / Pimpinan";
                if ($selected_opd == 'rekap_eselon_2') $ttd_jabatan = "Bupati / Sekretaris Daerah";
                elseif ($selected_opd == 'rekap_eselon_3') $ttd_jabatan = "Sekretaris Daerah / Asisten";
                elseif ($selected_opd == 'rekap_eselon_4') $ttd_jabatan = "Kepala OPD";
                elseif ($selected_opd == 'rekap_eselon_gabungan') $ttd_jabatan = "ESELON II, III, & IV";
                elseif (strpos($selected_opd, 'gabungan||') === 0) $ttd_jabatan = "Kepala Instansi";
                ?>
                doc.text("<?= $ttd_jabatan ?>", 145, finalY);

                finalY += 25;
                doc.text("( ........................................... )", 145, finalY);
                finalY += 5;
                doc.setFont("helvetica", "normal");
                doc.text("NIP. ...........................................", 145, finalY);

                // 5. EKSEKUSI DOWNLOAD
                let fileName = 'Rekap_Presensi_<?= date('F_Y', mktime(0, 0, 0, $bulan, 1, $tahun)) ?>.pdf';
                doc.save(fileName);

                btn.innerHTML = originalContent;
                btn.disabled = false;
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalContent;
                btn.disabled = false;
                Swal.fire('Error', 'Terjadi kesalahan saat membuat PDF.', 'error');
            }
        }, 100);
    }
</script>

<?= $this->endSection() ?>