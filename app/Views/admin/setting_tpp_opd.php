<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .form-switch .form-check-input {
        width: 2.8em;
        height: 1.4em;
        cursor: pointer;
    }
</style>

<div class="container-fluid px-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-sliders-h text-primary me-2"></i> Pengaturan Prototype TPP Dinamis OPD
            </h4>
            <p class="text-muted small mb-0">
                Pilih dan aktifkan Unit Kerja / OPD mana saja yang menerapkan formula TPP Dinamis (Beban Kerja 60% & Disiplin 40%).
            </p>
        </div>
        <div>
            <a href="<?= base_url('tpp_dinamis') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Rekap TPP
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-building me-2 text-primary"></i> Daftar Seluruh OPD Kabupaten
                </h6>
                <span class="small text-muted">Total: <?= count($list_opd) ?> Instansi</span>
            </div>
            <div class="col-md-4 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-secondary"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchOpd" class="form-control border-secondary shadow-none" placeholder="Cari nama OPD...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelOpd">
                <thead class="bg-light text-dark fw-bold" style="font-size: 0.75rem;">
                    <tr>
                        <th style="width: 50px;" class="text-center">NO</th>
                        <th>NAMA UNIT KERJA (OPD)</th>
                        <th style="width: 150px;" class="text-center">KODE OPD</th>
                        <th style="width: 200px;" class="text-center">STATUS PROTOTYPE</th>
                        <th style="width: 130px;" class="text-center">AKSI AKTIVASI</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.82rem;">
                    <?php $no = 1; foreach ($list_opd as $o): ?>
                        <tr class="opd-row" data-name="<?= strtolower(esc($o['nama_opd'])) ?>">
                            <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($o['nama_opd']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border"><?= esc($o['kode_opd'] ?: '-') ?></span>
                            </td>
                            <td class="text-center" id="badge_status_<?= $o['id_opd'] ?>">
                                <?php if (!empty($o['tpp_dinamis_aktif'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> Prototype Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle px-3 py-2 rounded-pill">
                                        <i class="fas fa-times-circle me-1"></i> Belum Aktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input switch-opd" 
                                           type="checkbox" 
                                           role="switch" 
                                           id="switch_<?= $o['id_opd'] ?>"
                                           data-id="<?= $o['id_opd'] ?>"
                                           data-name="<?= esc($o['nama_opd']) ?>"
                                           <?= !empty($o['tpp_dinamis_aktif']) ? 'checked' : '' ?>>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Search Filter
    document.getElementById('searchOpd').addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('.opd-row');
        rows.forEach(function(row) {
            var name = row.getAttribute('data-name');
            if (name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Toggle OPD Prototype Switch
    document.querySelectorAll('.switch-opd').forEach(function(switchEl) {
        switchEl.addEventListener('change', function() {
            var idOpd = this.getAttribute('data-id');
            var namaOpd = this.getAttribute('data-name');
            var isChecked = this.checked ? 1 : 0;
            var currentSwitch = this;

            var formData = new FormData();
            formData.append('id_opd', idOpd);
            formData.append('status', isChecked);

            fetch('<?= base_url('tpp_dinamis/toggle_opd') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    var badgeContainer = document.getElementById('badge_status_' + idOpd);
                    if (isChecked) {
                        badgeContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Prototype Aktif</span>';
                    } else {
                        badgeContainer.innerHTML = '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle px-3 py-2 rounded-pill"><i class="fas fa-times-circle me-1"></i> Belum Aktif</span>';
                    }

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    currentSwitch.checked = !isChecked;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                currentSwitch.checked = !isChecked;
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: 'Gagal menghubungi server.'
                });
            });
        });
    });
</script>

<?= $this->endSection() ?>
