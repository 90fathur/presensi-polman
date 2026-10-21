<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Approval Koreksi Kehadiran</h5>
        <small class="text-muted">Kelola pengajuan koreksi kehadiran dari seluruh Admin OPD.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="get" class="d-flex align-items-center gap-2">
            <select name="id_opd" class="form-select select2" style="max-width: 400px;" onchange="this.form.submit()">
                <option value="">-- Semua OPD --</option>
                <?php foreach ($opd_list as $opd): ?>
                    <option value="<?= $opd['id_opd'] ?>" <?= ($selected_opd == $opd['id_opd']) ? 'selected' : '' ?>>
                        <?= $opd['nama_opd'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($selected_opd)): ?>
                <a href="<?= base_url('koreksikehadiran/approval') ?>" class="btn btn-outline-secondary">Reset Filter</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <form action="<?= base_url('koreksikehadiran/approve_bulk') ?>" method="post" id="form-bulk">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4" width="40">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th width="40">NO</th>
                            <th>NAMA PEGAWAI & OPD</th>
                            <th>DETAIL PENGAJUAN (TANGGAL, KETERANGAN, AKSI)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_grouped)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan koreksi baru.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($pending_grouped as $row):
                                $group_ids = implode(',', $row['koreksi_ids']);
                            ?>
                                <tr>
                                    <td class="ps-4 align-top pt-3">
                                        <input class="form-check-input check-group" type="checkbox" data-target=".check-item-<?= $row['id_pegawai'] ?>">
                                    </td>
                                    <td class="text-muted small align-top pt-3"><?= $no++ ?></td>
                                    <td class="align-top pt-3">
                                        <div class="fw-bold text-dark small"><?= $row['nama_lengkap'] ?></div>
                                        <div class="text-muted mb-1" style="font-size: 0.65rem;">NIP: <?= $row['nip'] ?></div>
                                        <span class="badge bg-secondary-subtle text-secondary border"><?= $row['nama_opd'] ?></span>
                                    </td>
                                    <td class="p-2">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($row['koreksi_ids'] as $idx => $id_koreksi): ?>
                                                <div class="border rounded bg-white shadow-sm p-1 d-flex align-items-center" style="min-width: 220px;">
                                                    <div class="form-check mb-0 ms-1 me-2">
                                                        <input class="form-check-input check-item check-item-<?= $row['id_pegawai'] ?>" type="checkbox" name="koreksi_ids[]" value="<?= $id_koreksi ?>" style="margin-top: 0.25rem;">
                                                    </div>
                                                    <div class="d-flex flex-column flex-grow-1 me-2">
                                                        <span class="fw-bold text-primary" style="font-size: 0.75rem;"><?= $row['tanggal_list'][$idx] ?></span>
                                                        <span class="text-muted" style="font-size: 0.65rem; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $row['keterangan_list'][$idx] ?>">
                                                            <?= $row['keterangan_list'][$idx] ?>
                                                        </span>
                                                    </div>
                                                    <div class="border-start ps-2 d-flex gap-1">
                                                        <a href="<?= base_url('koreksikehadiran/approve_action/' . $id_koreksi) ?>"
                                                            class="btn btn-sm btn-outline-success p-0" style="width: 24px; height: 24px; line-height: 22px;" title="Approve"
                                                            onclick="return confirm('Approve koreksi tanggal ini?')">
                                                            <i class="fas fa-check" style="font-size: 0.7rem;"></i>
                                                        </a>
                                                        <a href="<?= base_url('koreksikehadiran/reject_action/' . $id_koreksi) ?>"
                                                            class="btn btn-sm btn-outline-danger p-0" style="width: 24px; height: 24px; line-height: 22px;" title="Reject"
                                                            onclick="return confirm('Tolak koreksi tanggal ini?')">
                                                            <i class="fas fa-times" style="font-size: 0.7rem;"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($pending_grouped)): ?>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin melakukan Approve Massal pada data terpilih?')">
                            <i class="fas fa-check-double me-1"></i> Approve Terpilih
                        </button>
                        <button type="submit" formaction="<?= base_url('koreksikehadiran/reject_bulk') ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin melakukan Reject Massal pada data terpilih?')">
                            <i class="fas fa-times me-1"></i> Reject Terpilih
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }

        const checkAll = document.getElementById('checkAll');
        const checkItems = document.querySelectorAll('.check-item');
        const checkGroups = document.querySelectorAll('.check-group');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkItems.forEach(item => item.checked = this.checked);
                checkGroups.forEach(item => item.checked = this.checked);
            });
        }

        checkGroups.forEach(group => {
            group.addEventListener('change', function() {
                const targetClass = this.getAttribute('data-target');
                document.querySelectorAll(targetClass).forEach(item => {
                    item.checked = this.checked;
                });
            });
        });

        document.getElementById('form-bulk')?.addEventListener('submit', function(e) {
            let checked = document.querySelectorAll('.check-item:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal satu data pengajuan yang ingin diproses!');
            }
        });
    });
</script>
<?= $this->endSection() ?>