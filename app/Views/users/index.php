<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manajemen Pengguna</h5>
    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="fas fa-plus-circle me-1"></i> Tambah User
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4">NO</th>
                        <th>NAMA PENGGUNA</th>
                        <th>USERNAME</th>
                        <th>UNIT KERJA / OPD</th>
                        <th class="text-center">LEVEL</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4 small text-muted"><?= $no++ ?>.</td>
                            <td>
                                <div class="fw-bold small"><?= $u['nama_user'] ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $u['username'] ?></span></td>
                            <td>
                                <div class="small text-muted"><?= $u['nama_opd'] ?? '<span class="text-danger">ADMIN PUSAT</span>' ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $u['level'] == 'kabupaten' ? 'bg-primary' : ($u['level'] == 'bpkad' ? 'bg-warning text-dark' : 'bg-info') ?> small">
                                    <?= strtoupper($u['level']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <button onclick="openEditUser('<?= $u['id_user'] ?>')" class="btn btn-sm btn-white border text-warning"><i class="fas fa-edit"></i></button>
                                    <a href="<?= base_url('users/hapus/' . $u['id_user']) ?>" class="btn btn-sm btn-white border text-danger" onclick="return confirm('Hapus akun ini?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('users/simpan') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_user" class="form-control" placeholder="Nama admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Untuk login" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Unit Kerja (OPD)</label>
                        <select name="id_opd" class="form-select">
                            <option value="">-- PILIH OPD (Kosongkan jika Admin Pusat) --</option>
                            <?php foreach ($opd as $o): ?>
                                <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Level Akses</label>
                            <select name="level" class="form-select" required>
                                <option value="opd">Admin OPD</option>
                                <option value="kabupaten">Admin Kabupaten</option>
                                <option value="bpkad">Admin BPKAD (Pencairan TPP)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('users/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_user" id="edit_id_user">

                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_user" id="edit_nama_user" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Unit Kerja (OPD)</label>
                        <select name="id_opd" id="edit_id_opd" class="form-select bg-light border-0">
                            <option value="">-- ADMIN PUSAT --</option>
                            <?php foreach ($opd as $o): ?>
                                <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Level Akses</label>
                            <select name="level" id="edit_level" class="form-select bg-light border-0" required>
                                <option value="opd">Admin OPD</option>
                                <option value="kabupaten">Admin Kabupaten</option>
                                <option value="bpkad">Admin BPKAD (Pencairan TPP)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-danger">Ganti Password?</label>
                            <input type="password" name="password" class="form-control bg-light border-0" placeholder="Kosongkan jika tidak ganti">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function openEditUser(id) {
        try {
            const response = await fetch('<?= base_url('users/edit_data') ?>/' + id);
            const data = await response.json();

            // Pastikan nama properti (data.xxx) sesuai dengan kolom di database Bapak
            document.getElementById('edit_id_user').value = data.id_user;
            document.getElementById('edit_nama_user').value = data.nama_user; // Sesuaikan jika di DB 'nama_user'
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_id_opd').value = data.id_opd || '';
            document.getElementById('edit_level').value = data.level;

            const myModal = new bootstrap.Modal(document.getElementById('modalEditUser'));
            myModal.show();
        } catch (err) {
            console.error(err);
            alert("Gagal memuat data. Cek Console F12!");
        }
    }
</script>
<?= $this->endSection() ?>