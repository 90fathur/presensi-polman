<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$bulan_list = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];
?>

<style>
    .bg-danger-subtle {
        background-color: #f8d7da !important;
    }

    .bg-success-subtle {
        background-color: #d1e7dd !important;
    }

    .table thead th {
        border: none !important;
        font-size: 0.65rem;
    }

    .table tbody td {
        border-color: #f0f0f0 !important;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }

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
</style>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form action="" method="GET" class="row g-2 align-items-end">
            <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin' || (isset($is_induk_opd) && $is_induk_opd)): ?>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Pilih Unit Kerja / Kategori</label>
                    <select name="id_opd" class="form-select form-select-sm shadow-none border-secondary" required>

                        <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin'): ?>
                            <option value="">-- Pilih Instansi --</option>
                            <optgroup label="[ REKAP GABUNGAN MASSAL ]">
                                <?php if (!empty($list_gabungan)): foreach ($list_gabungan as $gabungan): ?>
                                        <option value="gabungan||<?= $gabungan ?>" <?= ($selected_opd == 'gabungan||' . $gabungan) ? 'selected' : '' ?>>GABUNGAN <?= $gabungan ?></option>
                                <?php endforeach;
                                endif; ?>
                            </optgroup>
                            <optgroup label="[ REKAP STRUKTURAL KABUPATEN ]">
                                <option value="rekap_eselon_2" <?= ($selected_opd == 'rekap_eselon_2') ? 'selected' : '' ?>>ESELON II (KEPALA DINAS)</option>
                                <option value="rekap_eselon_3" <?= ($selected_opd == 'rekap_eselon_3') ? 'selected' : '' ?>>ESELON III (KABID/SEKRETARIS)</option>
                                <option value="rekap_eselon_4" <?= ($selected_opd == 'rekap_eselon_4') ? 'selected' : '' ?>>ESELON IV (KASI/KASUBAG)</option>
                                <option value="rekap_eselon_gabungan" <?= ($selected_opd == 'rekap_eselon_gabungan') ? 'selected' : '' ?>>GABUNGAN ESELON II, III, & IV</option>
                            </optgroup>
                            <optgroup label="[ PER UNIT KERJA (OPD) ]">
                                <?php foreach ($list_opd as $o): ?>
                                    <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>><?= $o['nama_opd'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>

                        <?php else: ?>
                            <option value="<?= session()->get('id_opd') ?>" <?= ($selected_opd == session()->get('id_opd')) ? 'selected' : '' ?>>-- REKAP KANTOR INDUK SAJA --</option>

                            <?php
                            $nama_gab_dinas = '';
                            foreach ($list_gabungan as $gab) {
                                if (strpos($admin_opd_name, $gab) !== false || (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && $gab == 'DINAS KESEHATAN')) {
                                    $nama_gab_dinas = $gab;
                                    break;
                                }
                            }
                            if ($nama_gab_dinas):
                            ?>
                                <option value="gabungan||<?= $nama_gab_dinas ?>" <?= ($selected_opd == 'gabungan||' . $nama_gab_dinas) ? 'selected' : '' ?>>-- REKAP GABUNGAN (INDUK + SELURUH UPTD) --</option>
                            <?php endif; ?>

                            <optgroup label="[ PILIH UPTD DI BAWAH ANDA ]">
                                <?php if (isset($child_opds)): foreach ($child_opds as $c): ?>
                                        <option value="<?= $c['id_opd'] ?>" <?= ($selected_opd == $c['id_opd']) ? 'selected' : '' ?>><?= $c['nama_opd'] ?></option>
                                <?php endforeach;
                                endif; ?>
                            </optgroup>
                        <?php endif; ?>

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
                <button type="button" id="btn-download-pdf" onclick="generateDirectPDF()" class="btn btn-danger btn-sm w-100 rounded-pill shadow-sm" <?= empty($pegawai) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
            </div>

        </form>
    </div>
</div>

<?php if (!empty($selected_opd)): ?>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <span class="badge bg-light text-dark border px-3 py-2 shadow-sm">
            <i class="fas fa-calendar-check text-primary me-2"></i> Hari Kerja Efektif: <strong><?= $hari_kerja ?> Hari</strong>
        </span>
    </div>
<?php endif; ?>

<?php if (!empty($pegawai)): ?>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center small align-middle mb-0" id="tabelRekap" style="font-size: 0.65rem;">
                <thead class="bg-dark text-white">
                    <tr>
                        <th rowspan="2" width="30" class="align-middle border-0">No</th>
                        <th rowspan="2" class="align-middle text-start ps-3 border-0" style="min-width: 170px;">Nama Pegawai / NIP</th>
                        <th rowspan="2" width="40" class="align-middle bg-success text-white border-0">Hari<br>Kerja</th>

                        <?php if (isset($is_nakes_view) && $is_nakes_view): ?>
                            <th rowspan="2" width="100" class="align-middle bg-primary text-white border-0" title="Kehadiran Untuk Hitungan Remunirasi">Hadir<br>Remun</th>
                        <?php endif; ?>

                        <th colspan="6" class="bg-primary text-white py-2 border-0">Kualitas Kedatangan Pagi</th>
                        <th colspan="2" class="bg-warning text-dark py-2 border-0">Telat Pagi</th>

                        <th colspan="3" class="bg-info py-2 border-0">Pulang Cepat & Lupa</th>

                        <th colspan="4" class="bg-secondary py-2 border-0">Ketidakhadiran</th>
                        
                        <?php if (isset($is_nakes_view) && $is_nakes_view): ?>
                            <th colspan="2" class="bg-danger text-white py-2 border-0">Tindakan CITO</th>
                        <?php endif; ?>

                        <th colspan="2" class="bg-dark py-2 border-0 border-start">Status Akhir</th>
                    </tr>
                    <tr>
                        <th width="30" class="bg-st-istimewa border-0" title="Sangat Baik / Istimewa">IS</th>
                        <th width="30" class="bg-st-baik border-0" title="Baik">B</th>
                        <th width="30" class="bg-st-cukup border-0" title="Cukup Baik">CB</th>
                        <th width="30" class="bg-st-telat border-0" title="Terlambat">TB</th>
                        <th width="30" class="bg-st-bina border-0" title="Pembinaan">PB</th>
                        <th width="30" class="bg-st-teguran border-0" title="Teguran">TG</th>

                        <th width="30" class="bg-light text-dark border-0">X</th>
                        <th width="45" class="bg-light text-dark border-0">Menit</th>

                        <th width="30" class="bg-light text-dark border-0" title="Kali Pulang Cepat">PC</th>
                        <th width="30" class="bg-danger text-white border-0" title="Kali Lupa Absen Pulang">LP</th>
                        <th width="45" class="bg-light text-dark border-0">Menit</th>

                        <th width="30" class="bg-danger text-white border-0">A</th>
                        <th width="30" class="bg-warning text-dark border-0">I/S</th>
                        <th width="30" class="bg-primary text-white border-0">C</th>
                        <th width="30" class="bg-info text-white border-0">TL</th>

                        <?php if (isset($is_nakes_view) && $is_nakes_view): ?>
                            <th width="30" class="bg-light text-danger fw-bold border-0" title="Frekuensi Cito">X</th>
                            <th width="50" class="bg-light text-danger fw-bold border-0" title="Durasi Cito">Jam</th>
                        <?php endif; ?>

                        <th width="65" class="bg-secondary text-white border-0 border-start">MASUK</th>
                        <th width="65" class="bg-secondary text-white border-0">PULANG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($pegawai as $p): ?>
                        <?php
                        $menit_telat = intval($p['total_menit_telat']);
                        $menit_pc    = intval($p['total_menit_pc']);
                        $lupa_pulang = intval($p['frekuensi_lupa_pulang'] ?? 0);
                        $alpa        = intval($p['total_alpa']);

                        // =========================================================================
                        // 1. LOGIKA STATUS AKHIR MASUK (PAGI)
                        // =========================================================================
                        $total_hari_absen = $p['st_istimewa'] + $p['st_baik'] + $p['st_cukup'] + $p['st_telat'] + $p['st_bina'] + $p['st_teguran'];
                        $rata2_masuk = 5;
                        if ($total_hari_absen > 0) {
                            $skor = ($p['st_istimewa'] * 5) + ($p['st_baik'] * 4) + ($p['st_cukup'] * 3) + ($p['st_telat'] * 2) + ($p['st_bina'] * 1);
                            $rata2_masuk = $skor / $total_hari_absen;
                        }

                        if ($rata2_masuk >= 4.5) {
                            $st_m = "Sangat Baik";
                            $bg_m = "bg-st-istimewa";
                        } elseif ($rata2_masuk >= 3.5) {
                            $st_m = "Baik";
                            $bg_m = "bg-st-baik";
                        } elseif ($rata2_masuk >= 2.5) {
                            $st_m = "Cukup Baik";
                            $bg_m = "bg-st-cukup text-dark";
                        } elseif ($rata2_masuk >= 1.5) {
                            $st_m = "Terlambat";
                            $bg_m = "bg-st-telat text-dark";
                        } elseif ($rata2_masuk >= 0.5) {
                            $st_m = "Pembinaan";
                            $bg_m = "bg-st-bina text-dark";
                        } else {
                            $st_m = "Teguran";
                            $bg_m = "bg-st-teguran";
                        }

                        if ($alpa >= 3 || $menit_telat > 300) {
                            $st_m = "Teguran";
                            $bg_m = "bg-st-teguran";
                        } elseif ($alpa > 0 || $menit_telat > 150) {
                            $st_m = "Pembinaan";
                            $bg_m = "bg-st-bina text-dark";
                        }

                        // =========================================================================
                        // 2. LOGIKA STATUS AKHIR PULANG (SORE)
                        // =========================================================================
                        $st_p = "Sangat Baik";
                        $bg_p = "bg-st-istimewa";

                        if ($alpa >= 3 || $menit_pc > 300 || $lupa_pulang >= 3) {
                            $st_p = "Teguran";
                            $bg_p = "bg-st-teguran";
                        } elseif ($alpa > 0 || $menit_pc > 150 || $lupa_pulang > 0) {
                            $st_p = "Pembinaan";
                            $bg_p = "bg-st-bina text-dark";
                        } elseif ($menit_pc > 60) {
                            $st_p = "Cukup Baik";
                            $bg_p = "bg-st-cukup text-dark";
                        } elseif ($menit_pc > 0) {
                            $st_p = "Baik";
                            $bg_p = "bg-st-baik";
                        }
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td class="text-start ps-3">
                                <div class="fw-bold text-dark"><?= $p['nama_lengkap'] ?></div>
                                <div class="text-muted" style="font-size: 0.6rem;">NIP. <?= $p['nip'] ?></div>
                                <div class="text-primary fw-bold" style="font-size: 0.55rem;">
                                    <?= $p['jabatan'] ?>
                                    <?php if (strpos($selected_opd, 'rekap_eselon') !== false || strpos($selected_opd, 'gabungan||') === 0): ?>
                                        <span class="text-muted">| <?= $p['nama_opd'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="fw-bold text-success bg-success-subtle border-start border-end" style="font-size: 0.8rem;">
                                <?= $hari_kerja ?>
                            </td>

                            <?php if (isset($is_nakes_view) && $is_nakes_view): ?>
                                <td class="align-middle bg-primary-subtle border-end p-1">
                                    <div class="fw-bold text-primary" style="font-size: 1rem;"><?= $p['kehadiran_remun'] ?></div>
                                    <div class="text-dark text-start mx-auto border border-primary border-opacity-25" style="font-size: 0.5rem; background: rgba(255,255,255,0.8); border-radius: 4px; padding: 3px; line-height: 1.2; width: max-content; margin-top: 2px;">
                                        A:<b class="text-danger"><?= $alpa ?></b> | T:<b class="text-danger"><?= $p['frekuensi_telat'] ?></b><br>
                                        I:<b class="text-warning text-dark"><?= $p['total_izin_khusus'] ?></b> | C:<b class="text-warning text-dark"><?= $p['total_cuti'] ?></b><br>
                                        S(>3):<b class="text-info text-dark"><?= $p['potongan_sakit'] ?></b> | TL(>3):<b class="text-info text-dark"><?= $p['potongan_tl'] ?></b>
                                    </div>
                                </td>
                            <?php endif; ?>

                            <td class="<?= ($p['st_istimewa'] > 0) ? 'fw-bold text-success' : 'text-muted' ?>"><?= $p['st_istimewa'] ?></td>
                            <td class="<?= ($p['st_baik'] > 0) ? 'fw-bold text-success' : 'text-muted' ?>"><?= $p['st_baik'] ?></td>
                            <td class="<?= ($p['st_cukup'] > 0) ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $p['st_cukup'] ?></td>
                            <td class="<?= ($p['st_telat'] > 0) ? 'fw-bold text-danger' : 'text-muted' ?>"><?= $p['st_telat'] ?></td>
                            <td class="<?= ($p['st_bina'] > 0) ? 'fw-bold text-danger' : 'text-muted' ?>"><?= $p['st_bina'] ?></td>
                            <td class="<?= ($p['st_teguran'] > 0) ? 'fw-bold text-danger' : 'text-muted' ?>"><?= $p['st_teguran'] ?></td>

                            <td class="<?= ($p['frekuensi_telat'] > 0) ? 'fw-bold' : 'text-muted' ?>"><?= $p['frekuensi_telat'] ?></td>
                            <td class="<?= ($menit_telat > 0) ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $menit_telat ?>m</td>

                            <td class="<?= ($p['frekuensi_pc'] > 0) ? 'fw-bold' : 'text-muted' ?>"><?= $p['frekuensi_pc'] ?></td>
                            <td class="<?= ($lupa_pulang > 0) ? 'text-danger fw-bold bg-danger-subtle' : 'text-muted' ?>"><?= $lupa_pulang ?></td>
                            <td class="<?= ($menit_pc > 0) ? 'text-primary fw-bold' : 'text-muted' ?>"><?= $menit_pc ?>m</td>

                            <td class="<?= ($alpa > 0) ? 'bg-danger-subtle fw-bold text-danger' : 'text-muted' ?>"><?= $alpa ?></td>
                            <td class="text-muted"><?= $p['total_izin_sakit'] ?></td>
                            <td class="text-muted fw-bold"><?= $p['total_cuti'] ?></td>
                            <td class="text-muted"><?= $p['total_dl'] ?></td>

                            <?php if (isset($is_nakes_view) && $is_nakes_view): ?>
                                <?php
                                $cito_jam = floor(($p['cito_menit'] ?? 0) / 60);
                                $sisa_menit = ($p['cito_menit'] ?? 0) % 60;
                                $cito_str = ($p['cito_kali'] > 0) ? $cito_jam . 'j ' . $sisa_menit . 'm' : '-';
                                ?>
                                <td class="<?= ($p['cito_kali'] > 0) ? 'fw-bold text-danger' : 'text-muted' ?>"><?= ($p['cito_kali'] > 0) ? $p['cito_kali'] : '-' ?></td>
                                <td class="<?= ($p['cito_kali'] > 0) ? 'fw-bold text-danger' : 'text-muted' ?>"><?= $cito_str ?></td>
                            <?php endif; ?>

                            <td class="border-start p-1">
                                <div class="badge <?= $bg_m ?> w-100 py-2 border border-light" style="font-size: 0.55rem; letter-spacing: 0.3px;">
                                    <?= strtoupper($st_m) ?>
                                </div>
                            </td>
                            <td class="p-1">
                                <div class="badge <?= $bg_p ?> w-100 py-2 border border-light" style="font-size: 0.55rem; letter-spacing: 0.3px;">
                                    <?= strtoupper($st_p) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info border-0 shadow-sm text-center py-4">Pilih OPD/Kategori, Filter, lalu klik Tampilkan.</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

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
                const doc = new jsPDF('l', 'mm', 'a4'); // 'l' = Landscape
                const namaBulan = "<?= $bulan_list[(int)$bulan] ?>";
                let judul = "LAPORAN DISIPLIN ASN";

                <?php if ($selected_opd == 'rekap_eselon_2') echo 'judul = "LAPORAN DISIPLIN PEJABAT ESELON II SE-KABUPATEN";'; ?>
                <?php if ($selected_opd == 'rekap_eselon_3') echo 'judul = "LAPORAN DISIPLIN PEJABAT ESELON III SE-KABUPATEN";'; ?>
                <?php if ($selected_opd == 'rekap_eselon_4') echo 'judul = "LAPORAN DISIPLIN PEJABAT ESELON IV SE-KABUPATEN";'; ?>
                <?php if ($selected_opd == 'rekap_eselon_gabungan') echo 'judul = "LAPORAN DISIPLIN PEJABAT ESELON II III IV SE-KABUPATEN";'; ?>
                <?php if (strpos($selected_opd, 'gabungan||') === 0) echo 'judul = "LAPORAN DISIPLIN GABUNGAN ' . strtoupper(str_replace('gabungan||', '', $selected_opd)) . '";'; ?>

                doc.setFontSize(14);
                doc.text(judul, 148, 15, {
                    align: "center"
                });
                doc.setFontSize(10);

                let subtitle = `Periode: ${namaBulan} <?= $tahun ?> | Hari Kerja Efektif: <?= $hari_kerja ?> Hari`;
                <?php if (!empty($selected_status_asn)): ?>
                    subtitle += ` | Status ASN: <?= strtoupper($selected_status_asn) ?>`;
                <?php endif; ?>

                <?php
                $shift_name = "";
                if (!empty($selected_shift)) {
                    foreach ($list_jam_kerja ?? [] as $jk) {
                        if ($jk['id_jam_kerja'] == $selected_shift) {
                            $shift_name = strtoupper($jk['nama_jadwal']);
                            break;
                        }
                    }
                }
                ?>
                <?php if (!empty($shift_name)): ?>
                    subtitle += ` | Shift: <?= $shift_name ?>`;
                <?php endif; ?>

                doc.text(subtitle, 148, 22, {
                    align: "center"
                });

                doc.autoTable({
                    html: '#tabelRekap',
                    startY: 30,
                    theme: 'grid',
                    styles: {
                        fontSize: 6.5,
                        halign: 'center',
                        valign: 'middle'
                    },
                    headStyles: {
                        fillColor: [33, 37, 41],
                        textColor: [255, 255, 255]
                    },
                    columnStyles: {
                        1: {
                            halign: 'left'
                        }
                    },
                    margin: {
                        top: 30
                    }
                });

                doc.save(`${judul}_${namaBulan}.pdf`);
                
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