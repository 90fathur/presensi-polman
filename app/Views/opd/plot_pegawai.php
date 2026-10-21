<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <a href="<?= base_url('opd/lokasi/'.$id_opd) ?>" class="btn btn-sm btn-secondary mb-2 rounded-pill shadow-sm text-white px-3">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Pengaturan Lokasi
    </a>
    <h5 class="fw-bold mt-2">👥 Plotting Pegawai ke Titik: <span class="text-primary"><?= $lokasi['nama_lokasi'] ?></span></h5>
    <p class="text-muted small">Pilih pegawai yang diizinkan melakukan presensi di lokasi ini (Bisa lebih dari satu lokasi).</p>
</div>

<div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="inputSearch" class="form-control border-0 shadow-none" placeholder="Cari Nama atau NIP pegawai di sini...">
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
    <form action="<?= base_url('opd/simpan_plot_pegawai') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id_lokasi" value="<?= $lokasi['id_lokasi'] ?>">
        <input type="hidden" name="id_opd" value="<?= $id_opd ?>">
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablePegawai">
                    <thead class="bg-light small">
                        <tr>
                            <th width="60" class="text-center ps-4">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                            </th>
                            <th>NAMA PEGAWAI</th>
                            <th>NIP</th>
                            <th class="text-center">STATUS AKSES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pegawai as $p): 
                            // Logika Multi-Lokasi: Cek apakah ID lokasi ini ada di dalam list id_lokasi pegawai
                            $allowed_locations = !empty($p['id_lokasi']) ? explode(',', $p['id_lokasi']) : [];
                            $is_checked = in_array($lokasi['id_lokasi'], $allowed_locations);
                        ?>
                        <tr class="pegawai-row">
                            <td class="text-center ps-4">
                                <input type="checkbox" name="pegawai[]" value="<?= $p['id_pegawai'] ?>" 
                                       class="form-check-input row-check" <?= $is_checked ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <div class="fw-bold small"><?= $p['nama_lengkap'] ?></div>
                            </td>
                            <td class="small text-muted"><?= $p['nip'] ?></td>
                            <td class="text-center">
                                <?php if($is_checked): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">Terdaftar</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border px-3">Tanpa Akses</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr id="noData" style="display: none;">
                            <td colspan="4" class="text-center py-5 text-muted small">
                                <i class="fas fa-user-slash fa-2x mb-2 opacity-25"></i><br>Pegawai tidak ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white p-3 border-0 text-center">
            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-bold transition-all" id="btnSimpan">
                <i class="fas fa-save me-1"></i> Simpan (<span id="countCheck">0</span>) Pegawai Pilihan
            </button>
        </div>
    </form>
</div>

<script>
    // 1. FUNGSI UPDATE COUNTER & WARNA TOMBOL
    function updateCounter() {
        const count = document.querySelectorAll('.row-check:checked').length;
        document.getElementById('countCheck').innerText = count;
        
        const btn = document.getElementById('btnSimpan');
        if (count > 0) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
        } else {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }
    }

    // 2. LOGIKA SEARCHING (LIVE FILTER)
    document.getElementById('inputSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.pegawai-row');
        let noData = document.getElementById('noData');
        let found = false;

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = "";
                found = true;
            } else {
                row.style.display = "none";
            }
        });

        noData.style.display = found ? "none" : "";
    });

    // 3. LOGIKA SELECT ALL (HANYA YANG TAMPIL)
    document.getElementById('checkAll').onclick = function() {
        var checkboxes = document.querySelectorAll('.row-check');
        for (var checkbox of checkboxes) {
            // Hanya mencentang yang tidak disembunyikan oleh filter search
            if (checkbox.closest('tr').style.display !== 'none') {
                checkbox.checked = this.checked;
            }
        }
        updateCounter();
    }

    // 4. EVENT LISTENER UNTUK SETIAP CHECKBOX INDIVIDU
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });

    // 5. JALANKAN SAAT HALAMAN DIBUKA
    document.addEventListener('DOMContentLoaded', updateCounter);
</script>

<style>
    .transition-all { transition: all 0.3s ease; }
    #tablePegawai thead th { border-top: none; }
</style>

<?= $this->endSection() ?>