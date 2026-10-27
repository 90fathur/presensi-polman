<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .stat-card-verval {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card-verval:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
    }
    .table-verval th {
        font-size: 0.72rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .table-verval td {
        font-size: 0.78rem;
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-0">

    <!-- HEADER TITLE -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-stamp text-success me-2"></i> Verifikasi & Rekomendasi TPP OPD (BKPSDM)
            </h4>
            <p class="text-muted small mb-0">
                Pemeriksaan dan validasi usulan TPP ASN seluruh OPD sebelum diterbitkan surat rekomendasi ke BPKAD.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('tpp_dinamis') ?>" class="btn btn-outline-primary btn-sm rounded-pill shadow-xs px-3">
                <i class="fas fa-coins me-1"></i> Rekap TPP BK & KK
            </a>
            <a href="<?= base_url('tpp_dinamis/setting_opd') ?>" class="btn btn-outline-secondary btn-sm rounded-pill shadow-xs px-3">
                <i class="fas fa-sliders-h me-1"></i> Setting OPD
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= base_url('tpp_dinamis/verval') ?>" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Status Pengajuan</label>
                    <select name="status" class="form-select form-select-sm border-secondary shadow-none">
                        <option value="">-- Semua Status --</option>
                        <option value="diajukan" <?= ($status_filter == 'diajukan') ? 'selected' : '' ?>>Menunggu Rekomendasi</option>
                        <option value="rekomendasi_bkpsdm" <?= ($status_filter == 'rekomendasi_bkpsdm') ? 'selected' : '' ?>>Direkomendasikan (Ke BPKAD)</option>
                        <option value="ditolak_bkpsdm" <?= ($status_filter == 'ditolak_bkpsdm') ? 'selected' : '' ?>>Dikembalikan ke OPD</option>
                        <option value="dicairkan" <?= ($status_filter == 'dicairkan') ? 'selected' : '' ?>>Telah Dicairkan BPKAD</option>
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
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-filter me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- METRICS CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-primary border-4">
                <span class="text-muted small fw-semibold text-uppercase">Total Pengajuan</span>
                <h4 class="fw-bold text-primary mb-0 mt-1"><?= number_format($stats['total_opd_mengajukan']) ?></h4>
                <span class="small text-muted" style="font-size: 0.65rem;">OPD Periode Ini</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-warning border-4">
                <span class="text-muted small fw-semibold text-uppercase">Menunggu Verval</span>
                <h4 class="fw-bold text-warning mb-0 mt-1"><?= number_format($stats['menunggu_rekomendasi']) ?></h4>
                <span class="small text-muted" style="font-size: 0.65rem;">Perlu Rekomendasi</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-info border-4">
                <span class="text-muted small fw-semibold text-uppercase">Direkomendasikan</span>
                <h4 class="fw-bold text-info mb-0 mt-1"><?= number_format($stats['direkomendasikan']) ?></h4>
                <span class="small text-muted" style="font-size: 0.65rem;">Diteruskan ke BPKAD</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-danger border-4">
                <span class="text-muted small fw-semibold text-uppercase">Dikembalikan</span>
                <h4 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['dikembalikan']) ?></h4>
                <span class="small text-muted" style="font-size: 0.65rem;">Perbaikan OPD</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-success border-4">
                <span class="text-muted small fw-semibold text-uppercase">Telah Dicairkan</span>
                <h4 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['dicairkan']) ?></h4>
                <span class="small text-muted" style="font-size: 0.65rem;">Oleh BPKAD</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card-verval border-0 shadow-sm bg-white p-3 text-center border-start border-dark border-4">
                <span class="text-muted small fw-semibold text-uppercase">Total Dana Bersih</span>
                <h6 class="fw-bold text-dark mb-0 mt-1">Rp <?= number_format($stats['total_nominal_bersih'], 0, ',', '.') ?></h6>
                <span class="small text-muted" style="font-size: 0.65rem;">Usulan Masuk</span>
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR USULAN TPP DARI OPD -->
    <div class="card shadow-sm border-0 mb-4 rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-list-check text-primary me-2"></i> Daftar Usulan TPP Periode <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>
            </h6>
            <span class="badge bg-light text-muted border"><?= count($list_pengajuan) ?> Usulan Ditemukan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped table-verval mb-0 align-middle">
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Instansi / OPD</th>
                        <th>Diajukan Oleh</th>
                        <th class="text-center">Jumlah ASN</th>
                        <th class="text-end">Total TPP Bersih</th>
                        <th class="text-center">Status</th>
                        <th>No. Rekomendasi / Keterangan</th>
                        <th class="text-center" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_pengajuan)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                Belum ada usulan TPP yang masuk untuk periode <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($list_pengajuan as $row): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++ ?></td>
                                <td>
                                    <span class="fw-bold text-dark"><?= esc($row['nama_opd']) ?></span>
                                    <div class="small text-muted" style="font-size: 0.7rem;">ID OPD: <?= $row['id_opd'] ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($row['diajukan_oleh'] ?? '-') ?></div>
                                    <div class="small text-muted" style="font-size: 0.7rem;"><?= !empty($row['tgl_diajukan']) ? date('d M Y H:i', strtotime($row['tgl_diajukan'])) : '-' ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= number_format($row['total_pegawai']) ?> Pegawai</span>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp <?= number_format($row['total_tpp_bersih'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status'] == 'diajukan'): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i> Menunggu Rekomendasi</span>
                                    <?php elseif ($row['status'] == 'rekomendasi_bkpsdm'): ?>
                                        <span class="badge bg-info text-white px-2 py-1"><i class="fas fa-stamp me-1"></i> Direkomendasikan</span>
                                    <?php elseif ($row['status'] == 'ditolak_bkpsdm'): ?>
                                        <span class="badge bg-danger px-2 py-1"><i class="fas fa-undo-alt me-1"></i> Dikembalikan</span>
                                    <?php elseif ($row['status'] == 'dicairkan'): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i> Dicairkan BPKAD</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2 py-1">Draf</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['no_rekomendasi'])): ?>
                                        <div class="small fw-bold text-primary"><i class="fas fa-file-alt me-1"></i> <?= esc($row['no_rekomendasi']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['no_sp2d'])): ?>
                                        <div class="small fw-semibold text-success"><i class="fas fa-money-check me-1"></i> SP2D: <?= esc($row['no_sp2d']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['catatan_bkpsdm'])): ?>
                                        <div class="small text-muted fst-italic" style="font-size: 0.7rem;">BKPSDM: "<?= esc($row['catatan_bkpsdm']) ?>"</div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['catatan_opd'])): ?>
                                        <div class="small text-muted fst-italic" style="font-size: 0.7rem;">OPD: "<?= esc($row['catatan_opd']) ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <!-- Tombol Periksa Rekap Lengkap -->
                                        <a href="<?= base_url('tpp_dinamis?id_opd=' . $row['id_opd'] . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 shadow-xs" title="Periksa Rincian TPP">
                                            <i class="fas fa-eye me-1"></i> Periksa
                                        </a>

                                        <!-- Tombol Rekomendasi jika status = diajukan -->
                                        <?php if ($row['status'] == 'diajukan'): ?>
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-2 py-1 shadow-xs" onclick="openModalRekomendasi(<?= $row['id'] ?>, '<?= esc($row['nama_opd']) ?>')" title="Terbitkan Rekomendasi">
                                                <i class="fas fa-stamp"></i> Rekomendasi
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-2 py-1 shadow-xs" onclick="openModalTolak(<?= $row['id'] ?>, '<?= esc($row['nama_opd']) ?>')" title="Kembalikan ke OPD">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Cetak Lembar Rekomendasi jika sudah direkomendasikan / dicairkan -->
                                        <?php if (in_array($row['status'], ['rekomendasi_bkpsdm', 'dicairkan'])): ?>
                                            <a href="<?= base_url('tpp_dinamis/cetak_lembar_pencairan/' . $row['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-2 py-1 shadow-xs" title="Cetak Surat Rekomendasi">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- OPD PROTOTYPE YANG BELUM MENGAJUKAN -->
    <?php if (!empty($opd_belum_mengajukan)): ?>
        <div class="card shadow-sm border-0 mb-4 rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-muted">
                    <i class="fas fa-hourglass-start text-warning me-2"></i> OPD Prototype Belum Mengajukan (<?= count($opd_belum_mengajukan) ?> Instansi)
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <?php foreach ($opd_belum_mengajukan as $opd): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="p-2 border rounded d-flex justify-content-between align-items-center bg-light">
                                <span class="small fw-semibold text-dark text-truncate me-2"><?= esc($opd['nama_opd']) ?></span>
                                <a href="<?= base_url('tpp_dinamis?id_opd=' . $opd['id_opd'] . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-secondary btn-xs rounded-pill px-2 py-0" style="font-size: 0.68rem;">
                                    Buka Rekap
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL DIALOG BERI REKOMENDASI -->
<div class="modal fade" id="modalActionRekomendasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-stamp me-2"></i> Terbitkan Rekomendasi BKPSDM (Ke BPKAD)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('tpp_dinamis/proses_rekomendasi') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pengajuan" id="rekomendasi_id_pengajuan">
                <input type="hidden" name="aksi" value="rekomendasi">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Nama Instansi / OPD</label>
                        <input type="text" id="rekomendasi_nama_opd" class="form-control form-control-sm bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Nomor Surat Rekomendasi <span class="text-danger">*</span></label>
                        <input type="text" name="no_rekomendasi" class="form-control form-control-sm" placeholder="Contoh: 800/125/BKPSDM/<?= date('m/Y') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Catatan Hasil Verval (Opsional)</label>
                        <textarea name="catatan_bkpsdm" class="form-control form-control-sm" rows="3" placeholder="Tuliskan catatan rekomendasi atau keterangan verifikasi..."></textarea>
                    </div>

                    <div class="alert alert-info border-0 small py-2 mb-0">
                        <i class="fas fa-paper-plane me-1"></i> Setelah disimpan, berkas usulan TPP otomatis diteruskan ke portal <b>BPKAD</b> untuk pencairan.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold">
                        <i class="fas fa-stamp me-1"></i> Terbitkan Rekomendasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DIALOG TOLAK / KEMBALIKAN KE OPD -->
