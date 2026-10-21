<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    div.dataTables_processing {
        background-color: rgba(255, 255, 255, 0.9) !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        padding: 15px !important;
        font-weight: bold;
        color: #0d6efd !important;
        z-index: 1055 !important;
    }

    /* Custom Scrollbar untuk Kalender Matrix */
    #kalenderContainer::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    #kalenderContainer::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Plotting Shift Pegawai</h5>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHapusMassal">
            <i class="fas fa-trash-alt me-1"></i> Hapus
        </button>
        <button class="btn btn-warning btn-sm rounded-pill px-3 shadow-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalTukarJaga">
            <i class="fas fa-exchange-alt me-1"></i> Tukar Jaga
        </button>

        <!-- [FITUR BARU] TOMBOL KALENDER SHIFT -->
        <button class="btn btn-info btn-sm rounded-pill px-3 shadow-sm text-white fw-bold" data-bs-toggle="modal" data-bs-target="#modalKalender">
            <i class="fas fa-calendar-alt me-1"></i> Kalender Shift
        </button>

        <!-- <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPlot">
            <i class="fas fa-plus me-1"></i> Manual
        </button> -->
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalPolaShift">
            <i class="fas fa-sync-alt me-1"></i> Pola Shift
        </button>
        <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPlotMassal">
            <i class="fas fa-layer-group me-1"></i> Plotting
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablePlotting" class="table table-hover align-middle mb-0 w-100">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4">TANGGAL</th>
                        <th>NAMA PEGAWAI</th>
                        <th>NAMA SHIFT</th>
                        <th class="text-center" width="10%">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- [FITUR BARU] MODAL KALENDER SHIFT MATRIX -->
