<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .table-matrix th,
    .table-matrix td {
        border: 1px solid #cbd5e1 !important;
        vertical-align: middle;
        padding: 4px;
        font-size: 0.7rem;
    }

    .table-matrix th {
        background-color: #f8fafc;
        font-weight: bold;
    }

    .cell-date {
        min-width: 25px;
        max-width: 25px;
        height: 25px;
        text-align: center;
        padding: 0 !important;
        cursor: pointer;
    }

    /* KODE WARNA BLOK PREDIKAT */
    .bg-st-istimewa {
        background-color: #155724 !important;
    }

    /* Hijau Tua */
    .bg-st-baik {
        background-color: #28a745 !important;
    }

    /* Hijau Daun */
    .bg-st-cukup {
        background-color: #8ce196 !important;
    }

    /* Hijau Muda */
    .bg-st-telat {
        background-color: #ffda6a !important;
    }

    /* Kuning Pudar */
    .bg-st-bina {
        background-color: #ffc107 !important;
    }

    /* Kuning Terang */
    .bg-st-teguran {
        background-color: #070404 !important;
    }

    /* Hitam Terang */
    .bg-st-izin {
        background-color: #6f42c1 !important;
    }

    /* Ungu (Izin/TL) */
    .bg-st-apel {
        background-color: #0d6efd !important;
    }

    /* Biru (Apel) */
    .bg-libur {
        background-color: #fed7aa !important;
    }

    /* Orange (Libur) */
    .bg-alpa {
        background-color: #ef4444 !important;
    }

    /* Merah Terang (Alpa) */

    .row-group {
        background-color: #e2e8f0 !important;
        color: #0f172a;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }

    .selected-massal {
        outline: 3px solid #ff0000 !important;
        outline-offset: -3px;
        transform: scale(0.9);
        transition: all 0.2s;
    }

    .fab-massal {
        position: fixed;
        bottom: -100px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #343a40;
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 9999;
        transition: bottom 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .fab-massal.show {
        bottom: 30px;
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
                <label class="small fw-bold text-muted">Status ASN</label>
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
                <label class="small fw-bold text-muted">Bulan</label>
                <select name="bulan" class="form-select form-select-sm shadow-none">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= ($bulan == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 10)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label class="small fw-bold text-muted">Tahun</label>
                <input type="number" name="tahun" class="form-control form-control-sm shadow-none" value="<?= $tahun ?>">
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm" title="Tampilkan"><i class="fas fa-search me-1"></i> Tampilkan</button>
                <button type="button" onclick="generateMatrixPDF()" class="btn btn-danger btn-sm w-100 shadow-sm" <?= empty($matrix_data) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                </button>
                <button type="button" onclick="generateMatrixExcel()" class="btn btn-success btn-sm w-100 shadow-sm" <?= empty($matrix_data) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($selected_opd)): ?>

    <div class="card bg-light border-0 shadow-sm mb-3 p-3 no-print">
        <h6 class="fw-bold mb-2 small text-dark"><i class="fas fa-palette me-1"></i> Keterangan Warna Predikat Absen Masuk</h6>
        <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.75rem;">
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #155724;"></span> Istimewa</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #28a745;"></span> Baik</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #8ce196;"></span> Cukup Baik</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #ffda6a;"></span> Terlambat</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #ffc107;"></span> Pembinaan</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #070404;"></span> Teguran</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #6f42c1;"></span> Izin/Sakit/TL</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #fed7aa;"></span> Libur/Akhir Pekan</div>
            <div class="d-flex align-items-center"><span class="rounded me-2" style="width: 14px; height: 14px; background-color: #ef4444;"></span> Alpa/Tanpa Ket</div>
        </div>
    </div>

    <?php if (!empty($matrix_data)): ?>
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="text-center py-3 border-bottom">
                <h6 class="fw-bold m-0">MONITORING IMPLEMENTASI ROLE MODEL <span class="badge bg-secondary">DEBUG: Username Anda adalah "<?= session()->get('username') ?>" | Level: "<?= session()->get('level') ?>"</span></h6>
                <h6 class="fw-bold m-0">PEJABAT JPT, ADMINISTRATOR DAN PENGAWAS</h6>
                <p class="m-0 small">PERIODE : <?= strtoupper(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></p>
            </div>

            <?php if (strpos(strtolower(session()->get('username')), 'superadmin') !== false): ?>
                <div class="p-3 pb-0 text-end no-print">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-bold" id="btnToggleMassal" onclick="toggleModeMassal()">
                        <i class="fas fa-check-double me-1"></i> Mode Pilihan Massal: OFF
                    </button>
                </div>
            <?php endif; ?>

            <div class="table-responsive p-3">
                <table class="table table-matrix text-center text-dark w-100" id="tabelMatrix">
                    <thead>
                        <tr>
                            <th rowspan="2" width="30">No</th>
                            <th rowspan="2" class="text-start" style="min-width: 250px;">Perangkat Daerah / Jabatan</th>
                            <th rowspan="2" style="min-width: 80px;">Eselon</th>
                            <th rowspan="2" class="text-start" style="min-width: 200px;">Nama</th>
                            <?php foreach ($list_tanggal as $d => $info): ?>
                                <th class="cell-date <?= $info['is_weekend'] ? 'bg-libur' : '' ?>"><?= $info['hari'] ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($list_tanggal as $d => $info): ?>
                                <th class="cell-date <?= $info['is_weekend'] ? 'bg-libur' : '' ?>"><?= $d ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_days = count($list_tanggal);

                        foreach ($matrix_data as $opd_name => $pegawai_list):
                            $total_cells = 0;
                            $c_istimewa = 0;
                            $c_baik = 0;
                            $c_cukup = 0;
                            $c_telat = 0;
                            $c_bina = 0;
                            $c_tegur = 0;
                            $c_izin = 0;
                            $c_libur = 0;
                            $c_alpa = 0;

                            foreach ($pegawai_list as $p) {
                                foreach ($p['absensi'] as $hari) {
                                    $total_cells++;
                                    $col = $hari['color'];

                                    if ($col === 'bg-st-istimewa') $c_istimewa++;
                                    elseif ($col === 'bg-st-baik' || $col === 'bg-st-apel') $c_baik++;
                                    elseif ($col === 'bg-st-cukup') $c_cukup++;
                                    elseif ($col === 'bg-st-telat') $c_telat++;
                                    elseif ($col === 'bg-st-bina') $c_bina++;
                                    elseif ($col === 'bg-st-teguran') $c_tegur++;
                                    elseif ($col === 'bg-st-izin') $c_izin++;
                                    elseif ($col === 'bg-libur') $c_libur++;
                                    elseif ($col === 'bg-alpa') $c_alpa++;
                                }
                            }

                            $p_ist  = ($total_cells > 0) ? round(($c_istimewa / $total_cells) * 100, 1) : 0;
                            $p_baik = ($total_cells > 0) ? round(($c_baik / $total_cells) * 100, 1) : 0;
                            $p_ckp  = ($total_cells > 0) ? round(($c_cukup / $total_cells) * 100, 1) : 0;
                            $p_tlt  = ($total_cells > 0) ? round(($c_telat / $total_cells) * 100, 1) : 0;
                            $p_bna  = ($total_cells > 0) ? round(($c_bina / $total_cells) * 100, 1) : 0;
                            $p_tgr  = ($total_cells > 0) ? round(($c_tegur / $total_cells) * 100, 1) : 0;
                            $p_izn  = ($total_cells > 0) ? round(($c_izin / $total_cells) * 100, 1) : 0;
                            $p_lbr  = ($total_cells > 0) ? round(($c_libur / $total_cells) * 100, 1) : 0;
                            $p_alp  = ($total_cells > 0) ? round(($c_alpa / $total_cells) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td colspan="<?= 4 + $total_days ?>" class="row-group p-2"
                                    data-opd-name="<?= $opd_name ?>"
                                    data-ist="<?= $p_ist ?>" data-baik="<?= $p_baik ?>"
                                    data-ckp="<?= $p_ckp ?>" data-tlt="<?= $p_tlt ?>"
                                    data-bina="<?= $p_bna ?>" data-tgr="<?= $p_tgr ?>"
                                    data-izin="<?= $p_izn ?>" data-lbr="<?= $p_lbr ?>"
                                    data-alpa="<?= $p_alp ?>">

                                    <div class="d-flex align-items-center flex-wrap gap-2 w-100">
                                        <span class="text-uppercase fw-bold me-2"><?= $opd_name ?></span>

                                        <div class="d-flex flex-wrap gap-1 no-print" style="font-size: 0.65rem; font-weight: 600;">
                                            <?php if ($p_ist > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #155724; color: white;">Istimewa: <?= $p_ist ?>%</span><?php endif; ?>
                                            <?php if ($p_baik > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #28a745; color: white;">Baik: <?= $p_baik ?>%</span><?php endif; ?>
                                            <?php if ($p_ckp > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #8ce196; color: #212529;">Cukup: <?= $p_ckp ?>%</span><?php endif; ?>
                                            <?php if ($p_tlt > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #ffda6a; color: #212529;">Telat: <?= $p_tlt ?>%</span><?php endif; ?>
                                            <?php if ($p_bna > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #ffc107; color: #212529;">Bina: <?= $p_bna ?>%</span><?php endif; ?>
                                            <?php if ($p_tgr > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #070404; color: white;">Teguran: <?= $p_tgr ?>%</span><?php endif; ?>
                                            <?php if ($p_izn > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #6f42c1; color: white;">Izin/TL: <?= $p_izn ?>%</span><?php endif; ?>
                                            <?php if ($p_lbr > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #fed7aa; color: #212529;">Libur: <?= $p_lbr ?>%</span><?php endif; ?>
                                            <?php if ($p_alp > 0): ?><span class="badge shadow-sm border border-light" style="background-color: #ef4444; color: white;">Alpa: <?= $p_alp ?>%</span><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php foreach ($pegawai_list as $p): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-start"><?= $p['jabatan'] ?></td>
                                    <td><?= $p['eselon'] ?></td>
                                    <td class="text-start fw-bold"><?= $p['nama'] ?></td>

                                    <?php foreach ($list_tanggal as $d => $info): ?>
                                        <?php
                                        $cell = $p['absensi'][$d];
                                        $cursor_style = '';
                                        // Cek username Superadmin
                                        $is_superadmin = (strpos(strtolower(session()->get('username')), 'superadmin') !== false);
                                        $has_record = (!empty($cell['color']) && $cell['color'] !== 'bg-alpa' && $cell['color'] !== 'bg-libur');

                                        $editable_class = '';
                                        $data_attrs = '';
                                        if ($has_record && $is_superadmin) {
                                            $safe_nama = htmlspecialchars(str_replace(["'", '"'], '', $p['nama']), ENT_QUOTES);
                                            $editable_class = 'editable-cell';
                                            $data_attrs = 'data-nip="' . $cell['nip'] . '" data-tgl="' . $cell['tanggal'] . '" data-nama="' . $safe_nama . '" data-color="' . $cell['color'] . '"';
                                            $cursor_style = 'cursor: pointer; box-shadow: inset 0 0 3px rgba(0,0,0,0.3); border: 1px solid #666 !important;';
                                        }
                                        ?>
                                        <td class="cell-date <?= $cell['color'] ?> <?= $editable_class ?>"
                                            style="<?= $cursor_style ?>"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="<?= $cell['title'] ?>"
                                            <?= $data_attrs ?>></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Floating Action Button untuk Massal -->
        <div class="fab-massal no-print" id="fabMassal">
            <div>
                <span class="fw-bold" id="countSelectedMassal">0</span> Data Terpilih
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="koreksiMassalSelected()">
                Koreksi Terpilih <i class="fas fa-arrow-right ms-1"></i>
            </button>
            <button class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm" onclick="clearSelectionMassal()">Batal</button>
        </div>
    <?php else: ?>
        <div class="alert alert-warning border-0 shadow-sm text-center py-4">
            Tidak ada data pegawai yang dapat ditampilkan.
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="fas fa-th-list text-light mb-3" style="font-size: 3rem;"></i>
            <h6 class="fw-bold">Matriks Role Model Kehadiran</h6>
            <p class="text-muted small">Pilih Kategori/Unit Kerja, Status ASN, dan Bulan untuk melihat matriks kehadiran dengan peta warna kedisiplinan.</p>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    // =========================================================================
    // CETAK PDF (KETERANGAN WARNA SUDAH ADA DI ATAS TABEL)
    // =========================================================================
    function generateMatrixPDF() {
        Swal.fire({
            title: 'Memproses Matriks PDF...',
            text: 'Mohon tunggu sebentar, sedang menyusun tata letak warna.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            try {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'legal');

                let judul = "MONITORING IMPLEMENTASI ROLE MODEL";
                let subJudul = "PEJABAT JPT, ADMINISTRATOR DAN PENGAWAS";

                let periode = "PERIODE : <?= strtoupper(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?>";
                <?php if (!empty($selected_status_asn)): ?>
                    periode += " | STATUS ASN : <?= strtoupper($selected_status_asn) ?>";
                <?php endif; ?>

                doc.setFontSize(12);
                doc.setFont("helvetica", "bold");
                doc.text(judul, 177, 15, {
                    align: "center"
                });
                doc.text(subJudul, 177, 20, {
                    align: "center"
                });

                doc.setFontSize(10);
                doc.setFont("helvetica", "normal");
                doc.text(periode, 177, 26, {
                    align: "center"
                });

                // LUKIS KETERANGAN WARNA DI PDF
                doc.setFontSize(8);
                doc.setFont("helvetica", "bold");
                doc.text("Keterangan Warna:", 10, 35);
                doc.setFont("helvetica", "normal");

                let startX = 10;
                let stepX = 29;

                doc.setFillColor(21, 87, 36);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Istimewa", startX + 6, 40.5);
                startX += stepX;
                doc.setFillColor(40, 167, 69);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Baik", startX + 6, 40.5);
                startX += 18;
                doc.setFillColor(140, 225, 150);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Cukup Baik", startX + 6, 40.5);
                startX += stepX + 2;
                doc.setFillColor(255, 218, 106);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Terlambat", startX + 6, 40.5);
                startX += stepX;
                doc.setFillColor(255, 193, 7);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Pembinaan", startX + 6, 40.5);
                startX += stepX + 2;
                doc.setFillColor(7, 4, 4);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Teguran", startX + 6, 40.5);
                startX += stepX - 2;
                doc.setFillColor(111, 66, 193);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Izin/Sakit/TL", startX + 6, 40.5);
                startX += stepX + 6;
                doc.setFillColor(254, 215, 170);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Libur/Akhir Pekan", startX + 6, 40.5);
                startX += stepX + 10;
                doc.setFillColor(239, 68, 68);
                doc.rect(startX, 37, 4, 4, 'F');
                doc.text("Alpa/Tanpa Ket", startX + 6, 40.5);

                doc.autoTable({
                    html: '#tabelMatrix',
                    startY: 45,
                    theme: 'grid',
                    styles: {
                        fontSize: 6,
                        halign: 'center',
                        valign: 'middle',
                        lineWidth: 0.1,
                        lineColor: [203, 213, 225]
                    },
                    headStyles: {
                        fillColor: [248, 250, 252],
                        textColor: [0, 0, 0],
                        fontStyle: 'bold'
                    },
                    columnStyles: {
                        1: {
                            halign: 'left',
                            cellWidth: 45
                        },
                        2: {
                            cellWidth: 15
                        },
                        3: {
                            halign: 'left',
                            cellWidth: 40
                        }
                    },
                    didParseCell: function(data) {
                        let raw = data.cell.raw;
                        if (!raw) return;

                        let cls = raw.className || '';
                        if (typeof cls !== 'string' && typeof raw.getAttribute === 'function') {
                            cls = raw.getAttribute('class') || '';
                        }

                        if (cls.includes('row-group')) {
                            data.cell.styles.fillColor = [226, 232, 240];
                            data.cell.text = [' '];
                        } else if (cls.includes('bg-st-istimewa')) {
                            data.cell.styles.fillColor = [21, 87, 36];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-baik')) {
                            data.cell.styles.fillColor = [40, 167, 69];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-cukup')) {
                            data.cell.styles.fillColor = [140, 225, 150];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-telat')) {
                            data.cell.styles.fillColor = [255, 218, 106];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-bina')) {
                            data.cell.styles.fillColor = [255, 193, 7];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-teguran')) {
                            data.cell.styles.fillColor = [7, 4, 4];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-apel')) {
                            data.cell.styles.fillColor = [13, 110, 253];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-st-izin')) {
                            data.cell.styles.fillColor = [111, 66, 193];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-libur')) {
                            data.cell.styles.fillColor = [254, 215, 170];
                            data.cell.text = [''];
                        } else if (cls.includes('bg-alpa')) {
                            data.cell.styles.fillColor = [239, 68, 68];
                            data.cell.text = [''];
                        }
                    },
                    didDrawCell: function(data) {
                        let raw = data.cell.raw;
                        if (!raw) return;

                        let cls = raw.className || '';
                        if (typeof cls !== 'string' && typeof raw.getAttribute === 'function') {
                            cls = raw.getAttribute('class') || '';
                        }

                        if (cls.includes('row-group')) {
                            let doc = data.doc;
                            let opdName = raw.getAttribute('data-opd-name') || '';

                            let startX = data.cell.x + 3;
                            let startY = data.cell.y + (data.cell.height / 2) + 1.2;

                            doc.setFontSize(7);
                            doc.setFont("helvetica", "bold");
                            doc.setTextColor(15, 23, 42);
                            doc.text(opdName, startX, startY);

                            let currentX = startX + doc.getTextWidth(opdName) + 4;

                            function drawBadgePDF(label, val, bgR, bgG, bgB, txtR, txtG, txtB) {
                                if (val > 0) {
                                    let txt = label + ": " + val + "%";
                                    let txtW = doc.getTextWidth(txt);
                                    let rectHeight = 4.5;
                                    let rectY = data.cell.y + (data.cell.height / 2) - (rectHeight / 2);

                                    doc.setFillColor(bgR, bgG, bgB);
                                    doc.rect(currentX, rectY, txtW + 3, rectHeight, 'F');

                                    doc.setTextColor(txtR, txtG, txtB);
                                    doc.setFontSize(6);
                                    doc.text(txt, currentX + 1.5, startY - 0.2);

                                    currentX += txtW + 5;
                                    doc.setFontSize(7);
                                }
                            }

                            drawBadgePDF("Ist", parseFloat(raw.getAttribute('data-ist')), 21, 87, 36, 255, 255, 255);
                            drawBadgePDF("Baik", parseFloat(raw.getAttribute('data-baik')), 40, 167, 69, 255, 255, 255);
                            drawBadgePDF("Ckp", parseFloat(raw.getAttribute('data-ckp')), 140, 225, 150, 0, 0, 0);
                            drawBadgePDF("Tlt", parseFloat(raw.getAttribute('data-tlt')), 255, 218, 106, 0, 0, 0);
                            drawBadgePDF("Bina", parseFloat(raw.getAttribute('data-bina')), 255, 193, 7, 0, 0, 0);
                            drawBadgePDF("Tgr", parseFloat(raw.getAttribute('data-tgr')), 7, 4, 4, 255, 255, 255);
                            drawBadgePDF("Izin/TL", parseFloat(raw.getAttribute('data-izin')), 111, 66, 193, 255, 255, 255);
                            drawBadgePDF("Lbr", parseFloat(raw.getAttribute('data-lbr')), 254, 215, 170, 0, 0, 0);
                            drawBadgePDF("Alpa", parseFloat(raw.getAttribute('data-alpa')), 239, 68, 68, 255, 255, 255);
                        }
                    },
                    margin: {
                        left: 10,
                        right: 10,
                        top: 45,
                        bottom: 15
                    }
                });

                doc.save(`Matriks_Role_Model_<?= $bulan ?>_<?= $tahun ?>.pdf`);
                Swal.close();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan saat membuat matriks PDF.', 'error');
            }
        }, 300);
    }

    // =========================================================================
    // CETAK EXCEL DENGAN INLINE CSS DAN KETERANGAN WARNA
    // =========================================================================
    function generateMatrixExcel() {
        Swal.fire({
            title: 'Mempersiapkan Excel...',
            text: 'Mengekspor matriks warna ke file Excel.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            try {
                let table = document.getElementById("tabelMatrix");
                let cloneTable = table.cloneNode(true);

                let rowGroups = cloneTable.querySelectorAll('.row-group');
                rowGroups.forEach(cell => {
                    let opdName = cell.getAttribute('data-opd-name');
                    cell.innerHTML = opdName;
                    cell.style.backgroundColor = "#e2e8f0";
                    cell.style.fontWeight = "bold";
                    cell.style.textAlign = "left";
                });

                let cells = cloneTable.querySelectorAll('td, th');
                cells.forEach(cell => {
                    let className = cell.className || '';

                    if (className.includes('bg-st-istimewa')) {
                        cell.style.backgroundColor = '#155724';
                        cell.style.color = '#ffffff';
                    } else if (className.includes('bg-st-baik')) {
                        cell.style.backgroundColor = '#28a745';
                        cell.style.color = '#ffffff';
                    } else if (className.includes('bg-st-cukup')) {
                        cell.style.backgroundColor = '#8ce196';
                        cell.style.color = '#000000';
                    } else if (className.includes('bg-st-telat')) {
                        cell.style.backgroundColor = '#ffda6a';
                        cell.style.color = '#000000';
                    } else if (className.includes('bg-st-bina')) {
                        cell.style.backgroundColor = '#ffc107';
                        cell.style.color = '#000000';
                    } else if (className.includes('bg-st-teguran')) {
                        cell.style.backgroundColor = '#070404';
                        cell.style.color = '#ffffff';
                    } else if (className.includes('bg-st-izin')) {
                        cell.style.backgroundColor = '#6f42c1';
                        cell.style.color = '#ffffff';
                    } else if (className.includes('bg-st-apel')) {
                        cell.style.backgroundColor = '#0d6efd';
                        cell.style.color = '#ffffff';
                    } else if (className.includes('bg-libur')) {
                        cell.style.backgroundColor = '#fed7aa';
                        cell.style.color = '#000000';
                    } else if (className.includes('bg-alpa')) {
                        cell.style.backgroundColor = '#ef4444';
                        cell.style.color = '#ffffff';
                    }

                    cell.style.border = "1px solid #000000";
                    cell.style.verticalAlign = "middle";
                });

                // MEMBANGUN TABEL KETERANGAN WARNA UNTUK EXCEL
                let legendHtml = `
                    <table style="margin-bottom: 20px; font-family: Arial, sans-serif; font-size: 11px;">
                        <tr><td colspan="9" style="font-weight: bold; border: none; text-align: left;">Keterangan Warna:</td></tr>
                        <tr>
                            <td style="background-color: #155724; color: #ffffff; border: 1px solid #000000; text-align: center; width: 80px;">Istimewa</td>
                            <td style="background-color: #28a745; color: #ffffff; border: 1px solid #000000; text-align: center; width: 80px;">Baik</td>
                            <td style="background-color: #8ce196; color: #000000; border: 1px solid #000000; text-align: center; width: 80px;">Cukup Baik</td>
                            <td style="background-color: #ffda6a; color: #000000; border: 1px solid #000000; text-align: center; width: 80px;">Terlambat</td>
                            <td style="background-color: #ffc107; color: #000000; border: 1px solid #000000; text-align: center; width: 80px;">Pembinaan</td>
                            <td style="background-color: #070404; color: #ffffff; border: 1px solid #000000; text-align: center; width: 80px;">Teguran</td>
                            <td style="background-color: #6f42c1; color: #ffffff; border: 1px solid #000000; text-align: center; width: 80px;">Izin/Sakit/TL</td>
                            <td style="background-color: #fed7aa; color: #000000; border: 1px solid #000000; text-align: center; width: 110px;">Libur/Akhir Pekan</td>
                            <td style="background-color: #ef4444; color: #ffffff; border: 1px solid #000000; text-align: center; width: 90px;">Alpa/Tanpa Ket</td>
                        </tr>
                    </table>
                    <br>
                `;

                let htmlTemplate = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <style>
                            table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
                        </style>
                    </head>
                    <body>
                        <h3>MONITORING IMPLEMENTASI ROLE MODEL</h3>
                        <h3>PEJABAT JPT, ADMINISTRATOR DAN PENGAWAS</h3>
                        <p>PERIODE : <?= strtoupper(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></p>
                        <br>
                        ${legendHtml}
                        ${cloneTable.outerHTML}
                    </body>
                    </html>
                `;

                let blob = new Blob([htmlTemplate], {
                    type: 'application/vnd.ms-excel'
                });
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'Matriks_Role_Model_<?= $bulan ?>_<?= $tahun ?>.xls';

                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                Swal.close();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan saat membuat file Excel.', 'error');
            }
        }, 500);
    }

    // =========================================================================
    // KOREKSI KETERLAMBATAN CERDAS (SMART CORRECTION) EVENT LISTENER
    // =========================================================================
    let isModeMassal = false;
    let selectedCells = [];

    function toggleModeMassal() {
        isModeMassal = !isModeMassal;
        const btn = document.getElementById('btnToggleMassal');
        if (isModeMassal) {
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger');
            btn.innerHTML = '<i class="fas fa-check-double me-1"></i> Mode Pilihan Massal: ON';
        } else {
            btn.classList.add('btn-outline-danger');
            btn.classList.remove('btn-danger');
            btn.innerHTML = '<i class="fas fa-check-double me-1"></i> Mode Pilihan Massal: OFF';
            clearSelectionMassal();
        }
    }

    function clearSelectionMassal() {
        selectedCells = [];
        document.querySelectorAll('.selected-massal').forEach(el => {
            el.classList.remove('selected-massal');
        });
        updateFabMassal();
    }

    function updateFabMassal() {
        const fab = document.getElementById('fabMassal');
        const countText = document.getElementById('countSelectedMassal');
        if (!fab) return;

        countText.innerText = selectedCells.length;
        if (selectedCells.length > 0) {
            fab.classList.add('show');
        } else {
            fab.classList.remove('show');
        }
    }

    document.addEventListener('click', function(e) {
        // Traverse up to find td if inner element is clicked
        let el = e.target.closest('td.editable-cell');
        if (el) {
            if (isModeMassal) {
                // Toggle seleksi sel
                let nip = el.getAttribute('data-nip');
                let tgl = el.getAttribute('data-tgl');
                let key = nip + '_' + tgl;

                let idx = selectedCells.findIndex(item => item.key === key);
                if (idx > -1) {
                    selectedCells.splice(idx, 1);
                    el.classList.remove('selected-massal');
                } else {
                    selectedCells.push({
                        key: key,
                        nip: nip,
                        tgl: tgl,
                        nama: el.getAttribute('data-nama'),
                        el: el
                    });
                    el.classList.add('selected-massal');
                }
                updateFabMassal();
            } else {
                // Single koreksi
                let nip = el.getAttribute('data-nip');
                let tgl = el.getAttribute('data-tgl');
                let nama = el.getAttribute('data-nama');
                let color = el.getAttribute('data-color');
                koreksiTerlambat(nip, tgl, nama, color, el);
            }
        }
    });

    function koreksiMassalSelected() {
        if (selectedCells.length === 0) return;

        Swal.fire({
            title: 'Koreksi ' + selectedCells.length + ' Data',
            html: `
                <div class="text-start">
                    <p class="text-muted small mb-3">Anda akan mengubah status dan jam absensi untuk <b>${selectedCells.length}</b> sel yang dipilih.</p>
                    
                    <label class="form-label fw-bold" style="font-size:13px;">Status Kehadiran Baru</label>
                    <select id="swal-massal-status" class="form-select mb-3">
                        <option value="Sangat Baik / Istimewa">Sangat Baik / Istimewa (Hijau Tua)</option>
                        <option value="Baik" selected>Baik / Tepat Waktu (Hijau Daun)</option>
                        <option value="Cukup Baik">Cukup Baik (Hijau Muda)</option>
                        <option value="Terlambat">Terlambat (Kuning)</option>
                        <option value="Pembinaan">Pembinaan (Kuning Terang)</option>
                        <option value="Teguran">Teguran (Coklat/Hitam)</option>
                    </select>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:13px;">Jam Masuk Baru</label>
                            <input type="time" id="swal-massal-jam" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:13px;">Jam Pulang Baru</label>
                            <input type="time" id="swal-massal-jam-pulang" class="form-control">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size:11px;">* Kosongkan jam jika tidak ingin merubah data jam.</small>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Terapkan ke ' + selectedCells.length + ' Data',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const status = document.getElementById('swal-massal-status').value;
                const jam = document.getElementById('swal-massal-jam').value;
                const jamPulang = document.getElementById('swal-massal-jam-pulang').value;
                return {
                    status: status,
                    jam: jam,
                    jamPulang: jamPulang
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let newStatus = result.value.status;
                let newJam = result.value.jam;
                let newJamPulang = result.value.jamPulang;

                Swal.fire({
                    title: 'Memproses Koreksi Massal...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Siapkan data JSON
                let payload = selectedCells.map(c => ({
                    nip: c.nip,
                    tanggal: c.tgl
                }));

                fetch('<?= base_url('rekap/ajax_koreksi_terlambat_massal') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            data_koreksi: payload,
                            status_baru: newStatus,
                            jam_baru: newJam,
                            jam_pulang: newJamPulang
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success');

                            // Ubah tampilan kotak menjadi warna yang baru
                            selectedCells.forEach(c => {
                                c.el.className = "cell-date " + data.new_color + " editable-cell";
                                c.el.setAttribute('data-color', data.new_color);

                                let displayTime = newJam ? newJam : "";
                                let newTitle = displayTime ? newStatus + " (" + displayTime + ") *" : newStatus + " *";

                                let tt = bootstrap.Tooltip.getInstance(c.el);
                                if (tt) {
                                    c.el.setAttribute('data-bs-original-title', newTitle);
                                    tt.update();
                                } else {
                                    c.el.title = newTitle;
                                }
                            });

                            clearSelectionMassal();

                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan sistem', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    });
            }
        });
    }

    function koreksiTerlambat(nip, tgl, namaPgw, currentColor, el) {
        try {
            // Coba ambil jam lama dari tooltip jika ada
            let oldTitle = el.getAttribute('data-bs-original-title') || el.title || '';
            let match = oldTitle.match(/\((\d{2}:\d{2})\)/);
            let oldTime = match ? match[1] : '';

            Swal.fire({
                title: 'Ubah Status & Jam',
                html: `
                    <div class="text-start">
                        <p class="text-muted small mb-3">Pegawai: <b>${namaPgw}</b><br>Tanggal: <b>${tgl}</b></p>
                        
                        <label class="form-label fw-bold" style="font-size:13px;">Status Kehadiran Baru</label>
                        <select id="swal-input-status" class="form-select mb-3">
                            <option value="Sangat Baik / Istimewa">Sangat Baik / Istimewa (Hijau Tua)</option>
                            <option value="Baik" selected>Baik / Tepat Waktu (Hijau Daun)</option>
                            <option value="Cukup Baik">Cukup Baik (Hijau Muda)</option>
                            <option value="Terlambat">Terlambat (Kuning)</option>
                            <option value="Pembinaan">Pembinaan (Kuning Terang)</option>
                            <option value="Teguran">Teguran (Coklat/Hitam)</option>
                        </select>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:13px;">Jam Masuk</label>
                                <input type="time" id="swal-input-jam" class="form-control" value="${oldTime}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size:13px;">Jam Pulang</label>
                                <input type="time" id="swal-input-jam-pulang" class="form-control">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size:11px;">* Kosongkan jika tidak ingin merubah jam terkait.</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const status = document.getElementById('swal-input-status').value;
                    const jam = document.getElementById('swal-input-jam').value;
                    const jamPulang = document.getElementById('swal-input-jam-pulang').value;
                    return {
                        status: status,
                        jam: jam,
                        jamPulang: jamPulang
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let newStatus = result.value.status;
                    let newJam = result.value.jam;
                    let newJamPulang = result.value.jamPulang;

                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('<?= base_url('rekap/ajax_koreksi_terlambat') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'nip=' + encodeURIComponent(nip) + '&tanggal=' + encodeURIComponent(tgl) + '&status_baru=' + encodeURIComponent(newStatus) + '&jam_baru=' + encodeURIComponent(newJam) + '&jam_pulang=' + encodeURIComponent(newJamPulang)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('Berhasil!', data.message, 'success');

                                // Ubah tampilan kotak menjadi warna yang baru
                                el.className = "cell-date " + data.new_color + " editable-cell";
                                el.setAttribute('data-color', data.new_color);

                                // Format tooltip baru
                                let displayTime = newJam ? newJam : oldTime;
                                let newTitle = displayTime ? newStatus + " (" + displayTime + ") *" : newStatus + " *";

                                // Update tooltip
                                let tt = bootstrap.Tooltip.getInstance(el);
                                if (tt) {
                                    el.setAttribute('data-bs-original-title', newTitle);
                                    tt.update();
                                } else {
                                    el.title = newTitle;
                                }
                            } else {
                                Swal.fire('Gagal!', data.message || 'Terjadi kesalahan sistem', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                        });
                }
            });
        } catch (error) {
            console.error("SweetAlert Error: ", error);

            let newStatus = prompt('Pilih status kehadiran baru (1-6):\n1. Sangat Baik\n2. Baik\n3. Cukup Baik\n4. Terlambat\n5. Pembinaan\n6. Teguran\n\n(Hanya bisa merubah status jika via prompt)');
            if (newStatus) {
                let map = {
                    '1': 'Sangat Baik / Istimewa',
                    '2': 'Baik',
                    '3': 'Cukup Baik',
                    '4': 'Terlambat',
                    '5': 'Pembinaan',
                    '6': 'Teguran'
                };
                if (map[newStatus]) {
                    fetch('<?= base_url('rekap/ajax_koreksi_terlambat') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'nip=' + encodeURIComponent(nip) + '&tanggal=' + encodeURIComponent(tgl) + '&status_baru=' + encodeURIComponent(map[newStatus]) + '&jam_baru='
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('Berhasil! Status diubah menjadi ' + map[newStatus]);
                                el.className = "cell-date " + data.new_color + " editable-cell";
                                el.setAttribute('data-color', data.new_color);
                            } else {
                                alert('Gagal: ' + data.message);
                            }
                        });
                } else {
                    alert('Pilihan tidak valid');
                }
            }
        }
    }
</script>

<?= $this->endSection() ?>