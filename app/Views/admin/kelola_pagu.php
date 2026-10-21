<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-coins text-warning me-2"></i> Kelola Master Pagu TPP (BK & KK)
            </h4>
            <p class="text-muted small mb-0">
                Manajemen nominal dasar pagu TPP Beban Kerja (BK) & Kondisi Kerja (KK) per pegawai/jabatan (Pagu Tetap).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('tpp_dinamis') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap
            </a>
            <a href="<?= base_url('tpp_dinamis/download_template_pagu?id_opd=' . $selected_opd) ?>" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas fa-download me-1"></i> Template Pagu Excel
            </a>
            <button type="button" class="btn btn-warning text-dark btn-sm rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadPagu">
                <i class="fas fa-upload me-1"></i> Upload Pagu TPP
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= base_url('tpp_dinamis/kelola_pagu') ?>" method="GET" class="row g-2 align-items-end">
                <?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
                    <div class="col-md-9">
                        <label class="small fw-bold text-muted mb-1">Pilih Unit Kerja (OPD)</label>
                        <select name="id_opd" class="form-select form-select-sm border-secondary shadow-none" required>
                            <?php foreach ($list_opd as $o): ?>
                                <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>>
                                    <?= esc($o['nama_opd']) ?> <?= !empty($o['tpp_dinamis_aktif']) ? '★' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-search me-1"></i> Buka Data Pagu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-building me-2 text-warning"></i> Master Pagu TPP: <?= esc($opd_info->nama_opd ?? 'OPD') ?>
            </h6>
            <span class="badge bg-warning-subtle text-dark border border-warning">
                Total Pegawai: <?= count($pegawai) ?>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" style="font-size: 0.8rem;">
                <thead class="bg-light text-dark fw-bold">
                    <tr>
                        <th style="width: 40px;">NO</th>
                        <th class="text-start ps-3" style="width: 240px;">NAMA / NIP</th>
                        <th class="text-start">JABATAN / GOLONGAN</th>
                        <th style="width: 160px;" class="text-end pe-3">TPP BK (Beban Kerja)</th>
                        <th style="width: 160px;" class="text-end pe-3">TPP KK (Kondisi Kerja)</th>
                        <th style="width: 160px;" class="text-end pe-3 bg-light">TOTAL PAGU TPP</th>
                        <th style="width: 110px;">STATUS</th>
                        <th style="width: 80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pegawai)): ?>
                        <tr>
                            <td colspan="8" class="py-4 text-muted">Belum ada data pegawai untuk OPD ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($pegawai as $p): 
                            $bk = (float)($p['tpp_bk'] ?? 0);
                            $kk = (float)($p['tpp_kk'] ?? 0);
                            $total_pagu = $bk + $kk;
                            $has_pagu = !empty($p['id_pagu']) || ($total_pagu > 0);
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="text-start ps-3">
                                    <div class="fw-bold text-dark"><?= esc($p['nama_lengkap']) ?></div>
                                    <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= $p['nip'] ?></div>
                                </td>
                                <td class="text-start">
                                    <div><?= esc($p['jabatan']) ?></div>
                                    <small class="text-muted"><?= esc($p['pangkat']) ?> (<?= esc($p['golongan']) ?>)</small>
                                </td>
                                <td class="text-end pe-3 fw-bold text-primary">
                                    Rp <?= number_format($bk, 0, ',', '.') ?>
                                </td>
                                <td class="text-end pe-3 fw-bold text-secondary">
                                    Rp <?= number_format($kk, 0, ',', '.') ?>
                                </td>
                                <td class="text-end pe-3 fw-bold text-dark bg-light">
                                    Rp <?= number_format($total_pagu, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?php if ($has_pagu): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Tersetting</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-warning text-dark btn-sm btn-icon rounded-circle btnEditPagu"
                                        data-nip="<?= $p['nip'] ?>"
                                        data-nama="<?= esc($p['nama_lengkap']) ?>"
                                        data-jabatan="<?= esc($p['jabatan']) ?>"
                                        data-bk="<?= $bk ?>"
                                        data-kk="<?= $kk ?>"
                                        title="Edit Pagu TPP Pegawai Ini">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Upload Pagu -->
<div class="modal fade" id="modalUploadPagu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i> Upload Master Pagu TPP (BK & KK)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadPagu" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_opd" value="<?= $selected_opd ?>">
                    <input type="hidden" name="bulan" value="<?= date('n') ?>">
                    <input type="hidden" name="tahun" value="<?= date('Y') ?>">

                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <b>Keterangan Pagu TPP:</b>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Data pagu ini bersifat <b>master/permanen</b> per pegawai/jabatan.</li>
                            <li>Dukungan file: Sheet <b>TPP REKAP BK&KK</b> (file resmi BKPSDM) atau <b>Template Pagu TPP</b>.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih File Excel (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <a href="<?= base_url('tpp_dinamis/download_template_pagu?id_opd=' . $selected_opd) ?>" class="small text-decoration-none">
                            <i class="fas fa-download me-1"></i> Unduh Format Template Pagu
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnUploadPaguSubmit" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-4">
                        <i class="fas fa-upload me-1"></i> Upload Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pagu Single -->
<div class="modal fade" id="modalEditPagu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-warning"></i> Edit Pagu TPP Pegawai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditPagu">
                <div class="modal-body p-4">
                    <input type="hidden" name="nip" id="modal_pagu_nip">

                    <div class="mb-3 p-3 bg-light rounded-3">
                        <div class="fw-bold text-dark" id="modal_pagu_nama">-</div>
                        <div class="small text-muted font-monospace" id="modal_pagu_nip_display">-</div>
                        <div class="small text-secondary" id="modal_pagu_jabatan">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP BK (Beban Kerja)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" step="0.01" name="tpp_bk" id="modal_pagu_bk" class="form-control" placeholder="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP KK (Kondisi Kerja)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" step="0.01" name="tpp_kk" id="modal_pagu_kk" class="form-control" placeholder="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditPaguSubmit" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-4">
                        <i class="fas fa-save me-1"></i> Simpan Pagu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Edit Pagu Trigger
    document.querySelectorAll('.btnEditPagu').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('modal_pagu_nip').value = this.dataset.nip;
            document.getElementById('modal_pagu_nip_display').innerText = this.dataset.nip;
            document.getElementById('modal_pagu_nama').innerText = this.dataset.nama;
            document.getElementById('modal_pagu_jabatan').innerText = this.dataset.jabatan;
            document.getElementById('modal_pagu_bk').value = this.dataset.bk;
            document.getElementById('modal_pagu_kk').value = this.dataset.kk;

            new bootstrap.Modal(document.getElementById('modalEditPagu')).show();
        });
    });

    // Form Upload Pagu Submit
    document.getElementById('formUploadPagu').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('btnUploadPaguSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengupload...';

        fetch('<?= base_url('tpp_dinamis/upload_pagu') ?>', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload Sekarang';
            if (res.status === 'success') {
                Swal.fire({icon: 'success', title: 'Berhasil', text: res.message}).then(() => location.reload());
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: res.message});
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload Sekarang';
            Swal.fire({icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.'});
        });
    });

    // Form Edit Pagu Single Submit
    document.getElementById('formEditPagu').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('btnEditPaguSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        fetch('<?= base_url('tpp_dinamis/save_manual_pagu') ?>', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Pagu';
            if (res.status === 'success') {
                Swal.fire({icon: 'success', title: 'Berhasil', text: res.message}).then(() => location.reload());
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: res.message});
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Pagu';
            Swal.fire({icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.'});
        });
    });
});
</script>

<?= $this->endSection() ?>
