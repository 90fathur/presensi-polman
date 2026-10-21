<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x me-3"></i>
            <div>
                <strong>Berhasil!</strong><br>
                <?= session()->getFlashdata('success') ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <strong>Gagal!</strong><br>
                <?= session()->getFlashdata('error') ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
        <form action="" method="GET" class="row g-2 align-items-end">
            <div class="col-md-10">
                <label class="small fw-bold">Pilih OPD Pegawai</label>
                <select name="id_opd" class="form-select form-select-sm select2">
                    <option value="">-- Pilih Instansi --</option>
                    <?php foreach($list_opd as $o): ?>
                        <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>><?= $o['nama_opd'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Cari Pegawai</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-tie me-2"></i>Input Presensi Khusus Pejabat (Eselon II)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($pegawai) && in_array($level, ['Superadmin', 'kabupaten'])): ?>
                    <div class="alert alert-warning text-center">Silakan pilih OPD terlebih dahulu untuk menampilkan daftar pejabat Eselon II.</div>
                <?php else: ?>
                    <?php if(empty($pegawai)): ?>
                        <div class="alert alert-info text-center border-0">Tidak ada pejabat Eselon II di OPD ini.</div>
                    <?php else: ?>
                        <form action="<?= base_url('rekap/simpan_manual') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label class="small fw-bold">Pilih Pejabat (Eselon II.a / II.b)</label>
                                <select name="nip" class="form-select select2" required>
                                    <option value="">-- Cari Nama Kepala Dinas/Badan --</option>
                                    <?php foreach($pegawai as $p): ?>
                                        <option value="<?= $p['nip'] ?>"><?= $p['nama_lengkap'] ?> (NIP. <?= $p['nip'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="small fw-bold">Dari Tanggal</label>
                                    <input type="date" name="tgl_mulai" class="form-control" value="<?= date('Y-m-01') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Sampai Tanggal</label>
                                    <input type="date" name="tgl_selesai" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Status Kehadiran</label>
                                    <select name="kategori" class="form-select">
                                        <option value="Hadir">Hadir (Kantor)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-info small py-2 mb-3 border-0">
                                <i class="fas fa-info-circle me-1"></i> Sistem secara otomatis akan melewati (tidak mengisi) hari Sabtu dan Minggu.
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold">Jam Masuk (Otomatis)</label>
                                    <input type="time" name="jam_masuk" class="form-control bg-light" value="07:30" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold">Jam Pulang (Otomatis)</label>
                                    <input type="time" name="jam_pulang" class="form-control bg-light" value="16:00" readonly>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold">Alasan / Catatan</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Pengecualian Jabatan Eselon II / Kepala Dinas" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="return confirm('Apakah rentang tanggal sudah benar? Data absensi di rentang tersebut akan ditimpa.')">SIMPAN PRESENSI RENTANG WAKTU</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5', 
            width: '100%'         
        });
    });
</script>

<?= $this->endSection() ?>