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
</style>

<div class="card shadow-sm border-0 mb-4" id="form-filter-area">
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
                        <option value="<?= $m ?>" <?= ($bulan == $m) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold">Tahun</label>
                <input type="number" name="tahun" class="form-control form-control-sm shadow-none" value="<?= $tahun ?>">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
                <button type="button" data-bs-toggle="modal" data-bs-target="#modalCetakPDF" class="btn btn-danger btn-sm w-100 rounded-pill shadow-sm" <?= empty($pegawai) ? 'disabled' : '' ?>>
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
                <?php if (!empty($pegawai)): ?>
                    <a href="<?= base_url('cetaktpp/excel?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun . '&status_asn=' . ($selected_status_asn ?? '')) ?>" class="btn btn-success btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<?php if (!empty($pegawai)): ?>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table id="tabel-tpp" class="table table-sm table-bordered text-center align-middle mb-0" style="font-size: 0.65rem; border-color: #dee2e6;">
                <thead class="bg-light text-dark fw-bold">
                    <tr>
                        <th rowspan="3" style="vertical-align: middle;">NO</th>
                        <th rowspan="3" style="vertical-align: middle;" width="180">NAMA</th>
                        <th rowspan="3" style="vertical-align: middle;" width="100">PANGKAT / GOL. RUANG</th>
                        <th rowspan="3" style="vertical-align: middle;" width="120">JABATAN</th>
                        <th rowspan="3" style="vertical-align: middle;" width="80">JUMLAH<br>HARI<br>KERJA</th>
                        <th colspan="12" class="bg-warning bg-opacity-10 text-warning-emphasis">JUMLAH HARI TIDAK MASUK KERJA</th>
                        <th colspan="12" class="bg-danger bg-opacity-10 text-danger-emphasis">PERHITUNGAN % KETIDAKTAATAN JAM KERJA</th>
                        <th rowspan="3" style="vertical-align: middle;" class="bg-primary text-white">JML<br>POT (%)</th>
                    </tr>
                    <tr>
                        <th colspan="4">Tb (menit)</th>
                        <th colspan="4">Pc (menit)</th>
                        <th rowspan="2" class="bg-info bg-opacity-10">TDK<br>(perkeg)</th>
                        <th rowspan="2" class="bg-primary bg-opacity-10">TA<br>(perkeg)</th>
                        <th rowspan="2">I<br>(hari)</th>
                        <th rowspan="2">A<br>(hari)</th>

                        <th colspan="4">Tb</th>
                        <th colspan="4">Pc</th>
                        <th rowspan="2" class="bg-info bg-opacity-10">TDK<br>(0,5%)</th>
                        <th rowspan="2" class="bg-primary bg-opacity-10">TA<br>(2%)</th>
                        <th rowspan="2">I<br>(0%)</th>
                        <th rowspan="2">A<br>(3%)</th>
                    </tr>
                    <tr>
                        <th>P1</th>
                        <th>P2</th>
                        <th>P3</th>
                        <th>P4</th>
                        <th>P1</th>
                        <th>P2</th>
                        <th>P3</th>
                        <th>P4</th>

                        <th>P1<br>0,5%</th>
                        <th>P2<br>1,0%</th>
                        <th>P3<br>1,25%</th>
                        <th>P4<br>1,5%</th>
                        <th>P1<br>0,5%</th>
                        <th>P2<br>1,0%</th>
                        <th>P3<br>1,25%</th>
                        <th>P4<br>1,5%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $sum_hk = 0;
                    $sum_p1 = 0; $sum_p2 = 0; $sum_p3 = 0; $sum_p4 = 0;
                    $sum_pc1 = 0; $sum_pc2 = 0; $sum_pc3 = 0; $sum_pc4 = 0;
                    $sum_tdk = 0; $sum_ta = 0; $sum_i = 0; $sum_a = 0;
                    $sum_tot_persen = 0;
                    foreach ($pegawai as $p): 
                        $sum_hk += (int)($p['hari_kerja'] ?? 0);
                        $sum_p1 += (int)($p['p1'] ?? 0);
                        $sum_p2 += (int)($p['p2'] ?? 0);
                        $sum_p3 += (int)($p['p3'] ?? 0);
                        $sum_p4 += (int)($p['p4'] ?? 0);
                        $sum_pc1 += (int)($p['pc1'] ?? 0);
                        $sum_pc2 += (int)($p['pc2'] ?? 0);
                        $sum_pc3 += (int)($p['pc3'] ?? 0);
                        $sum_pc4 += (int)($p['pc4'] ?? 0);
                        $sum_tdk += (int)($p['tdk'] ?? 0);
                        $sum_ta += (int)($p['ta'] ?? 0);
                        $sum_i += (int)($p['i'] ?? 0);
                        $sum_a += (int)($p['a'] ?? 0);
                        $sum_tot_persen += (float)($p['total_persen'] ?? 0);
                    ?>
                        <tr>
                            <td class="bg-light"><?= $no++ ?></td>
                            <td class="text-start ps-2">
                                <span class="fw-bold d-block text-dark"><?= $p['nama'] ?></span>
                                <span class="text-muted" style="font-size: 0.6rem;"><?= $p['nip'] ?></span>
                            </td>
                            <td class="small"><?= $p['pangkat'] ?><br><span class="text-muted" style="font-size: 0.6rem;"><?= $p['golongan'] ?></span></td>
                            <td class="text-start small"><?= $p['jab'] ?></td>
                            <td class="fw-bold text-success"><?= $p['hari_kerja'] ?></td>

                            <td><?= $p['p1'] ?: '-' ?></td>
                            <td><?= $p['p2'] ?: '-' ?></td>
                            <td><?= $p['p3'] ?: '-' ?></td>
                            <td><?= $p['p4'] ?: '-' ?></td>

                            <td><?= $p['pc1'] ?: '-' ?></td>
                            <td><?= $p['pc2'] ?: '-' ?></td>
                            <td><?= $p['pc3'] ?: '-' ?></td>
                            <td><?= $p['pc4'] ?: '-' ?></td>

                            <td class="bg-info bg-opacity-10 fw-bold"><?= $p['tdk'] ?: '-' ?></td>
                            <td class="bg-primary bg-opacity-10 fw-bold"><?= $p['ta'] ?: '-' ?></td>
                            <td><?= $p['i'] ?: '-' ?></td>
                            <td><?= $p['a'] ?: '-' ?></td>

                            <td class="bg-light text-muted small"><?= ($p['p1'] > 0) ? ($p['p1'] * 0.5) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['p2'] > 0) ? ($p['p2'] * 1) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['p3'] > 0) ? ($p['p3'] * 1.25) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['p4'] > 0) ? ($p['p4'] * 1.5) : '' ?></td>

                            <td class="bg-light text-muted small"><?= ($p['pc1'] > 0) ? ($p['pc1'] * 0.5) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['pc2'] > 0) ? ($p['pc2'] * 1) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['pc3'] > 0) ? ($p['pc3'] * 1.25) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['pc4'] > 0) ? ($p['pc4'] * 1.5) : '' ?></td>

                            <td class="bg-light text-muted small fw-bold text-info"><?= ($p['tdk'] > 0) ? ($p['tdk'] * 0.5) : '' ?></td>
                            <td class="bg-light text-muted small fw-bold text-primary"><?= ($p['ta'] > 0) ? ($p['ta'] * 2) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['i'] > 0) ? ($p['i'] * 0) : '' ?></td>
                            <td class="bg-light text-muted small"><?= ($p['a'] > 0) ? ($p['a'] * 3) : '' ?></td>

                            <td class="fw-bold text-white bg-danger"><?= $p['total_persen'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light fw-bold text-center" style="font-size: 0.7rem;">
                    <tr class="align-middle">
                        <td colspan="4" class="text-end pe-3 bg-white">TOTAL KESELURUHAN:</td>
                        <td class="text-success fw-bold"><?= $sum_hk ?></td>

                        <td><?= $sum_p1 ?: '-' ?></td>
                        <td><?= $sum_p2 ?: '-' ?></td>
                        <td><?= $sum_p3 ?: '-' ?></td>
                        <td><?= $sum_p4 ?: '-' ?></td>

                        <td><?= $sum_pc1 ?: '-' ?></td>
                        <td><?= $sum_pc2 ?: '-' ?></td>
                        <td><?= $sum_pc3 ?: '-' ?></td>
                        <td><?= $sum_pc4 ?: '-' ?></td>

                        <td class="bg-info bg-opacity-10 fw-bold"><?= $sum_tdk ?: '-' ?></td>
                        <td class="bg-primary bg-opacity-10 fw-bold"><?= $sum_ta ?: '-' ?></td>
                        <td><?= $sum_i ?: '-' ?></td>
                        <td><?= $sum_a ?: '-' ?></td>

                        <td class="bg-light text-muted small"><?= ($sum_p1 > 0) ? ($sum_p1 * 0.5) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_p2 > 0) ? ($sum_p2 * 1) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_p3 > 0) ? ($sum_p3 * 1.25) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_p4 > 0) ? ($sum_p4 * 1.5) : '-' ?></td>

                        <td class="bg-light text-muted small"><?= ($sum_pc1 > 0) ? ($sum_pc1 * 0.5) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_pc2 > 0) ? ($sum_pc2 * 1) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_pc3 > 0) ? ($sum_pc3 * 1.25) : '-' ?></td>
                        <td class="bg-light text-muted small"><?= ($sum_pc4 > 0) ? ($sum_pc4 * 1.5) : '-' ?></td>

                        <td class="bg-light text-muted small fw-bold text-info"><?= ($sum_tdk > 0) ? ($sum_tdk * 0.5) : '-' ?></td>
                        <td class="bg-light text-muted small fw-bold text-primary"><?= ($sum_ta > 0) ? ($sum_ta * 2) : '-' ?></td>
                        <td class="bg-light text-muted small">-</td>
                        <td class="bg-light text-muted small text-danger fw-bold"><?= ($sum_a > 0) ? ($sum_a * 3) : '-' ?></td>

                        <td class="fw-bold text-white bg-danger"><?= number_format($sum_tot_persen, 1) ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer bg-white p-4 border-top">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Keterangan :</h6>
            <ul class="list-unstyled text-muted mb-0" style="font-size: 0.75rem; line-height: 1.6;">
                <li><strong class="text-dark">Tb :</strong> Terlambat datang {(P1: s.d 30' = 0,5%), (P2: 31' s.d 60' = 1%), (P3: 61' s.d 90' = 1,25%), (P4: > 90' = 1,5%)} x jumlah keterlambatan 1 bulan</li>
                <li><strong class="text-dark">Pc :</strong> Pulang Cepat {(P1: s.d 30' = 0,5%), (P2: 31' s.d 60' = 1%), (P3: 61' s.d 90' = 1,25%), (P4: > 90' = 1,5%)} x jumlah pulang cepat 1 bulan</li>
                <li><strong class="text-dark">C :</strong> Cuti sakit lebih dari 12 hari s/d 1 tahun / Cuti bersalin ketiga / Cuti besar (0%)</li>
                <li><strong class="text-dark">I :</strong> Izin tidak masuk kerja / izin sakit / izin setelah cuti sakit (0% x jumlah hari tidak masuk)</li>
                <li><strong class="text-dark">A :</strong> Alpa (3% x jumlah hari Alpa)</li>
                <li><strong class="text-dark">TDK :</strong> Tidak mengikuti Dialog Kinerja Senin (0,5% x Jumlah tidak mengikuti Dialog dalam 1 bulan)</li>
                <li><strong class="text-dark">TA :</strong> Tidak Apel (2% x Jumlah Apel yang tidak diikuti)</li>
            </ul>
        </div>
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

