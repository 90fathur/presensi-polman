<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
<div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
    <div class="card-body">
        <form method="GET" action="<?= current_url() ?>">
            <div class="row align-items-end">
                <div class="col-md-9">
                    <label class="fw-bold text-muted small mb-2"><i class="fas fa-filter me-1"></i>Pilih OPD (Wajib dipilih untuk menampilkan pegawai)</label>
                    <select name="id_opd" class="form-select shadow-none border-secondary" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Instansi / OPD --</option>
                        <?php if(isset($list_opd)): foreach ($list_opd as $o): ?>
                            <option value="<?= $o['id_opd'] ?>" <?= (isset($filter_opd) && $filter_opd == $o['id_opd']) ? 'selected' : '' ?>>
                                <?= $o['nama_opd'] ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold"><i class="fas fa-search me-1"></i> Tampilkan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<div class="row">
    <div class="col-12">
        <form id="formUtusan" action="<?= base_url('apel/simpan_utusan') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_apel" value="<?= $apel['id_apel'] ?>">
            
            <input type="hidden" name="id_opd_target" value="<?= $filter_opd ?? '' ?>">

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-primary">Pilih Utusan: <?= $apel['nama_apel'] ?></h5>
                        <p class="text-muted small mb-0">Hanya pegawai terpilih yang akan melihat tombol absen apel di PWA.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" <?= (in_array($level, ['Superadmin', 'kabupaten']) && empty($filter_opd)) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-2"></i> Simpan Utusan
                    </button>
                </div>
                
                <div class="card-body">
                    <?php if (in_array($level, ['Superadmin', 'kabupaten']) && empty($filter_opd)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i>
                            <h6 class="text-muted fw-bold">Silakan Pilih Instansi / OPD Terlebih Dahulu</h6>
                            <p class="small text-muted mb-0">Daftar pegawai disembunyikan untuk mencegah beban server yang terlalu berat.</p>
                        </div>
                    <?php else: ?>
                    
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tableUtusan">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="50" class="text-center">
                                            <input type="checkbox" id="checkAll" class="form-check-input border-secondary" style="cursor:pointer">
                                        </th>
                                        <th>Pegawai</th>
                                        <th>NIP</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($pegawai)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada data pegawai di OPD ini.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pegawai as $p): ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="id_pegawai[]" value="<?= $p['id_pegawai'] ?>" 
                                                class="form-check-input checkItem border-secondary" 
                                                <?= in_array($p['id_pegawai'], $utusan_ids) ? 'checked' : '' ?> style="cursor:pointer">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= $p['nama_lengkap'] ?></div>
                                            </td>
                                            <td><code class="text-pink" style="font-size: 0.85rem;"><?= $p['nip'] ?></code></td>
                                            <td class="text-center">
                                                <?php if (in_array($p['id_pegawai'], $utusan_ids)): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.7rem;">Sudah Terpilih</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted" style="font-size: 0.7rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const checkItems = document.querySelectorAll('.checkItem');

        // Logika saat CheckAll diklik
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkItems.forEach(item => {
                    item.checked = this.checked;
                });
            });
        }

        // Logika opsional: Jika satu dilepas, CheckAll otomatis batal centang
        if (checkItems) {
            checkItems.forEach(item => {
                item.addEventListener('change', function() {
                    if (!this.checked) {
                        checkAll.checked = false;
                    } else {
                        const allChecked = Array.from(checkItems).every(i => i.checked);
                        checkAll.checked = allChecked;
                    }
                });
            });
        }
    });
</script>

<?= $this->endSection() ?>