<div class="modal fade" id="modalActionTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-3">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-undo-alt me-2"></i> Kembalikan Usulan TPP ke OPD
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('tpp_dinamis/proses_rekomendasi') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pengajuan" id="tolak_id_pengajuan">
                <input type="hidden" name="aksi" value="tolak">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Nama Instansi / OPD</label>
                        <input type="text" id="tolak_nama_opd" class="form-control form-control-sm bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Catatan Alasan Pengembalian / Perbaikan <span class="text-danger">*</span></label>
                        <textarea name="catatan_bkpsdm" class="form-control form-control-sm" rows="4" placeholder="Jelaskan secara spesifik ketidakhadiran atau capaian kinerja yang harus diperbaiki OPD..." required></textarea>
                    </div>

                    <div class="alert alert-warning border-0 small py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> OPD terkait akan melihat catatan ini dan dapat mengajukan ulang setelah perbaikan.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold">
                        <i class="fas fa-undo-alt me-1"></i> Kembalikan ke OPD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModalRekomendasi(id, namaOpd) {
        document.getElementById('rekomendasi_id_pengajuan').value = id;
        document.getElementById('rekomendasi_nama_opd').value = namaOpd;
        var modal = new bootstrap.Modal(document.getElementById('modalActionRekomendasi'));
        modal.show();
    }

    function openModalTolak(id, namaOpd) {
        document.getElementById('tolak_id_pengajuan').value = id;
        document.getElementById('tolak_nama_opd').value = namaOpd;
        var modal = new bootstrap.Modal(document.getElementById('modalActionTolak'));
        modal.show();
    }
</script>

<?= $this->endSection() ?>

