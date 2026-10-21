<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bolder text-dark mb-1"><i class="fas fa-calendar-check text-primary me-2"></i> Matriks Absen Bulanan</h3>
            <p class="text-muted">Isi presensi (Hadir) untuk seluruh hari kerja dalam satu bulan secara massal.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="<?= base_url('bulkabsen/save') ?>" method="POST">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Bulan</label>
                        <select name="bulan" class="form-select" required>
                            <option value="">-- Pilih Bulan --</option>
                            <?php
                            $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            for ($i = 1; $i <= 12; $i++):
                                $selected = (date('n') == $i) ? 'selected' : '';
                            ?>
                                <option value="<?= $i ?>" <?= $selected ?>><?= $bulan_nama[$i] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Tahun</label>
                        <select name="tahun" class="form-select" required>
                            <?php
                            $tahun_sekarang = date('Y');
                            for ($t = $tahun_sekarang - 1; $t <= $tahun_sekarang + 1; $t++):
                                $selected = ($tahun_sekarang == $t) ? 'selected' : '';
                            ?>
                                <option value="<?= $t ?>" <?= $selected ?>><?= $t ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Pilih Pegawai (Bisa Lebih dari Satu)</label>
                    <select name="id_pegawai[]" class="form-select select2-multiple" multiple="multiple" required>
                        <?php foreach ($pegawai as $p): ?>
                            <option value="<?= $p['id_pegawai'] ?>"><?= esc($p['nama']) ?> - <?= esc($p['nip']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Sistem akan mengisikan absensi Hadir dengan jam 07:30 (Masuk) dan 16:00 (Pulang) untuk setiap <b>hari kerja (Senin-Jumat)</b> di bulan tersebut.</small>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2" onclick="return confirm('Apakah Anda yakin ingin mengisi presensi Hadir secara massal untuk pegawai yang dipilih pada bulan ini?')">
                        <i class="fas fa-save me-2"></i> Proses Absen
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-multiple').select2({
            theme: 'bootstrap-5',
            placeholder: "-- Pilih Pegawai --",
            allowClear: true
        });
    });
</script>

<?= $this->endSection() ?>