<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Jadwal Apel Pegawai</h5>
        <?php if ($level == 'kabupaten' || $level == 'Superadmin'): ?>
            <button class="btn btn-primary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-plus me-2"></i>Tambah Jadwal
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Kegiatan Apel</th>
                        <th>Waktu & Tanggal</th>
                        <th>Titik Lokasi (Radius)</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list_apel as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary"><?= $a['nama_apel'] ?></div>
                                <small class="text-muted">ID: <?= $a['id_apel'] ?></small>
                            </td>
                            <td>
                                <div class="small fw-bold"><i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y', strtotime($a['tanggal'])) ?></div>
                                <div class="small text-muted"><i class="far fa-clock me-1"></i> <?= $a['jam_mulai'] ?> - <?= $a['jam_akhir'] ?></div>
                            </td>
                            <td>
                                <div class="small text-truncate" style="max-width: 150px;">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> <?= $a['latitude'] ?>, <?= $a['longitude'] ?>
                                </div>
                                <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Radius: <?= $a['radius'] ?> meter</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="<?= base_url('apel/kelola/' . $a['id_apel']) ?>" class="btn btn-success btn-sm rounded-start-pill px-3" title="Kelola Utusan">
                                        <i class="fas fa-users"></i>
                                    </a>

                                    <a href="<?= base_url('apel/rekap/' . $a['id_apel']) ?>" class="btn btn-secondary btn-sm px-3" title="Rekap Kehadiran Apel">
                                        <i class="fas fa-file-alt"></i>
                                    </a>

                                    <button onclick="copyLinkWeb('<?= base_url('apelweb/checkin/' . $a['id_apel']) ?>')" class="btn btn-dark btn-sm px-3" title="Salin Link Web Check-in">
                                        <i class="fas fa-link"></i>
                                    </button>

                                    <?php if ($level == 'kabupaten' || $level == 'Superadmin'): ?>
                                        <button onclick="sendReminder(<?= $a['id_apel'] ?>, '<?= addslashes($a['nama_apel']) ?>')" class="btn btn-info btn-sm text-white px-3" title="Kirim Pengingat Wajib Apel">
                                            <i class="fas fa-bell"></i>
                                        </button>

                                        <button class="btn btn-warning btn-sm px-3 btn-edit-trigger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEdit"
                                            data-id="<?= $a['id_apel'] ?>"
                                            data-nama="<?= $a['nama_apel'] ?>"
                                            data-tanggal="<?= $a['tanggal'] ?>"
                                            data-mulai="<?= $a['jam_mulai'] ?>"
                                            data-akhir="<?= $a['jam_akhir'] ?>"
                                            data-lat="<?= $a['latitude'] ?>"
                                            data-lng="<?= $a['longitude'] ?>"
                                            data-radius="<?= $a['radius'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button onclick="confirmDelete('<?= base_url('apel/delete/' . $a['id_apel']) ?>')" class="btn btn-danger btn-sm rounded-end-pill px-3">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <?= $pager ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= base_url('apel/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h6 class="fw-bold mb-0">Buat Jadwal Apel Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Nama Kegiatan Apel</label>
                        <input type="text" name="nama_apel" class="form-control bg-light border-0" placeholder="Nama Kegiatan" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control bg-light border-0" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><input type="time" name="jam_mulai" class="form-control bg-light border-0" required></div>
                        <div class="col-6"><input type="time" name="jam_akhir" class="form-control bg-light border-0" required></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6"><input type="text" name="latitude" class="form-control bg-light border-0" placeholder="Lat" required></div>
                        <div class="col-6"><input type="text" name="longitude" class="form-control bg-light border-0" placeholder="Lng" required></div>
                        <div class="col-12"><input type="number" name="radius" class="form-control bg-light border-0" value="100" required></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Terbitkan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formEdit" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h6 class="fw-bold mb-0 text-warning">Edit Jadwal Apel</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Nama Kegiatan Apel</label>
                        <input type="text" name="nama_apel" id="edit_nama" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Tanggal</label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control bg-light border-0" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><input type="time" name="jam_mulai" id="edit_mulai" class="form-control bg-light border-0" required></div>
                        <div class="col-6"><input type="time" name="jam_akhir" id="edit_akhir" class="form-control bg-light border-0" required></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6"><input type="text" name="latitude" id="edit_lat" class="form-control bg-light border-0" required></div>
                        <div class="col-6"><input type="text" name="longitude" id="edit_lng" class="form-control bg-light border-0" required></div>
                        <div class="col-12"><input type="number" name="radius" id="edit_radius" class="form-control bg-light border-0" required></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 text-dark">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Pastikan script berjalan setelah halaman siap
    document.addEventListener('DOMContentLoaded', function() {

        // Logika Mengisi Modal Edit saat terbuka
        const modalEdit = document.getElementById('modalEdit');
        if (modalEdit) {
            modalEdit.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const tgl = button.getAttribute('data-tanggal');
                const mulai = button.getAttribute('data-mulai');
                const akhir = button.getAttribute('data-akhir');
                const lat = button.getAttribute('data-lat');
                const lng = button.getAttribute('data-lng');
                const rad = button.getAttribute('data-radius');

                document.getElementById('edit_nama').value = nama;
                document.getElementById('edit_tanggal').value = tgl;
                document.getElementById('edit_mulai').value = mulai;
                document.getElementById('edit_akhir').value = akhir;
                document.getElementById('edit_lat').value = lat;
                document.getElementById('edit_lng').value = lng;
                document.getElementById('edit_radius').value = rad;

                document.getElementById('formEdit').action = '<?= base_url('apel/update') ?>/' + id;
            });
        }
    });

    // Fungsi Hapus (SweetAlert)
    function confirmDelete(url) {
        if (confirm('Apakah Anda yakin ingin menghapus jadwal ini? Daftar utusan juga akan terhapus.')) {
            window.location.href = url;
        }
    }

    // Fungsi Copy Link Web Check-in
    function copyLinkWeb(url) {
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Link Web Check-in berhasil disalin ke clipboard!',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(err => {
            alert('Gagal menyalin link: ' + err);
        });
    }

    // Fungsi AJAX Kirim Pengingat Notifikasi
    function sendReminder(idApel, namaApel) {
        Swal.fire({
            title: 'Kirim Pengingat?',
            html: `Sistem akan menembakkan Push Notifikasi & WhatsApp ke seluruh pegawai yang menjadi utusan pada kegiatan <b>${namaApel}</b>.<br><br>Lanjutkan?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses Pengiriman...',
                    text: 'Jangan tutup halaman ini.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: '<?= base_url('apel/remind_peserta') ?>',
                    type: 'POST',
                    data: {
                        id_apel: idApel,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Selesai!', res.message, 'success');
                        } else if (res.status === 'warning') {
                            Swal.fire('Perhatian', res.message, 'warning');
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan pada server, periksa koneksi Anda.', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>