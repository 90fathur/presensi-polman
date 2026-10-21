<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Manajemen Pegawai</h5>
        <p class="text-muted small mb-0">Kelola data personil, lokasi, dan hak akses WFA</p>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <?php if (session()->get('level') == 'kabupaten'): ?>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalKewenanganWFA">
                <i class="fas fa-user-shield me-1"></i> Izin WFA OPD
            </button>
        <?php endif; ?>

        <?php if (session()->get('level') == 'kabupaten' || (session()->get('level') == 'opd' && $opd_wfa_allowed == 1)): ?>
            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalWfaMassal">
                <i class="fas fa-laptop-house me-1"></i> Setup WFA Massal
            </button>
        <?php endif; ?>

        <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="fas fa-file-excel me-1"></i> Import Excel
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPegawai">
            <i class="fas fa-user-plus me-1"></i> Tambah Pegawai
        </button>
        <button id="btnGeneratePass" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">
            <i class="fas fa-shield-alt me-1"></i> Amankan Password
        </button>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->get('level') == 'opd' && $opd_wfa_allowed == 0): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <i class="fas fa-lock me-2"></i> <strong>Akses WFA Terkunci:</strong> Admin Kabupaten saat ini tidak memberikan hak akses kepada OPD Anda untuk mengatur Work From Anywhere. Silakan hubungi Admin Kabupaten.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="small fw-bold text-muted mb-1">Filter Unit Kerja (OPD)</label>
                <select id="filter_opd" class="form-select form-select-sm border-light bg-light shadow-none">
                    <?php if (session()->get('level') == 'kabupaten'): ?>
                        <option value="">-- Tampilkan Semua OPD --</option>
                    <?php endif; ?>
                    <?php foreach ($opd_list as $o): ?>
                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="tabelPegawai" class="table table-hover align-middle mb-0" style="width:100%">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th width="5%">NO</th>
                        <th width="20%">NAMA PEGAWAI</th>
                        <th width="20%">JABATAN / PANGKAT</th>
                        <th width="10%" class="text-center">LEVEL JABATAN</th>
                        <th width="10%" class="text-center">STATUS ASN</th>
                        <th width="10%" class="text-center">AKSES WFA</th>
                        <th width="10%" class="text-center">STATUS WAJAH</th>
                        <th width="10%" class="text-center">NOMOR HP/WA</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKewenanganWFA" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 pb-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-user-shield me-2"></i>Hak Akses WFA Admin OPD</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger small border-0 mb-3">
                    <i class="fas fa-lock me-1"></i> Fitur ini untuk <strong>membuka atau mengunci</strong> tombol WFA bagi Admin OPD. Jika dikunci, Admin OPD tidak akan bisa mengubah status WFA pegawainya.
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Instansi (OPD)</label>
                    <select id="wewenang_opd" class="form-select border-danger bg-light shadow-none">
                        <option value="">-- Terapkan Ke SELURUH OPD --</option>
                        <?php foreach ($opd_list as $o): ?>
                            <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Hak Akses</label>
                    <select id="wewenang_status" class="form-select border-dark shadow-none">
                        <option value="1">IZINKAN (Buka Kunci Tombol WFA)</option>
                        <option value="0">CABUT IZIN (Kunci Tombol WFA)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="submitKewenanganWfa()" class="btn btn-danger rounded-pill px-4 shadow">Terapkan Kebijakan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalWfaMassal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 pb-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-laptop-house me-2"></i>Setup WFA Massal Pegawai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small border-0 mb-3">
                    <i class="fas fa-info-circle me-1"></i> Fitur ini mengubah status WFA pada <strong>banyak pegawai sekaligus berdasarkan rentang tanggal.</strong>
                </div>
                <?php if (session()->get('level') == 'kabupaten'): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Target OPD</label>
                        <select id="wfa_massal_opd" class="form-select border-primary bg-light shadow-none">
                            <option value="">-- Terapkan Ke SELURUH KABUPATEN --</option>
                            <?php foreach ($opd_list as $o): ?>
                                <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" id="wfa_massal_opd" value="<?= session()->get('id_opd') ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Aksi yang dilakukan</label>
                    <select id="wfa_massal_aksi" class="form-select border-dark shadow-none" onchange="toggleTglMassal()">
                        <option value="1">AKTIFKAN WFA Pegawai</option>
                        <option value="0">NONAKTIFKAN WFA (Kembali ke Kantor)</option>
                    </select>
                </div>
                <div class="row" id="wfa_massal_tgl_area">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Tanggal Mulai</label>
                        <input type="date" id="wfa_massal_mulai" class="form-control bg-light" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Tanggal Selesai</label>
                        <input type="date" id="wfa_massal_selesai" class="form-control bg-light" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="submitWfaMassal()" class="btn btn-primary rounded-pill px-4 shadow">Proses Sekarang</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTransfer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 pb-3">
                <h6 class="modal-title fw-bold"><i class="fas fa-exchange-alt me-2"></i>Transfer / Mutasi Pegawai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small border-0 mb-3">
                    <i class="fas fa-exclamation-circle me-1"></i> Perhatian: Pegawai ini akan dipindahkan ke OPD baru dan lokasi absensinya akan direset.
                </div>
                <div class="mb-3 text-center">
                    <div class="small text-muted">Pegawai yang akan dimutasi:</div>
                    <div id="transfer_nama_pegawai" class="fw-bold fs-5 text-dark"></div>
                </div>
                <input type="hidden" id="transfer_id_pegawai">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih OPD Tujuan</label>
                    <select id="transfer_id_opd" class="form-select border-dark bg-light shadow-none">
                        <option value="">-- Pilih OPD Baru --</option>
                        <?php foreach ($opd_list as $o): ?>
                            <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="submitTransfer()" class="btn btn-dark rounded-pill px-4 shadow">Proses Mutasi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImport" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Import Pegawai (Server-Side)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="btnCloseImport"></button>
            </div>
            <div class="modal-body px-4">
                <div id="formArea">
                    <p class="small text-muted">Pilih file excel untuk memulai proses import massal.</p>
                    <input type="file" id="fileExcel" class="form-control border-light bg-light" accept=".xlsx, .xls">
                    <button type="button" onclick="startUpload()" class="btn btn-success w-100 rounded-pill mt-3 shadow">Unggah & Proses</button>
                </div>
                <div id="progressArea" style="display:none;">
                    <div class="text-center mb-3">
                        <div class="spinner-border text-success mb-2"></div>
                        <div class="fw-bold small" id="importStatus">Memulai...</div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%"></div>
                    </div>
                    <p class="text-center small text-muted mt-2" id="importDetail">0 dari 0 data</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade <?= (session()->getFlashdata('error')) ? 'show' : '' ?>" id="modalPegawai" tabindex="-1" aria-hidden="true" style="<?= (session()->getFlashdata('error')) ? 'display: block; background: rgba(0,0,0,0.5);' : '' ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary bg-gradient text-white rounded-top-4 pb-3 pt-4 px-4 shadow-sm">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 opacity-75"></i> Registrasi Pegawai Baru</h5>
                <button type="button" class="btn-close btn-close-white" <?= (session()->getFlashdata('error')) ? 'onclick="location.reload()"' : 'data-bs-dismiss="modal"' ?> aria-label="Close"></button>
            </div>

            <form action="<?= base_url('pegawai/simpan') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body px-4 py-4 bg-white">
                    <?php if (session()->getFlashdata('error')) : ?>
                        <div class="alert alert-danger mb-4 py-2 small fw-bold rounded-3 border-0 shadow-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-4 mb-4 rounded-4" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                        <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom border-secondary-subtle" style="font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">
                            <i class="fas fa-id-card text-primary me-2"></i>Informasi Utama
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-building text-primary me-1"></i> Unit Kerja (OPD)</label>
                                <select name="id_opd" class="form-select border-0 shadow-sm py-2 rounded-3" required>
                                    <?php foreach ($opd_list as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>" <?= old('id_opd') == $o['id_opd'] ? 'selected' : '' ?>><?= $o['nama_opd'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-fingerprint text-primary me-1"></i> NIP</label>
                                <input type="text" name="nip" class="form-control border-0 shadow-sm py-2 rounded-3 <?= session()->getFlashdata('error') ? 'is-invalid border-danger' : '' ?>" placeholder="Masukkan NIP Pegawai" value="<?= old('nip') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-user text-primary me-1"></i> Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Nama Tanpa Gelar" value="<?= old('nama_lengkap') ?>" required>
                            </div>
                            <div class="col-md-12 mt-4">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-key text-primary me-1"></i> Password Akun</label>
                                <input type="password" name="password" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Buat password minimal 6 karakter" required>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-4" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                        <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom border-secondary-subtle" style="font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">
                            <i class="fas fa-briefcase text-primary me-2"></i>Detail Kepegawaian & Jabatan
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Status ASN</label>
                                <select name="status_asn" class="form-select border-0 shadow-sm py-2 rounded-3">
                                    <option value="PNS" <?= old('status_asn') == 'PNS' ? 'selected' : '' ?>>PNS</option>
                                    <option value="PPPK" <?= old('status_asn') == 'PPPK' ? 'selected' : '' ?>>PPPK</option>
                                    <option value="PPPKPW" <?= old('status_asn') == 'PPPKPW' ? 'selected' : '' ?>>PPPKPW</option>
                                    <option value="TENAGA KONTRAK" <?= old('status_asn') == 'TENAGA KONTRAK' ? 'selected' : '' ?>>TENAGA KONTRAK</option>
                                    <option value="KONTRAK BLUD" <?= old('status_asn') == 'KONTRAK BLUD' ? 'selected' : '' ?>>KONTRAK BLUD</option>
                                    <option value="NON ASN LAINNYA" <?= old('status_asn') == 'NON ASN LAINNYA' ? 'selected' : '' ?>>NON ASN LAINNYA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Pangkat / Gol.</label>
                                <input type="text" name="pangkat" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Cth: Pembina / IV.a" value="<?= old('pangkat') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Eselon</label>
                                <select name="eselon" class="form-select border-0 shadow-sm py-2 rounded-3">
                                    <option value="" <?= old('eselon') == '' ? 'selected' : '' ?>>Non-Eselon</option>
                                    <option value="II.a" <?= old('eselon') == 'II.a' ? 'selected' : '' ?>>II.a</option>
                                    <option value="II.b" <?= old('eselon') == 'II.b' ? 'selected' : '' ?>>II.b</option>
                                    <option value="III.a" <?= old('eselon') == 'III.a' ? 'selected' : '' ?>>III.a</option>
                                    <option value="III.b" <?= old('eselon') == 'III.b' ? 'selected' : '' ?>>III.b</option>
                                    <option value="IV.a" <?= old('eselon') == 'IV.a' ? 'selected' : '' ?>>IV.a</option>
                                    <option value="IV.b" <?= old('eselon') == 'IV.b' ? 'selected' : '' ?>>IV.b</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label small fw-bold text-secondary">Jabatan Spesifik</label>
                                <input type="text" name="jabatan" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Contoh: Analis Sistem IT" value="<?= old('jabatan') ?>" required>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label small fw-bold text-secondary">Tingkat / Level Jabatan</label>
                                <select name="level_jabatan" class="form-select border-0 shadow-sm py-2 rounded-3" required>
                                    <option value="Jabatan Pimpinan Tinggi Pratama" <?= old('level_jabatan') == 'Jabatan Pimpinan Tinggi Pratama' ? 'selected' : '' ?>>JPT Pratama</option>
                                    <option value="Pejabat Administrator" <?= old('level_jabatan') == 'Pejabat Administrator' ? 'selected' : '' ?>>Administrator</option>
                                    <option value="Pejabat Pengawas" <?= old('level_jabatan') == 'Pejabat Pengawas' ? 'selected' : '' ?>>Pengawas</option>
                                    <option value="Pejabat Fungsional" <?= old('level_jabatan') == 'Pejabat Fungsional' ? 'selected' : '' ?>>Fungsional</option>
                                    <option value="Staf" <?= (old('level_jabatan') == 'Staf' || !old('level_jabatan')) ? 'selected' : '' ?>>Staf / Pelaksana</option>
                                </select>
                            </div>
                            <?php if (session()->get('level') == 'kabupaten'): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">Jalur Presensi (Khusus)</label>
                                    <select name="is_bercadar" class="form-select border-0 shadow-sm py-2 rounded-3">
                                        <option value="0" <?= old('is_bercadar') == '0' ? 'selected' : '' ?>>Kamera Wajah (Reguler)</option>
                                        <option value="1" <?= old('is_bercadar') == '1' ? 'selected' : '' ?>>Fingerprint (Pegawai Bercadar)</option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="is_bercadar" value="0">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 bg-white rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" <?= (session()->getFlashdata('error')) ? 'onclick="location.reload()"' : 'data-bs-dismiss="modal"' ?>>Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-bold"><i class="fas fa-save me-2"></i> Simpan Pegawai</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-dark bg-gradient text-white rounded-top-4 pb-3 pt-4 px-4 shadow-sm">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 opacity-75"></i> Pembaruan Data Pegawai</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= base_url('pegawai/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pegawai" id="edit_id_pegawai">

                <div class="modal-body px-4 py-4 bg-white">
                    <div class="p-4 mb-4 rounded-4" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom border-secondary-subtle" style="font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">
                            <i class="fas fa-id-card text-dark me-2"></i>Informasi Utama
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-building text-dark me-1"></i> Unit Kerja (OPD)</label>
                                <select name="id_opd" id="edit_id_opd" class="form-select border-0 shadow-sm py-2 rounded-3" required>
                                    <?php foreach ($opd_list as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-fingerprint text-dark me-1"></i> NIP</label>
                                <input type="text" name="nip" id="edit_nip" class="form-control border-0 shadow-sm py-2 rounded-3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary"><i class="fas fa-user text-dark me-1"></i> Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control border-0 shadow-sm py-2 rounded-3" required>
                            </div>

                            <div class="col-md-12 mt-4">
                                <label class="form-label small fw-bold text-danger"><i class="fas fa-key text-danger me-1"></i> Ganti Password Akses</label>
                                <input type="password" name="password" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Biarkan kosong jika tidak ingin mengubah password lama">
                                <small class="text-muted" style="font-size: 0.7rem;">Isi hanya jika pegawai lupa password.</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-4" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom border-secondary-subtle" style="font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">
                            <i class="fas fa-briefcase text-dark me-2"></i>Detail Kepegawaian & Jabatan
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Status ASN</label>
                                <select name="status_asn" id="edit_status_asn" class="form-select border-0 shadow-sm py-2 rounded-3">
                                    <option value="PNS">PNS</option>
                                    <option value="PPPK">PPPK</option>
                                    <option value="PPPKPW">PPPKPW</option>
                                    <option value="TENAGA KONTRAK">TENAGA KONTRAK</option>
                                    <option value="KONTRAK BLUD">KONTRAK BLUD</option>
                                    <option value="NON ASN LAINNYA">NON ASN LAINNYA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Pangkat / Gol.</label>
                                <input type="text" name="pangkat" id="edit_pangkat" class="form-control border-0 shadow-sm py-2 rounded-3" placeholder="Cth: Pembina / IV.a">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Eselon</label>
                                <select name="eselon" id="edit_eselon" class="form-select border-0 shadow-sm py-2 rounded-3">
                                    <option value="">Non-Eselon</option>
                                    <option value="II.a">II.a</option>
                                    <option value="II.b">II.b</option>
                                    <option value="III.a">III.a</option>
                                    <option value="III.b">III.b</option>
                                    <option value="IV.a">IV.a</option>
                                    <option value="IV.b">IV.b</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label small fw-bold text-secondary">Jabatan Spesifik</label>
                                <input type="text" name="jabatan" id="edit_jabatan" class="form-control border-0 shadow-sm py-2 rounded-3" required>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label small fw-bold text-secondary">Tingkat / Level Jabatan</label>
                                <select name="level_jabatan" id="edit_level_jabatan" class="form-select border-0 shadow-sm py-2 rounded-3" required>
                                    <option value="Jabatan Pimpinan Tinggi Pratama">JPT Pratama</option>
                                    <option value="Pejabat Administrator">Administrator</option>
                                    <option value="Pejabat Pengawas">Pengawas</option>
                                    <option value="Pejabat Fungsional">Fungsional</option>
                                    <option value="Staf">Staf / Pelaksana</option>
                                </select>
                            </div>
                            <?php if (session()->get('level') == 'kabupaten'): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">Jalur Presensi (Khusus)</label>
                                    <select name="is_bercadar" id="edit_is_bercadar" class="form-select border-0 shadow-sm py-2 rounded-3">
                                        <option value="0">Kamera Wajah (Reguler)</option>
                                        <option value="1">Fingerprint (Pegawai Bercadar)</option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="is_bercadar" id="edit_is_bercadar">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 bg-white rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-5 shadow fw-bold"><i class="fas fa-check-circle me-2"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLokasiPegawai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Multi-Lokasi Penugasan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 text-center">
                    <div class="small text-muted">Pegawai:</div>
                    <div id="lokasi_nama_pegawai" class="fw-bold"></div>
                </div>
                <input type="hidden" id="lokasi_id_pegawai">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Satu atau Lebih Lokasi (Gunakan Ctrl untuk pilih banyak)</label>
                    <select id="select_id_lokasi" class="form-select border-info bg-light shadow-none" multiple style="height: 150px;">
                    </select>
                </div>
                <div class="alert alert-info py-2 small border-0">
                    <i class="fas fa-info-circle me-1"></i> Tahan tombol <b>Ctrl</b> (Windows) atau <b>Command</b> (Mac) untuk memilih lebih dari satu lokasi.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" onclick="saveLokasi()" class="btn btn-info text-white rounded-pill px-4 shadow">Simpan Multi-Lokasi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEnroll" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title small"><i class="fas fa-id-card-alt me-2"></i>Registrasi Wajah: <span id="enroll_name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopCamera()"></button>
            </div>
            <div class="modal-body text-center bg-light p-4">
                <div class="position-relative d-inline-block border border-5 border-white shadow-lg rounded-circle overflow-hidden" style="width: 280px; height: 280px;">
                    <video id="video" width="400" height="300" autoplay muted style="margin-left: -60px;"></video>
                </div>
                <div id="status_enroll" class="mt-3 fw-bold text-secondary small">Menyiapkan AI & Kamera...</div>
                <input type="hidden" id="enroll_id_pegawai">
            </div>
            <div class="modal-footer border-0">
                <button type="button" id="btnCapture" class="btn btn-success w-100 rounded-pill py-2 shadow" disabled onclick="captureFace()">
                    <i class="fas fa-fingerprint me-2"></i> Pindai & Daftarkan Wajah
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    <?php if (session()->getFlashdata('success')) : ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '<?= session()->getFlashdata('success') ?>',
            confirmButtonColor: '#155724'
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        $(document).ready(function() {
            var modalTambah = new bootstrap.Modal(document.getElementById('modalPegawai'));
            modalTambah.show();
        });
    <?php endif; ?>

    var table;
    $(document).ready(function() {
        table = $('#tabelPegawai').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "ajax": {
                "url": "<?= base_url('pegawai/ajax_list') ?>",
                "type": "POST",
                "data": function(d) {
                    d.filter_opd = $('#filter_opd').val();
                    d.<?= csrf_token() ?> = "<?= csrf_hash() ?>";
                }
            },
            "columnDefs": [{
                    "targets": [0, 5, 6, 7],
                    "orderable": false
                },
                {
                    "targets": [3, 4, 5, 6, 7],
                    "className": "text-center"
                }
            ],
            "language": {
                "search": "Cari Nama/NIP:",
                "lengthMenu": "Tampil _MENU_",
                "info": "Data _START_ - _END_ dari _TOTAL_",
                "paginate": {
                    "previous": "<",
                    "next": ">"
                }
            }
        });

        $('#filter_opd').change(function() {
            table.ajax.reload();
        });
    });

    // --- FUNGSI HAK AKSES WFA OPD (BARU KHUSUS KABUPATEN) ---
    function submitKewenanganWfa() {
        const id_opd = $('#wewenang_opd').val();
        const status = $('#wewenang_status').val();

        let target_text = id_opd ? "OPD yang dipilih" : "SELURUH OPD KABUPATEN";
        let aksi_text = status == 1 ? "MEMBERIKAN" : "MENCABUT";

        Swal.fire({
            title: 'Ubah Hak Akses',
            html: `Anda akan <b>${aksi_text}</b> Hak Akses Admin OPD untuk mengelola WFA di <b>${target_text}</b>.<br>Lanjutkan?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Eksekusi!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    didOpen: () => Swal.showLoading()
                });
                $.ajax({
                    url: '<?= base_url('pegawai/set_wewenang_opd') ?>',
                    type: 'POST',
                    data: {
                        id_opd_wewenang: id_opd,
                        status_wewenang: status,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Selesai!', res.message, 'success').then(() => {
                                $('#modalKewenanganWFA').modal('hide');
                                location.reload(); // Perlu reload agar rule berubah
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // --- FUNGSI TOGGLE WFA INDIVIDU DENGAN RENTANG TANGGAL ---
    function toggleWFA(id, next_status, nama, current_mulai, current_selesai) {
        if (next_status == 1) { // MENGAKTIFKAN WFA
            let today = new Date().toISOString().split('T')[0];
            let val_mulai = current_mulai ? current_mulai : today;
            let val_selesai = current_selesai ? current_selesai : today;

            Swal.fire({
                title: 'Seting WFA Pegawai',
                html: `
                    <p class="small text-muted mb-3">Tentukan rentang tanggal WFA untuk <b>${nama}</b></p>
                    <div class="text-start mb-2"><label class="small fw-bold">Tgl Mulai:</label><input type="date" id="swal_wfa_mulai" class="form-control bg-light" value="${val_mulai}"></div>
                    <div class="text-start"><label class="small fw-bold">Tgl Selesai:</label><input type="date" id="swal_wfa_selesai" class="form-control bg-light" value="${val_selesai}"></div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Aktifkan / Update WFA',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return {
                        mulai: document.getElementById('swal_wfa_mulai').value,
                        selesai: document.getElementById('swal_wfa_selesai').value
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesWfaIndividu(id, 1, result.value.mulai, result.value.selesai);
                }
            });
        } else {
            // MENONAKTIFKAN WFA
            Swal.fire({
                title: 'Nonaktifkan WFA',
                text: `Cabut izin WFA untuk ${nama}? Pegawai akan wajib absen di kantor sesuai radius.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Cabut Izin WFA',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    prosesWfaIndividu(id, 0, null, null);
                }
            });
        }
    }

    function prosesWfaIndividu(id, status, tgl_mulai, tgl_selesai) {
        $.ajax({
            url: '<?= base_url('pegawai/toggle_wfa_individu') ?>',
            type: 'POST',
            data: {
                id_pegawai: id,
                status_wfa: status,
                tgl_mulai: tgl_mulai,
                tgl_selesai: tgl_selesai,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            success: function(res) {
                if (res.status === 'success') {
                    table.ajax.reload(null, false);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message,
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }
        });
    }

    // --- FUNGSI TOGGLE JALUR KHUSUS BERCADAR ---
    function toggleBercadar(id, next_status, nama) {
        let action_text = next_status == 1 ? "MENGAKTIFKAN" : "MENONAKTIFKAN";
        let info_text = next_status == 1 ?
            "Pegawai ini akan menggunakan Sidik Jari untuk Absen." :
            "Pegawai ini akan kembali menggunakan pemindaian Wajah untuk Absen.";

        Swal.fire({
            title: 'Jalur Khusus (Bercadar)',
            html: `Anda akan <b>${action_text}</b> mode Fingerprint untuk <b>${nama}</b>.<br><small class="text-muted">${info_text}</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: next_status == 1 ? '#0d6efd' : '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('pegawai/toggle_bercadar') ?>',
                    type: 'POST',
                    data: {
                        id_pegawai: id,
                        status_bercadar: next_status,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: res.message,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // --- FUNGSI WFA MASSAL DENGAN RENTANG TANGGAL ---
    function toggleTglMassal() {
        let val = document.getElementById('wfa_massal_aksi').value;
        document.getElementById('wfa_massal_tgl_area').style.display = (val == 1) ? 'flex' : 'none';
    }

    function submitWfaMassal() {
        const id_opd = $('#wfa_massal_opd').val();
        const status_wfa = $('#wfa_massal_aksi').val();
        const tgl_mulai = $('#wfa_massal_mulai').val();
        const tgl_selesai = $('#wfa_massal_selesai').val();

        let target_text = id_opd ? "OPD yang dipilih" : "SELURUH PEGAWAI KABUPATEN";
        let aksi_text = status_wfa == 1 ? `MENGAKTIFKAN WFA (${tgl_mulai} s/d ${tgl_selesai})` : "MENONAKTIFKAN WFA";

        Swal.fire({
            title: 'Peringatan Massal!',
            html: `Anda akan <b>${aksi_text}</b> untuk <b>${target_text}</b>.<br>Apakah Anda yakin?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Eksekusi Massal!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    didOpen: () => Swal.showLoading()
                });
                $.ajax({
                    url: '<?= base_url('pegawai/set_wfa_massal') ?>',
                    type: 'POST',
                    data: {
                        id_opd_wfa: id_opd,
                        status_wfa_massal: status_wfa,
                        tgl_mulai_massal: tgl_mulai,
                        tgl_selesai_massal: tgl_selesai,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Selesai!', res.message, 'success').then(() => {
                                $('#modalWfaMassal').modal('hide');
                                table.ajax.reload();
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // --- FUNGSI TRANSFER PEGAWAI ---
    function openTransfer(id, nama, opd_asal) {
        $('#transfer_id_pegawai').val(id);
        $('#transfer_nama_pegawai').text(nama);
        $('#transfer_id_opd').val('');

        const modal = new bootstrap.Modal(document.getElementById('modalTransfer'));
        modal.show();
    }

    function submitTransfer() {
        const id_pegawai = $('#transfer_id_pegawai').val();
        const id_opd_baru = $('#transfer_id_opd').val();

        if (!id_opd_baru) {
            Swal.fire('Peringatan', 'Silakan pilih OPD tujuan terlebih dahulu.', 'warning');
            return;
        }

        $.ajax({
            url: '<?= base_url('pegawai/transfer_opd') ?>',
            type: 'POST',
            data: {
                id_pegawai: id_pegawai,
                id_opd_tujuan: id_opd_baru,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('Berhasil', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server.', 'error');
            }
        });
    }

    // --- FUNGSI LOKASI & WAJAH ---
    function openLokasi(id, id_opd, nama, current_ids) {
        $('#lokasi_id_pegawai').val(id);
        $('#lokasi_nama_pegawai').text(nama);
        $('#select_id_lokasi').html('<option>Memuat...</option>');

        const selectedArray = current_ids ? current_ids.split(',') : [];

        fetch('<?= base_url('pegawai/get_lokasi_by_opd') ?>/' + id_opd)
            .then(res => res.json())
            .then(data => {
                let html = '';
                if (Array.isArray(data)) {
                    data.forEach(l => {
                        let isSelected = selectedArray.includes(l.id_lokasi.toString()) ? 'selected' : '';
                        html += `<option value="${l.id_lokasi}" ${isSelected}>${l.nama_lokasi}</option>`;
                    });
                }
                $('#select_id_lokasi').html(html);
                new bootstrap.Modal(document.getElementById('modalLokasiPegawai')).show();
            });
    }

    function saveLokasi() {
        const id_pegawai = $('#lokasi_id_pegawai').val();
        const id_lokasi = $('#select_id_lokasi').val();

        $.ajax({
            url: '<?= base_url('pegawai/update_lokasi') ?>',
            type: 'POST',
            data: {
                id_pegawai: id_pegawai,
                id_lokasi: id_lokasi,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            success: function(res) {
                Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
            }
        });
    }

    // --- FUNGSI EDIT DATA ---
    async function openEdit(id) {
        fetch('<?= base_url('pegawai/edit_data') ?>/' + id)
            .then(res => res.json())
            .then(data => {
                $('#edit_id_pegawai').val(data.id_pegawai);
                $('#edit_id_opd').val(data.id_opd);
                $('#edit_nip').val(data.nip);
                $('#edit_nama_lengkap').val(data.nama_lengkap);
                $('#edit_jabatan').val(data.jabatan);
                $('#edit_level_jabatan').val(data.level_jabatan);
                $('#edit_status_asn').val(data.status_asn);
                $('#edit_pangkat').val(data.pangkat);
                $('#edit_eselon').val(data.eselon);
                $('#edit_is_bercadar').val(data.is_bercadar ?? 0);
                new bootstrap.Modal(document.getElementById('modalEditPegawai')).show();
            });
    }

    // --- BIOMETRIC LOGIC ---
    const video = document.getElementById('video');
    const statusText = document.getElementById('status_enroll');
    const btnCapture = document.getElementById('btnCapture');
    let modelsLoaded = false;

    async function loadModels() {
        if (modelsLoaded) return true;
        statusText.innerText = "Memuat Modul AI...";
        try {
            const MODEL_URL = '<?= base_url('models') ?>';
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            modelsLoaded = true;
            return true;
        } catch (e) {
            statusText.innerText = "Modul AI Gagal!";
            return false;
        }
    }

    async function startCamera() {
        const ready = await loadModels();
        if (!ready) return;
        navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user"
                }
            })
            .then(stream => {
                video.srcObject = stream;
                statusText.innerText = "Dekatkan wajah ke tengah lingkaran";
                btnCapture.disabled = false;
            })
            .catch(err => {
                statusText.innerText = "Kamera diblokir!";
            });
    }

    async function captureFace() {
        btnCapture.disabled = true;
        statusText.innerText = "Menganalisis wajah...";
        const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
            inputSize: 224
        })).withFaceLandmarks().withFaceDescriptor();

        if (detections) {
            const faceVector = JSON.stringify(Array.from(detections.descriptor));
            const formData = new FormData();
            formData.append('id_pegawai', $('#enroll_id_pegawai').val());
            formData.append('face_vector', faceVector);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            fetch('<?= base_url('pegawai/simpan_face_vector') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        location.reload();
                    } else {
                        alert("Gagal: " + res.msg);
                        btnCapture.disabled = false;
                    }
                });
        } else {
            statusText.innerText = "Wajah tidak jelas.";
            btnCapture.disabled = false;
        }
    }

    function openEnroll(id, name) {
        $('#enroll_id_pegawai').val(id);
        $('#enroll_name').text(name);
        const modal = new bootstrap.Modal(document.getElementById('modalEnroll'));
        modal.show();
        document.getElementById('modalEnroll').addEventListener('shown.bs.modal', startCamera, {
            once: true
        });
    }

    function stopCamera() {
        if (video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
    }

    document.getElementById('btnGeneratePass').onclick = function() {
        Swal.fire({
            title: 'Konfirmasi',
            text: "Hash password semua pegawai menggunakan NIP?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Jalankan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                runPasswordBatch();
            }
        });
    };

    function runPasswordBatch() {
        Swal.fire({
            title: 'Memproses Password...',
            html: '<div class="progress mt-3"><div id="pass-progress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div></div><p class="mt-2 small text-muted" id="pass-status">Menghubungi server...</p>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                executeBatch(0, 0);
            }
        });
    }

    function executeBatch(totalProcessed, totalInitial) {
        fetch('<?= base_url('pegawai/ajax_generate_password') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'process') {
                    let currentProcessed = totalProcessed + data.processed;
                    let totalData = currentProcessed + data.remaining;
                    let percent = Math.round((currentProcessed / totalData) * 100);
                    document.getElementById('pass-progress').style.width = percent + '%';
                    document.getElementById('pass-status').innerText = `Memproses ${currentProcessed} dari ${totalData} pegawai...`;
                    executeBatch(currentProcessed, totalData);
                } else {
                    Swal.fire('Selesai!', data.message, 'success').then(() => {
                        location.reload();
                    });
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Terjadi gangguan koneksi.', 'error');
            });
    }

    let tempFileName = "";
    let totalData = 0;
    let processedData = 0;

    function startUpload() {
        const file = document.getElementById('fileExcel').files[0];
        if (!file) return Swal.fire('Error', 'Pilih file dulu Pak!', 'error');

        const formData = new FormData();
        formData.append('file_excel', file);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        $('#formArea').hide();
        $('#progressArea').show();
        $('#btnCloseImport').hide();

        fetch('<?= base_url('pegawai/init_import') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    tempFileName = res.temp_file;
                    totalData = res.total_rows;
                    doBatchImport(0);
                } else {
                    Swal.fire('Gagal', res.msg, 'error').then(() => location.reload());
                }
            });
    }

    function doBatchImport(offset) {
        const formData = new FormData();
        formData.append('temp_file', tempFileName);
        formData.append('offset', offset);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= base_url('pegawai/process_import_batch') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    let percent = Math.round((offset / totalData) * 100);
                    if (percent > 100) percent = 100;
                    document.getElementById('importProgressBar').style.width = percent + '%';
                    document.getElementById('importDetail').innerText = `${offset} dari ${totalData} data selesai`;

                    if (!res.is_finished) {
                        doBatchImport(offset + 50);
                    } else {
                        document.getElementById('importProgressBar').style.width = '100%';
                        document.getElementById('importDetail').innerText = `Proses selesai`;
                        Swal.fire('Selesai!', 'Semua data berhasil diimport', 'success').then(() => location.reload());
                    }
                } else {
                    Swal.fire('Gagal', res.msg, 'error').then(() => location.reload());
                }
            });
    }

    // =====================================================================
    // [FUNGSI BARU]: Trigger Konfirmasi Reset HP Menggunakan AJAX
    // =====================================================================
    function resetDevice(id, nama) {
        Swal.fire({
            title: 'Buka Kunci HP?',
            html: `Anda yakin ingin mereset KTP Digital HP untuk pegawai <b>${nama}</b>?<br><br><small class="text-danger fw-bold">Lakukan ini HANYA jika pegawai benar-benar ganti HP baru atau HP lamanya rusak/hilang.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<i class="fas fa-unlock-alt me-1"></i> Ya, Reset HP!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: '<?= base_url('pegawai/reset_device') ?>',
                    type: 'POST',
                    data: {
                        id_pegawai: id,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Berhasil!', res.message, 'success');
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Koneksi Gagal', 'Terjadi kesalahan saat menghubungi server.', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>