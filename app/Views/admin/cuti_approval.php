<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="mb-4">
    <h5 class="fw-bold mt-2">Daftar Pengajuan Cuti</h5>
    <p class="small text-muted">Persetujuan permohonan izin/cuti dari pegawai</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light small text-uppercase">
                <tr>
                    <th class="ps-4">Pegawai</th>
                    <th>Jenis Cuti</th>
                    <th>Tanggal Cuti</th>
                    <th width="250">Alasan</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pengajuan as $p): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark"><?= $p->nama_pegawai ?></div>
                        <div class="small text-muted">ID: #<?= $p->pegawai_id ?></div>
                    </td>
                    <td><span class="small fw-bold text-primary"><?= $p->nama_cuti ?></span></td>
                    <td class="small">
                        <i class="far fa-calendar-alt text-muted me-1"></i> <?= date('d/m/y', strtotime($p->tgl_mulai)) ?> - <?= date('d/m/y', strtotime($p->tgl_selesai)) ?>
                    </td>
                    <td><div class="small text-muted" style="line-height: 1.2;"><em>"<?= $p->alasan ?>"</em></div></td>
                    <td class="text-center">
                        <?php 
                            $clr = 'bg-warning text-dark';
                            if($p->status == 'disetujui') $clr = 'bg-success';
                            if($p->status == 'ditolak') $clr = 'bg-danger';
                        ?>
                        <span class="badge <?= $clr ?> rounded-pill px-3 shadow-sm small uppercase"><?= $p->status ?></span>
                    </td>
                    <td class="text-center">
                        <?php if($p->status == 'pending'): ?>
                        <form action="<?= base_url('cuti/proses_approval') ?>" method="POST" class="d-flex justify-content-center gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_pengajuan" value="<?= $p->id_pengajuan ?>">
                            <button type="submit" name="status" value="disetujui" class="btn btn-success btn-sm rounded-circle shadow-sm"><i class="fas fa-check"></i></button>
                            <button type="submit" name="status" value="ditolak" class="btn btn-danger btn-sm rounded-circle shadow-sm"><i class="fas fa-times"></i></button>
                        </form>
                        <?php else: ?>
                            <i class="fas fa-check-double text-muted small"></i>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>