<div class="modal fade" id="modalCetakPDF" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-print me-2"></i>Konfigurasi Penandatangan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formCetak">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Tempat</label>
                            <input type="text" id="inputTempat" class="form-control form-control-sm shadow-none" value="Polewali">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Tanggal Cetak</label>
                            <input type="date" id="inputTanggal" class="form-control form-control-sm shadow-none" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">Jabatan Penandatangan</label>
                            <input type="text" id="inputJabatan" class="form-control form-control-sm shadow-none" value="Kepala OPD / Unit Kerja">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">Nama Pejabat</label>
                            <input type="text" id="inputNamaPejabat" class="form-control form-control-sm shadow-none" placeholder="Masukkan nama lengkap...">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">NIP Pejabat</label>
                            <input type="text" id="inputNipPejabat" class="form-control form-control-sm shadow-none" placeholder="Masukkan NIP...">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="generatePDF()" class="btn btn-danger rounded-pill px-4 fw-bold">
                    <i class="fas fa-file-download me-1"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function generatePDF() {
        const tempat = document.getElementById('inputTempat').value || 'Polewali';
        const tglInput = document.getElementById('inputTanggal').value;
        const jabatan = document.getElementById('inputJabatan').value;
        const namaPejabat = document.getElementById('inputNamaPejabat').value;
        const nipPejabat = document.getElementById('inputNipPejabat').value;

        const dateObj = new Date(tglInput);
        const tanggalIndo = dateObj.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        const modalEl = document.getElementById('modalCetakPDF');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();

        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF('l', 'mm', [330, 215]); // Ukuran F4 Landscape

        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("TABEL PERHITUNGAN KETAATAN JAM KERJA & POTONGAN TPP", 165, 12, {
            align: "center"
        });

        doc.setFontSize(9);
        doc.setFont("helvetica", "normal");
        doc.text("UNIT KERJA: <?= strtoupper($nama_opd) ?>", 165, 18, {
            align: "center"
        });

        let strPeriode = "PERIODE: <?= strtoupper(date('F', mktime(0, 0, 0, $bulan, 10))) ?> <?= $tahun ?>";
        <?php if (!empty($selected_status_asn)): ?>
            strPeriode += " | STATUS ASN: <?= strtoupper($selected_status_asn) ?>";
        <?php endif; ?>

        doc.text(strPeriode, 165, 23, {
            align: "center"
        });

        doc.autoTable({
            html: '#tabel-tpp',
            startY: 30,
            theme: 'grid',
            styles: {
                fontSize: 5.5,
                cellPadding: 0.8,
                halign: 'center',
                valign: 'middle',
                lineColor: [0, 0, 0],
                lineWidth: 0.1
            },
            headStyles: {
                fillColor: [240, 240, 240],
                textColor: [0, 0, 0],
                fontStyle: 'bold'
            },
            columnStyles: {
                1: {
                    halign: 'left',
                    cellWidth: 35
                },
                2: {
                    halign: 'left',
                    cellWidth: 30
                },
                33: {
                    fillColor: [255, 200, 200],
                    fontStyle: 'bold'
                }
            }
        });

        let finalY = doc.lastAutoTable.finalY + 10;
        if (finalY > 170) {
            doc.addPage();
            finalY = 20;
        }

        doc.setFontSize(9);
        doc.text(`${tempat}, ${tanggalIndo}`, 280, finalY, {
            align: "center"
        });
        doc.text("Mengetahui,", 280, finalY + 5, {
            align: "center"
        });
        doc.text(jabatan + ",", 280, finalY + 10, {
            align: "center"
        });

        const namaText = namaPejabat ? `( ${namaPejabat} )` : "( ........................................................ )";
        const nipText = nipPejabat ? `NIP. ${nipPejabat}` : "NIP. ........................................................";

        doc.setFont("helvetica", "bold");
        doc.text(namaText, 280, finalY + 30, {
            align: "center"
        });
        doc.setFont("helvetica", "normal");
        doc.text(nipText, 280, finalY + 35, {
            align: "center"
        });

        doc.save("Laporan_TPP_<?= str_replace([' ', '|'], '_', $nama_opd) ?>_<?= $bulan ?>_<?= $tahun ?>.pdf");
    }
</script>

<?= $this->endSection() ?>