<!-- ====================================================================== -->
<div class="modal fade" id="modalKalender" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-calendar-alt me-2"></i>Kalender Plotting Shift Bulanan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formFilterKalender" class="mb-4 bg-white p-3 rounded shadow-sm border">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Bulan</label>
                            <select name="bulan" id="kalenderBulan" class="form-select border-secondary">
                                <?php
                                $bulans = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                foreach ($bulans as $b => $nama): ?>
                                    <option value="<?= $b ?>" <?= date('m') == $b ? 'selected' : '' ?>><?= $nama ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold mb-1">Tahun</label>
                            <select name="tahun" id="kalenderTahun" class="form-select border-secondary">
                                <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                            </select>
                        </div>

                        <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                            <div class="col-md-4">
                                <label class="small fw-bold mb-1">Pilih Instansi (OPD)</label>
                                <select name="id_opd" id="kalenderOpd" class="form-select border-secondary select2-kalender">
                                    <option value="">-- Semua OPD --</option>
                                    <?php foreach ($opd as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-info text-white w-100 fw-bold shadow-sm mb-2">
                                <i class="fas fa-search me-1"></i> Tampilkan Matriks
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success w-50 btn-sm fw-bold shadow-sm" onclick="exportKalender('excel')">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger w-50 btn-sm fw-bold shadow-sm" onclick="exportKalender('pdf')">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <!-- Kontainer untuk Tabel HTML dari AJAX -->
                        <div class="table-responsive" id="kalenderContainer" style="max-height: 65vh; overflow-y: auto;">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-table fa-3x mb-3 opacity-25"></i>
                                <p class="fw-bold">Silakan sesuaikan filter dan klik "Tampilkan Matriks" untuk memuat kalender.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlot" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold">Plotting Harian</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSimpanManual" action="<?= base_url('plotting/simpan') ?>" method="POST">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="csrf_val">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Pegawai</label>
                        <select name="id_pegawai" class="form-select bg-light border-light select2-manual" required>
                            <option value="">-- Cari NIP / Nama Pegawai --</option>
                            <?php foreach ($pegawai as $pg): ?>
                                <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Pilih Shift</label>
                        <select name="id_jam_kerja" class="form-select bg-light border-light" required>
                            <?php foreach ($jam_kerja as $jk): ?>
                                <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Tanggal Dinas</label>
                        <input type="date" name="tanggal" class="form-control bg-light border-light" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlotMassal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-layer-group me-2"></i>Plotting Massal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSimpanMassal" action="<?= base_url('plotting/simpan_massal') ?>" method="POST">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="csrf_val">
                <div class="modal-body p-4">

                    <label class="small fw-bold mb-2">Target Jadwal</label>
                    <div class="d-flex gap-3 mb-3">
                        <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeGlobal" value="global" onclick="toggleScope()" checked>
                                <label class="form-check-label small" for="scopeGlobal">Semua ASN (Global)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeOPD" value="opd" onclick="toggleScope()">
                                <label class="form-check-label small" for="scopeOPD">Per OPD</label>
                            </div>
                        <?php else: ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeGlobal" value="global" onclick="toggleScope()" checked>
                                <label class="form-check-label small" for="scopeGlobal">Seluruh Pegawai (OPD Anda)</label>
                            </div>
                        <?php endif; ?>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope" id="scopePegawai" value="pegawai" onclick="toggleScope()">
                            <label class="form-check-label small" for="scopePegawai">Pilih Pegawai</label>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div id="boxGlobal" class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Jadwal akan diterapkan ke <b>seluruh pegawai</b> sesuai filter di atas.
                        </div>

                        <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                            <div id="boxOPD" class="d-none">
                                <label class="small fw-bold">Pilih OPD</label>
                                <select name="id_opd" class="form-select border-success select2-massal">
                                    <option value="">-- Pilih Dinas/Badan --</option>
                                    <?php foreach ($opd as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div id="boxPegawai" class="d-none">
                            <label class="small fw-bold">Pilih Pegawai (Bisa Lebih Dari 1)</label>
                            <select name="target_pegawai[]" class="form-select border-success select2-massal-multiple" multiple="multiple">
                                <?php foreach ($pegawai as $pg): ?>
                                    <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Pilih Shift / Jam Kerja</label>
                        <select name="id_jam_kerja" class="form-select bg-light border-light" required>
                            <?php foreach ($jam_kerja as $jk): ?>
                                <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="text-muted">

                    <div class="mb-3">
                        <label class="small fw-bold mb-2">Metode Pilih Tanggal</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_tanggal" id="tipeRange" value="range" onclick="toggleTipeTanggal()" checked>
                                <label class="form-check-label small" for="tipeRange">Rentang Tanggal (Awal-Akhir)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_tanggal" id="tipeMulti" value="multiple" onclick="toggleTipeTanggal()">
                                <label class="form-check-label small" for="tipeMulti">Pilih Beberapa Tanggal</label>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="boxRangeTanggal">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Dari Tanggal</label>
                            <input type="date" name="tgl_mulai" class="form-control bg-light border-light">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Sampai Tanggal</label>
                            <input type="date" name="tgl_selesai" class="form-control bg-light border-light">
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="boxMultiTanggal">
                        <label class="small fw-bold">Pilih Tanggal Acak (Bisa Lebih Dari 1)</label>
                        <input type="text" name="tanggal_multiple" id="flatpickr-multi" class="form-control border-success bg-white" placeholder="Klik untuk memilih tanggal...">
                    </div>

                    <div class="alert alert-warning small py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Data jadwal lama di tanggal tersebut akan <b>ditimpa</b>.
                    </div>

                    <br>
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">Terapkan Jadwal Massal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- [FITUR BARU] MODAL POLA SHIFT (TEMPLATE SIKLUS) -->
<!-- ====================================================================== -->
<div class="modal fade" id="modalPolaShift" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2"></i>Plotting Pola Shift (Siklus)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolaShift" action="<?= base_url('plotting/simpan_pola') ?>" method="POST">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="csrf_val">
                <div class="modal-body p-4">
                    <div class="alert alert-info small border-0 py-2">
                        <i class="fas fa-info-circle me-1"></i> Buat template pola shift berulang. Sistem otomatis mengulang pola ini dari Tanggal Mulai hingga Selesai.
                    </div>

                    <label class="small fw-bold mb-2">Target Jadwal</label>
                    <div class="d-flex gap-3 mb-3">
                        <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope_pola" id="scopePolaGlobal" value="global" onclick="toggleScopePola()" checked>
                                <label class="form-check-label small" for="scopePolaGlobal">Semua ASN (Global)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope_pola" id="scopePolaOPD" value="opd" onclick="toggleScopePola()">
                                <label class="form-check-label small" for="scopePolaOPD">Per OPD</label>
                            </div>
                        <?php else: ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope_pola" id="scopePolaGlobal" value="global" onclick="toggleScopePola()" checked>
                                <label class="form-check-label small" for="scopePolaGlobal">Seluruh Pegawai (OPD Anda)</label>
                            </div>
                        <?php endif; ?>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope_pola" id="scopePolaPegawai" value="pegawai" onclick="toggleScopePola()">
                            <label class="form-check-label small" for="scopePolaPegawai">Pilih Pegawai</label>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div id="boxPolaGlobal" class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Jadwal akan diterapkan ke <b>seluruh pegawai</b> sesuai filter di atas.
                        </div>

                        <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                            <div id="boxPolaOPD" class="d-none">
                                <label class="small fw-bold">Pilih OPD</label>
                                <select name="id_opd_pola" class="form-select border-primary select2-pola">
                                    <option value="">-- Pilih Dinas/Badan --</option>
                                    <?php foreach ($opd as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div id="boxPolaPegawai" class="d-none">
                            <label class="small fw-bold">Pilih Pegawai (Bisa Lebih Dari 1)</label>
                            <select name="target_pegawai_pola[]" class="form-select border-primary select2-pola-multiple" multiple="multiple">
                                <?php foreach ($pegawai as $pg): ?>
                                    <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="text-muted">

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Dari Tanggal (Hari 1 Pola)</label>
                            <input type="date" name="tgl_mulai" class="form-control bg-light border-light" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Sampai Tanggal (Berakhir)</label>
                            <input type="date" name="tgl_selesai" class="form-control bg-light border-light" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold d-flex justify-content-between align-items-center">
                            Susunan Siklus / Pola Shift
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2" onclick="addPolaRow()">
                                <i class="fas fa-plus"></i> Tambah Hari
                            </button>
                        </label>
                        <div id="polaContainer" class="p-3 bg-light border rounded">
                            <div class="row mb-2 pola-row align-items-end" id="polaRow_1">
                                <div class="col-3">
                                    <span class="badge bg-secondary mb-1">Hari Ke-1</span>
                                </div>
                                <div class="col-8">
                                    <select name="pola_jam_kerja[]" class="form-select border-primary" required>
                                        <option value="LIBUR">-- LEPAS JAGA / LIBUR --</option>
                                        <?php foreach ($jam_kerja as $jk): ?>
                                            <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-1 text-end">
                                </div>
                            </div>
                            <div class="row mb-2 pola-row align-items-end" id="polaRow_2">
                                <div class="col-3">
                                    <span class="badge bg-secondary mb-1">Hari Ke-2</span>
                                </div>
                                <div class="col-8">
                                    <select name="pola_jam_kerja[]" class="form-select border-primary" required>
                                        <option value="LIBUR" selected>-- LEPAS JAGA / LIBUR --</option>
                                        <?php foreach ($jam_kerja as $jk): ?>
                                            <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-1 text-end">
                                    <button type="button" class="btn btn-sm btn-danger rounded-circle" onclick="removePolaRow(2)"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning small py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Jadwal lama di rentang tanggal tersebut akan <b>ditimpa</b> oleh siklus pola ini.
                    </div>

                    <br>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Generate Pola Massal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapusMassal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Hapus Jadwal Massal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formHapusMassal" action="<?= base_url('plotting/hapus_massal') ?>" method="POST">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="csrf_val">
                <div class="modal-body p-4">
                    <div class="alert alert-warning small border-0">
                        <i class="fas fa-info-circle me-1"></i> Pilih kriteria data yang ingin dihapus secara permanen.
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Scope Penghapusan</label>
                        <select name="scope_hapus" class="form-select form-select-sm" id="scopeHapus" onchange="toggleScopeHapus()">
                            <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                                <option value="global">Semua ASN (Global)</option>
                                <option value="opd">Per OPD / Instansi</option>
                            <?php else: ?>
                                <option value="global">Seluruh Pegawai (OPD Anda)</option>
                            <?php endif; ?>
                            <option value="pegawai">Pilih Beberapa Pegawai</option>
                        </select>
                    </div>

                    <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                        <div id="boxOPDHapus" class="d-none mb-3">
                            <label class="small fw-bold">Pilih OPD</label>
                            <select name="id_opd_hapus" class="form-select form-select-sm border-danger select2-hapus">
                                <option value="">-- Pilih Dinas/Badan --</option>
                                <?php foreach ($opd as $o): ?>
                                    <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div id="boxPegawaiHapus" class="d-none mb-3">
                        <label class="small fw-bold">Pilih Pegawai (Bisa Lebih Dari 1)</label>
                        <select name="target_pegawai_hapus[]" class="form-select form-select-sm border-danger select2-hapus-multiple" multiple="multiple">
                            <?php foreach ($pegawai as $pg): ?>
                                <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Dari Tanggal</label>
                            <input type="date" name="tgl_mulai_hapus" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Sampai Tanggal</label>
                            <input type="date" name="tgl_selesai_hapus" class="form-control form-control-sm" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold" onclick="return confirm('Apakah Anda yakin? Data jadwal yang dihapus tidak bisa dikembalikan.')">
                        MULAI HAPUS MASSAL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTukarJaga" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning border-0">
                <h6 class="modal-title fw-bold text-dark"><i class="fas fa-exchange-alt me-2"></i>Tukar Jaga Pegawai</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTukarJaga" action="<?= base_url('plotting/proses_tukar_jaga') ?>" method="POST">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="csrf_val">
                <div class="modal-body p-4 bg-light">
                    <div class="alert alert-info small border-0 py-2">
                        <i class="fas fa-info-circle me-1"></i> Pilih tanggal dan kedua pegawai yang sepakat untuk bertukar jadwal dinas (shift).
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Tanggal Pertukaran <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_tukar" id="tanggalTukar" class="form-control bg-white border-primary" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-primary">Pegawai 1 (Pemohon)</label>
                        <select name="id_pegawai_1" id="id_pegawai_1" class="form-select select2-tukar" required>
                            <option value="">-- Cari NIP / Nama --</option>
                            <?php foreach ($pegawai as $pg): ?>
                                <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div id="infoShift1" class="small mt-2 text-primary fw-bold fst-italic"></div>
                    </div>

                    <div class="d-flex justify-content-center my-1 text-muted">
                        <i class="fas fa-arrows-alt-v fa-lg"></i>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-danger">Pegawai 2 (Pengganti)</label>
                        <select name="id_pegawai_2" id="id_pegawai_2" class="form-select select2-tukar" required>
                            <option value="">-- Cari NIP / Nama --</option>
                            <?php foreach ($pegawai as $pg): ?>
                                <option value="<?= $pg['id_pegawai'] ?>"><?= $pg['nip'] ?> - <?= $pg['nama_lengkap'] ?> (<?= $pg['nama_opd'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div id="infoShift2" class="small mt-2 text-danger fw-bold fst-italic"></div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold text-dark shadow-sm">
                        Proses Tukar Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    function updateCsrf(newHash) {
        if (newHash) {
            csrfHash = newHash;
            $('.csrf_val').val(newHash);
        }
    }

    function toggleTipeTanggal() {
        const tipeRange = document.getElementById('tipeRange').checked;
        const boxRange = document.getElementById('boxRangeTanggal');
        const boxMulti = document.getElementById('boxMultiTanggal');

        if (tipeRange) {
            boxRange.classList.remove('d-none');
            boxMulti.classList.add('d-none');
            document.getElementById('flatpickr-multi').required = false;
        } else {
            boxRange.classList.add('d-none');
            boxMulti.classList.remove('d-none');
            document.getElementById('flatpickr-multi').required = true;
        }
    }

    function toggleScopeHapus() {
        const val = document.getElementById('scopeHapus').value;
        const boxOPD = document.getElementById('boxOPDHapus');
        const boxPeg = document.getElementById('boxPegawaiHapus');

        if (boxOPD) boxOPD.classList.add('d-none');
        if (boxPeg) boxPeg.classList.add('d-none');

        if (val === 'opd' && boxOPD) boxOPD.classList.remove('d-none');
        if (val === 'pegawai' && boxPeg) boxPeg.classList.remove('d-none');
    }

    function toggleScope() {
        const boxGlobal = document.getElementById('boxGlobal');
        const boxOPD = document.getElementById('boxOPD');
        const boxPegawai = document.getElementById('boxPegawai');

        if (boxGlobal) boxGlobal.classList.add('d-none');
        if (boxOPD) boxOPD.classList.add('d-none');
        if (boxPegawai) boxPegawai.classList.add('d-none');

        if (document.getElementById('scopeGlobal').checked && boxGlobal) {
            boxGlobal.classList.remove('d-none');
        } else if (document.getElementById('scopeOPD') && document.getElementById('scopeOPD').checked && boxOPD) {
            boxOPD.classList.remove('d-none');
        } else if (document.getElementById('scopePegawai').checked && boxPegawai) {
            boxPegawai.classList.remove('d-none');
        }
    }

    // --- POLA SHIFT JS ---
    let polaCounter = 2;

    function addPolaRow() {
        polaCounter++;
        let options = `<option value="LIBUR" selected>-- LEPAS JAGA / LIBUR --</option>`;
        <?php foreach ($jam_kerja as $jk): ?>
            options += `<option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>`;
        <?php endforeach; ?>

        let html = `
            <div class="row mb-2 pola-row align-items-end" id="polaRow_${polaCounter}">
                <div class="col-3">
                    <span class="badge bg-secondary mb-1">Hari Ke-<span class="nomor-pola">${polaCounter}</span></span>
                </div>
                <div class="col-8">
                    <select name="pola_jam_kerja[]" class="form-select border-primary" required>
                        ${options}
                    </select>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-sm btn-danger rounded-circle" onclick="removePolaRow(${polaCounter})"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        $('#polaContainer').append(html);
        reindexPola();
    }

    function removePolaRow(id) {
        if ($('.pola-row').length <= 1) {
            Swal.fire('Error', 'Minimal harus ada 1 pola hari.', 'error');
            return;
        }
        $('#polaRow_' + id).remove();
        reindexPola();
    }

    function reindexPola() {
        let count = 1;
        $('.pola-row').each(function() {
            $(this).find('.nomor-pola').text(count);
            $(this).find('span.badge').text('Hari Ke-' + count);
            count++;
        });
    }

    function toggleScopePola() {
        const boxGlobal = document.getElementById('boxPolaGlobal');
        const boxOPD = document.getElementById('boxPolaOPD');
        const boxPegawai = document.getElementById('boxPolaPegawai');

        if (boxGlobal) boxGlobal.classList.add('d-none');
        if (boxOPD) boxOPD.classList.add('d-none');
        if (boxPegawai) boxPegawai.classList.add('d-none');

        if (document.getElementById('scopePolaGlobal').checked && boxGlobal) {
            boxGlobal.classList.remove('d-none');
        } else if (document.getElementById('scopePolaOPD') && document.getElementById('scopePolaOPD').checked && boxOPD) {
            boxOPD.classList.remove('d-none');
        } else if (document.getElementById('scopePolaPegawai').checked && boxPegawai) {
            boxPegawai.classList.remove('d-none');
        }
    }

    function hapusPlotting(id) {
        Swal.fire({
            title: 'Hapus Jadwal?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                let payload = {
                    id_plotting: id
                };
                payload[csrfName] = csrfHash;

                $.ajax({
                    url: "<?= base_url('plotting/hapus') ?>",
                    type: "POST",
                    data: payload,
                    dataType: "json",
                    success: function(response) {
                        updateCsrf(response.csrf_hash);

                        if (response.status === 'success') {
                            $('#tablePlotting').DataTable().ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Sesi mungkin berakhir atau server error. Silakan refresh halaman.', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        // Init Select2 & Flatpickr
        $('.select2-manual').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalPlot'),
            width: '100%'
        });
        $('.select2-massal').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalPlotMassal'),
            width: '100%'
        });
        $('.select2-massal-multiple').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalPlotMassal'),
            width: '100%',
            placeholder: '-- Pilih NIP / Nama Pegawai --',
            allowClear: true
        });
        $('.select2-hapus').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalHapusMassal'),
            width: '100%'
        });
        $('.select2-hapus-multiple').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalHapusMassal'),
            width: '100%',
            placeholder: '-- Pilih NIP / Nama Pegawai --',
            allowClear: true
        });
        $('.select2-tukar').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalTukarJaga'),
            width: '100%'
        });

        // Init Select2 Kalender jika OPD tersedia
        if ($('.select2-kalender').length > 0) {
            $('.select2-kalender').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalKalender'),
                width: '100%'
            });
        }

        // Init Select2 Pola Shift
        $('.select2-pola').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalPolaShift'),
            width: '100%'
        });
        $('.select2-pola-multiple').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalPolaShift'),
            width: '100%',
            placeholder: '-- Pilih NIP / Nama Pegawai --',
            allowClear: true
        });

        flatpickr("#flatpickr-multi", {
            mode: "multiple",
            dateFormat: "Y-m-d",
            conjunction: ", "
        });

        $('#tablePlotting').DataTable({
            processing: true,
            serverSide: true,
            order: [],
            ajax: {
                url: "<?= base_url('plotting/ajax_list') ?>",
                type: "POST",
                data: function(d) {
                    d[csrfName] = csrfHash;
                },
                dataSrc: function(json) {
                    updateCsrf(json.csrf_hash);
                    return json.data;
                }
            },
            language: {
                processing: '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Memuat...</span></div><h6 class="mt-2 text-primary fw-bold">Memuat Data Plotting...</h6>',
                search: "Pencarian:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "Lanjut",
                    previous: "Mundur"
                }
            },
            columnDefs: [{
                targets: [3],
                orderable: false
            }]
        });

        // Form Simpan Manual
        $('#formSimpanManual').on('submit', function(e) {
            e.preventDefault();
            let formBtn = $(this).find('button[type="submit"]');
            formBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    updateCsrf(response.csrf_hash);
                    formBtn.prop('disabled', false).text('Simpan Jadwal');

                    if (response.status === 'success') {
                        $('#modalPlot').modal('hide');
                        $('#tablePlotting').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#formSimpanManual')[0].reset();
                        $('.select2-manual').val(null).trigger('change');
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    formBtn.prop('disabled', false).text('Simpan Jadwal');
                    Swal.fire('Error!', 'Terjadi kesalahan pada server', 'error');
                }
            });
        });

        // Form Simpan Massal
        $('#formSimpanMassal').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Memproses Data...',
                html: 'Mohon tunggu, sistem sedang membuat ribuan jadwal plotting. <b>Jangan tutup jendela ini.</b>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    updateCsrf(response.csrf_hash);
                    if (response.status === 'success') {
                        $('#modalPlotMassal').modal('hide');
                        $('#tablePlotting').DataTable().ajax.reload(null, false);
                        Swal.fire('Berhasil!', response.message, 'success');
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                }
            });
        });

        // Form Simpan Pola Shift
        $('#formPolaShift').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Memproses Pola...',
                html: 'Sistem sedang men-generate siklus jadwal shift. <b>Jangan tutup jendela ini.</b>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    updateCsrf(response.csrf_hash);
                    if (response.status === 'success') {
                        $('#modalPolaShift').modal('hide');
                        $('#tablePlotting').DataTable().ajax.reload(null, false);
                        Swal.fire('Berhasil!', response.message, 'success');
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                }
            });
        });

        // Form Hapus Massal
        $('#formHapusMassal').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus Massal?',
                text: "Ribuan data jadwal akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Eksekusi!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus Data...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function(response) {
                            updateCsrf(response.csrf_hash);
                            if (response.status === 'success') {
                                $('#modalHapusMassal').modal('hide');
                                $('#tablePlotting').DataTable().ajax.reload(null, false);
                                Swal.fire('Berhasil!', response.message, 'success');
                            } else {
                                Swal.fire('Gagal!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                        }
                    });
                }
            });
        });

        // Form Tukar Jaga
        $('#formTukarJaga').on('submit', function(e) {
            e.preventDefault();
            let formBtn = $(this).find('button[type="submit"]');
            formBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    updateCsrf(response.csrf_hash);
                    formBtn.prop('disabled', false).text('Proses Tukar Jadwal');

                    if (response.status === 'success') {
                        $('#modalTukarJaga').modal('hide');
                        $('#tablePlotting').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        $('#formTukarJaga')[0].reset();
                        $('.select2-tukar').val(null).trigger('change');
                        $('#infoShift1, #infoShift2').text('');
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    formBtn.prop('disabled', false).text('Proses Tukar Jadwal');
                    Swal.fire('Error!', 'Terjadi kesalahan pada server saat menukar jadwal.', 'error');
                }
            });
        });

        // =========================================================================
        // [FITUR BARU] AJAX LOAD KALENDER MATRIX 
        // =========================================================================
        $('#formFilterKalender').on('submit', function(e) {
            e.preventDefault();

            let btnSubmit = $(this).find('button[type="submit"]');
            let originalText = btnSubmit.html();

            btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memuat...');
            $('#kalenderContainer').html('<div class="text-center py-5 text-primary"><i class="fas fa-circle-notch fa-spin fa-3x mb-3"></i><p>Menyusun matriks kalender, mohon tunggu...</p></div>');

            let payload = {
                bulan: $('#kalenderBulan').val(),
                tahun: $('#kalenderTahun').val(),
            };

            if ($('#kalenderOpd').length > 0) {
                payload.id_opd = $('#kalenderOpd').val();
            }

            payload[csrfName] = csrfHash;

            $.ajax({
                url: "<?= base_url('plotting/ajax_kalender') ?>",
                type: "POST",
                data: payload,
                dataType: "json",
                success: function(response) {
                    updateCsrf(response.csrf_hash);
                    btnSubmit.prop('disabled', false).html(originalText);

                    if (response.status === 'success') {
                        $('#kalenderContainer').html(response.html);
                        // Inisialisasi Tooltip Bootstrap pada tabel kalender
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    } else {
                        $('#kalenderContainer').html('<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Gagal memuat kalender.</p></div>');
                    }
                },
                error: function() {
                    btnSubmit.prop('disabled', false).html(originalText);
                    $('#kalenderContainer').html('<div class="text-center py-5 text-danger"><i class="fas fa-wifi fa-3x mb-3"></i><p>Terjadi kesalahan jaringan atau sesi berakhir.</p></div>');
                }
            });
        });

        // AJAX UNTUK CEK SHIFT SAAT INI SECARA REAL-TIME
        function cekShiftRealTime(id_pegawai, elementTarget) {
            let tanggal = $('#tanggalTukar').val();

            if (!tanggal || !id_pegawai) {
                $(elementTarget).text('');
                return;
            }

            $(elementTarget).html('<i class="fas fa-spinner fa-spin me-1"></i>Mengecek data...');

            let payload = {
                id_pegawai: id_pegawai,
                tanggal: tanggal
            };
            payload[csrfName] = csrfHash;

            $.ajax({
                url: "<?= base_url('plotting/cek_shift_pegawai') ?>",
                type: "POST",
                data: payload,
                dataType: "json",
                success: function(response) {
                    if (response.csrf_hash) updateCsrf(response.csrf_hash);

                    let shiftText = response.shift;
                    if (shiftText === 'Lepas Jaga (Kosong)') {
                        $(elementTarget).html('<i class="fas fa-exclamation-circle me-1"></i>Jadwal Asli: <span class="text-secondary">' + shiftText + '</span>');
                    } else {
                        $(elementTarget).html('<i class="fas fa-check-circle me-1"></i>Jadwal Asli: <b>' + shiftText + '</b>');
                    }
                },
                error: function() {
                    $(elementTarget).html('<i class="fas fa-times-circle me-1"></i>Gagal mengecek jadwal');
                }
            });
        }

        $('#tanggalTukar').on('change', function() {
            let pg1 = $('#id_pegawai_1').val();
            let pg2 = $('#id_pegawai_2').val();
            if (pg1) cekShiftRealTime(pg1, '#infoShift1');
            if (pg2) cekShiftRealTime(pg2, '#infoShift2');
        });

        $('#id_pegawai_1').on('change', function() {
            cekShiftRealTime($(this).val(), '#infoShift1');
        });

        $('#id_pegawai_2').on('change', function() {
            cekShiftRealTime($(this).val(), '#infoShift2');
        });

        $('#modalTukarJaga').on('hidden.bs.modal', function() {
            $('#infoShift1, #infoShift2').text('');
        });

    });

    // =========================================================================
    // [FITUR BARU] FUNGSI EXPORT EXCEL & PDF
    // =========================================================================
    function exportKalender(type) {
        let bulan = $('#kalenderBulan').val();
        let tahun = $('#kalenderTahun').val();
        let id_opd = $('#kalenderOpd').length > 0 ? $('#kalenderOpd').val() : '';

        let url = '';
        if (type === 'excel') {
            url = '<?= base_url('plotting/export_excel') ?>';
        } else if (type === 'pdf') {
            url = '<?= base_url('plotting/export_pdf') ?>';
        }

        url += `?bulan=${bulan}&tahun=${tahun}&id_opd=${id_opd}`;
        window.open(url, '_blank');
    }
</script>

<?= $this->endSection() ?>