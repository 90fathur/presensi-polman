<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-file-invoice-dollar text-success me-2"></i> Kelola Data E-Kinerja & Pagu TPP
            </h4>
            <p class="text-muted small mb-0">
                Manajemen manual data WKE, Capaian Kinerja, dan Pagu Dasar TPP (Beban Kerja & Kondisi Kerja) per pegawai.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('tpp_dinamis?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap
            </a>
            <a href="<?= base_url('tpp_dinamis/download_template?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas fa-download me-1"></i> Template Excel
            </a>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUpload">
                <i class="fas fa-upload me-1"></i> Upload File Excel
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= base_url('tpp_dinamis/kelola_ekin') ?>" method="GET" class="row g-2 align-items-end">
                <?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
                    <div class="col-md-5">
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
                    <label class="small fw-bold text-muted mb-1">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm border-secondary shadow-none">
                        <?php 
                        $bln_indo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                        foreach ($bln_indo as $num => $nama_bln): ?>
                            <option value="<?= $num ?>" <?= ($bulan == $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Tahun</label>
                    <input type="number" name="tahun" class="form-control form-control-sm border-secondary shadow-none" value="<?= $tahun ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-search me-1"></i> Buka Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-users me-2 text-primary"></i> Data Pegawai: <?= esc($opd_info->nama_opd ?? 'OPD') ?>
            </h6>
            <span class="badge bg-light text-dark border">
                Periode: <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" style="font-size: 0.78rem;">
                <thead class="bg-light text-dark fw-bold">
                    <tr>
                        <th style="width: 40px;">NO</th>
                        <th class="text-start ps-3" style="width: 220px;">NAMA / NIP</th>
                        <th>JABATAN</th>
                        <th style="width: 80px;">WKE</th>
                        <th style="width: 100px;">PREDIKAT</th>
                        <th style="width: 90px;">% KINERJA</th>
                        <th style="width: 120px;" class="text-end pe-3">TPP BK</th>
                        <th style="width: 120px;" class="text-end pe-3">TPP KK</th>
                        <th style="width: 100px;">STATUS</th>
                        <th style="width: 80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pegawai)): ?>
                        <tr>
                            <td colspan="10" class="py-4 text-muted">Belum ada data pegawai untuk OPD ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($pegawai as $p): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= $no++ ?></td>
                                <td class="text-start ps-3">
                                    <div class="fw-bold text-dark"><?= esc($p['nama_lengkap']) ?></div>
                                    <div class="text-muted small">NIP. <?= esc($p['nip']) ?></div>
                                </td>
                                <td class="text-start small"><?= esc($p['jabatan']) ?></td>
                                <td class="fw-bold text-primary"><?= esc($p['wke'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= esc($p['predikat_kinerja'] ?? 'BAIK') ?></span>
                                </td>
                                <td class="fw-bold text-success"><?= number_format($p['persen_kinerja'] ?? 100, 0) ?>%</td>
                                <td class="text-end pe-3">Rp <?= number_format($p['tpp_bk'] ?? 0, 0, ',', '.') ?></td>
                                <td class="text-end pe-3">Rp <?= number_format($p['tpp_kk'] ?? 0, 0, ',', '.') ?></td>
                                <td>
                                    <?php if (!empty($p['id_ekin'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Terisi</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">Default</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-circle py-0 px-2"
                                            onclick="openEditModal('<?= $p['nip'] ?>', '<?= addslashes($p['nama_lengkap']) ?>', '<?= $p['wke'] ?>', '<?= $p['persen_kinerja'] ?>', '<?= $p['tpp_bk'] ?>', '<?= $p['tpp_kk'] ?>', '<?= $p['predikat_kinerja'] ?>')">
                                        <i class="fas fa-edit" style="font-size: 0.7rem;"></i>
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

<!-- Modal Upload -->
<div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-file-excel me-2"></i> Upload File E-Kinerja</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadModal" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_opd" value="<?= $selected_opd ?>">

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold text-muted mb-1">Target Periode Bulan</label>
                            <select name="bulan" class="form-select form-select-sm" required>
                                <?php foreach ($bln_indo as $num => $nama_bln): ?>
                                    <option value="<?= $num ?>" <?= ($bulan == $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold text-muted mb-1">Target Tahun</label>
                            <input type="number" name="tahun" class="form-control form-control-sm" value="<?= $tahun ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih File Excel (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnUploadModalSubmit" class="btn btn-success btn-sm rounded-pill px-4">Upload Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Single -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-warning"></i> Edit Data Pegawai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditSingle">
                <div class="modal-body p-4">
                    <input type="hidden" name="nip" id="modal_nip">
                    <input type="hidden" name="bulan" value="<?= $bulan ?>">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">

                    <div class="p-2 bg-light rounded-3 mb-3">
                        <div class="fw-bold text-dark" id="modal_nama">-</div>
                        <div class="small text-muted" id="modal_nip_txt">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">Jumlah WKE</label>
                            <input type="number" name="wke" id="modal_wke" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">% Kinerja</label>
                            <input type="number" step="0.01" name="persen_kinerja" id="modal_pct" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP BK</label>
                            <input type="number" step="0.01" name="tpp_bk" id="modal_bk" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP KK</label>
                            <input type="number" step="0.01" name="tpp_kk" id="modal_kk" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1">Rating / Predikat</label>
                            <select name="predikat_kinerja" id="modal_predikat" class="form-select form-select-sm">
                                <option value="SANGAT BAIK">SANGAT BAIK</option>
                                <option value="BAIK">BAIK</option>
                                <option value="BUTUH PERBAIKAN">BUTUH PERBAIKAN</option>
                                <option value="KURANG">KURANG</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnEditSubmit" class="btn btn-primary btn-sm rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openEditModal(nip, nama, wke, pct, bk, kk, predikat) {
        document.getElementById('modal_nip').value = nip;
        document.getElementById('modal_nama').innerText = nama;
        document.getElementById('modal_nip_txt').innerText = 'NIP. ' + nip;
        document.getElementById('modal_wke').value = wke || '0';
        document.getElementById('modal_pct').value = pct || '100';
        document.getElementById('modal_bk').value = bk || '0';
        document.getElementById('modal_kk').value = kk || '0';
        document.getElementById('modal_predikat').value = predikat || 'BAIK';

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    document.getElementById('formUploadModal').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('btnUploadModalSubmit');
        btn.disabled = true;
        btn.innerText = 'Mengupload...';

        const fd = new FormData(this);
        fetch('<?= base_url('tpp_dinamis/upload_ekin') ?>', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerText = 'Upload Sekarang';
            if (res.status === 'success') {
                Swal.fire({icon: 'success', title: 'Berhasil', text: res.message}).then(() => {
                    const targetBln = fd.get('bulan') || '<?= $bulan ?>';
                    const targetThn = fd.get('tahun') || '<?= $tahun ?>';
                    window.location.href = '<?= base_url('tpp_dinamis/kelola_ekin') ?>?id_opd=<?= $selected_opd ?>&bulan=' + encodeURIComponent(targetBln) + '&tahun=' + encodeURIComponent(targetThn);
                });
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: res.message});
            }
        });
    });

    document.getElementById('formEditSingle').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('btnEditSubmit');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        fetch('<?= base_url('tpp_dinamis/save_manual_ekin') ?>', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerText = 'Simpan';
            if (res.status === 'success') {
                Swal.fire({icon: 'success', title: 'Tersimpan', text: res.message, timer: 1500, showConfirmButton: false}).then(() => location.reload());
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: res.message});
            }
        });
    });
</script>

<?= $this->endSection() ?>
