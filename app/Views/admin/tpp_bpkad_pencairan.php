<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .stat-card-bpkad {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card-bpkad:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
    }
    .table-bpkad th {
        font-size: 0.72rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .table-bpkad td {
        font-size: 0.78rem;
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-0">

    <!-- HEADER TITLE -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-hand-holding-usd text-warning me-2"></i> Daftar Pencairan TPP ASN (BPKAD)
            </h4>
            <p class="text-muted small mb-0">
                Penerimaan daftar rekapitulasi TPP ASN yang telah diverifikasi & diterbitkan rekomendasi oleh BKPSDM untuk proses penerbitan SP2D.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('tpp_dinamis') ?>" class="btn btn-outline-primary btn-sm rounded-pill shadow-xs px-3">
                <i class="fas fa-coins me-1"></i> Rekap TPP BK & KK
            </a>
            <a href="<?= base_url('cetaktpp') ?>" class="btn btn-outline-secondary btn-sm rounded-pill shadow-xs px-3">
                <i class="fas fa-file-invoice-dollar me-1"></i> Rekap TPP Reguler
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= base_url('tpp_dinamis/bpkad') ?>" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Status Pencairan</label>
                    <select name="status" class="form-select form-select-sm border-secondary shadow-none">
                        <option value="">-- Semua Status --</option>
                        <option value="rekomendasi_bkpsdm" <?= ($status_filter == 'rekomendasi_bkpsdm') ? 'selected' : '' ?>>Siap Dicairkan (Menunggu SP2D)</option>
                        <option value="dicairkan" <?= ($status_filter == 'dicairkan') ? 'selected' : '' ?>>Telah Dicairkan (SP2D Terbit)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm border-secondary shadow-none">
                        <?php 
                        $bln_indo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                        foreach ($bln_indo as $num => $nama_bln): ?>
                            <option value="<?= $num ?>" <?= ($bulan == $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Tahun</label>
                    <input type="number" name="tahun" class="form-control form-control-sm border-secondary shadow-none" value="<?= $tahun ?>" min="2020" max="2035">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-filter me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- STATS CARDS BPKAD -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl-3">
            <div class="card stat-card-bpkad border-0 shadow-sm bg-white p-3 text-center border-start border-warning border-4">
                <span class="text-muted small fw-semibold text-uppercase">Siap Dicairkan</span>
                <h4 class="fw-bold text-warning mb-0 mt-1"><?= number_format($stats['total_siap_cair_opd']) ?> OPD</h4>
                <div class="small fw-bold text-dark mt-1">Rp <?= number_format($stats['total_siap_cair_nominal'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="card stat-card-bpkad border-0 shadow-sm bg-white p-3 text-center border-start border-success border-4">
                <span class="text-muted small fw-semibold text-uppercase">Telah Dicairkan</span>
                <h4 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['total_sudah_cair_opd']) ?> OPD</h4>
                <div class="small fw-bold text-dark mt-1">Rp <?= number_format($stats['total_sudah_cair_nominal'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="card stat-card-bpkad border-0 shadow-sm bg-white p-3 text-center border-start border-primary border-4">
                <span class="text-muted small fw-semibold text-uppercase">Total Pegawai Tercover</span>
                <h4 class="fw-bold text-primary mb-0 mt-1"><?= number_format($stats['total_pegawai_tercover']) ?></h4>
                <div class="small text-muted mt-1">ASN Penerima TPP</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="card stat-card-bpkad border-0 shadow-sm bg-white p-3 text-center border-start border-info border-4">
                <span class="text-muted small fw-semibold text-uppercase">Total Pagu Siap + Cair</span>
                <h5 class="fw-bold text-info mb-0 mt-1">Rp <?= number_format($stats['total_siap_cair_nominal'] + $stats['total_sudah_cair_nominal'], 0, ',', '.') ?></h5>
                <div class="small text-muted mt-1">Akumulasi Periode <?= $bln_indo[$bulan] ?? $bulan ?></div>
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR TPP SIAP CAIR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-money-check-alt text-warning me-2"></i> Daftar TPP Terverifikasi Periode <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>
            </h6>
            <span class="badge bg-light text-muted border"><?= count($list_cair) ?> Instansi Siap / Selesai</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bpkad mb-0 align-middle">
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Instansi / OPD</th>
                        <th>Rekomendasi BKPSDM</th>
                        <th class="text-center">ASN</th>
                        <th class="text-end">Nominal Bersih (Rp)</th>
                        <th class="text-center">Status</th>
                        <th>Data SP2D / Pencairan</th>
                        <th class="text-center" style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_cair)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-check-double fa-3x mb-2 text-warning opacity-50 d-block"></i>
                                Belum ada usulan TPP yang direkomendasikan BKPSDM untuk periode <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($list_cair as $row): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++ ?></td>
                                <td>
                                    <span class="fw-bold text-dark"><?= esc($row['nama_opd']) ?></span>
                                    <div class="small text-muted" style="font-size: 0.7rem;">ID OPD: <?= $row['id_opd'] ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white"><i class="fas fa-stamp me-1"></i> <?= esc($row['no_rekomendasi'] ?? '-') ?></span>
                                    <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                        Oleh: <?= esc($row['verval_oleh_bkpsdm'] ?? 'BKPSDM') ?> (<?= !empty($row['tgl_verval_bkpsdm']) ? date('d/m/Y', strtotime($row['tgl_verval_bkpsdm'])) : '-' ?>)
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= number_format($row['total_pegawai']) ?> ASN</span>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    Rp <?= number_format($row['total_tpp_bersih'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status'] == 'rekomendasi_bkpsdm'): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1 shadow-xs">
                                            <i class="fas fa-clock me-1"></i> Siap Dicairkan
                                        </span>
                                    <?php elseif ($row['status'] == 'dicairkan'): ?>
                                        <span class="badge bg-success px-2 py-1 shadow-xs">
                                            <i class="fas fa-check-circle me-1"></i> Telah Dicairkan
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['no_sp2d'])): ?>
                                        <div class="small fw-bold text-success"><i class="fas fa-file-invoice me-1"></i> SP2D: <?= esc($row['no_sp2d']) ?></div>
                                        <div class="small text-muted" style="font-size: 0.7rem;">Tgl: <?= !empty($row['tgl_cair_bpkad']) ? date('d M Y', strtotime($row['tgl_cair_bpkad'])) : '-' ?></div>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">Menunggu input SP2D</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <!-- Tombol Periksa Rekap Lengkap -->
                                        <a href="<?= base_url('tpp_dinamis?id_opd=' . $row['id_opd'] . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 shadow-xs" title="Lihat Rekap TPP Pegawai">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Tombol Export Excel -->
                                        <a href="<?= base_url('tpp_dinamis/excel?id_opd=' . $row['id_opd'] . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-success btn-sm rounded-pill px-2 py-1 shadow-xs" title="Unduh Excel Lampiran">
                                            <i class="fas fa-file-excel"></i>
                                        </a>

                                        <!-- Tombol Proses Pencairan jika belum dicairkan -->
                                        <?php if ($row['status'] == 'rekomendasi_bkpsdm'): ?>
                                            <button type="button" class="btn btn-warning text-dark btn-sm rounded-pill px-2 py-1 shadow-xs fw-bold" onclick="openModalCairkan(<?= $row['id'] ?>, '<?= esc($row['nama_opd']) ?>', '<?= esc($row['no_rekomendasi']) ?>', 'Rp <?= number_format($row['total_tpp_bersih'], 0, ',', '.') ?>')" title="Proses Pencairan / Input SP2D">
                                                <i class="fas fa-money-check-alt me-1"></i> Cairkan
                                            </button>
                                        <?php else: ?>
                                            <!-- Tombol Edit SP2D -->
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1 shadow-xs" onclick="openModalCairkan(<?= $row['id'] ?>, '<?= esc($row['nama_opd']) ?>', '<?= esc($row['no_rekomendasi']) ?>', 'Rp <?= number_format($row['total_tpp_bersih'], 0, ',', '.') ?>', '<?= esc($row['no_sp2d']) ?>', '<?= date('Y-m-d', strtotime($row['tgl_cair_bpkad'])) ?>')" title="Ubah SP2D">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Cetak Lembar Verifikasi & Pencairan -->
                                        <a href="<?= base_url('tpp_dinamis/cetak_lembar_pencairan/' . $row['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-2 py-1 shadow-xs" title="Cetak Lembar Pencairan">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL INPUT SP2D & EKSEKUSI PENCAIRAN (BPKAD) -->
<div class="modal fade" id="modalDialogCairkan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-money-check-alt me-2"></i> Eksekusi Pencairan TPP (Input SP2D)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('tpp_dinamis/proses_pencairan') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pengajuan" id="cair_id_pengajuan">

                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">OPD Penerima:</span>
                            <span class="small fw-bold text-dark text-end" id="cair_nama_opd">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">No. Rekomendasi BKPSDM:</span>
                            <span class="small fw-bold text-primary" id="cair_no_rekomendasi">-</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                            <span class="small fw-bold text-dark">Total Dana Dicairkan:</span>
                            <span class="small fw-bold text-success fs-6" id="cair_nominal">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Nomor SP2D (Surat Perintah Pencairan Dana) <span class="text-danger">*</span></label>
                        <input type="text" name="no_sp2d" id="cair_no_sp2d" class="form-control form-control-sm" placeholder="Contoh: 900/0124/SP2D-TPP/BPKAD/<?= date('Y') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Tanggal SP2D / Pencairan <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_cair" id="cair_tgl" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Catatan Pencairan BPKAD (Opsional)</label>
                        <textarea name="catatan_bpkad" id="cair_catatan" class="form-control form-control-sm" rows="2" placeholder="Nomor registrasi SPM atau keterangan perbendaharaan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> Simpan & Konfirmasi Dicairkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModalCairkan(id, namaOpd, noRekom, nominal, noSp2d = '', tglCair = '<?= date('Y-m-d') ?>') {
        document.getElementById('cair_id_pengajuan').value = id;
        document.getElementById('cair_nama_opd').innerText = namaOpd;
        document.getElementById('cair_no_rekomendasi').innerText = noRekom || '-';
        document.getElementById('cair_nominal').innerText = nominal;
        document.getElementById('cair_no_sp2d').value = noSp2d;
        document.getElementById('cair_tgl').value = tglCair;

        var modal = new bootstrap.Modal(document.getElementById('modalDialogCairkan'));
        modal.show();
    }
</script>

<?= $this->endSection() ?>

