<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Command Center - Presensi Polman 2.0' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* HEADER */
        .live-header {
            background-color: #ffffff;
            padding: 1.5rem 2.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .live-indicator {
            width: 10px;
            height: 10px;
            background-color: #ef4444;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse-soft 2s infinite;
        }

        @keyframes pulse-soft {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .clock-container {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            text-align: right;
        }

        .digital-clock {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
            letter-spacing: 1px;
        }

        /* RUNNING TEXT (TICKER) */
        .ticker-wrap {
            width: 100%;
            overflow: hidden;
            background-color: #1e293b;
            color: white;
            padding: 8px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .ticker {
            display: inline-block;
            white-space: nowrap;
            padding-right: 100%;
            animation: ticker 45s linear infinite;
        }

        .ticker:hover {
            animation-play-state: paused;
            cursor: pointer;
        }

        @keyframes ticker {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .ticker-item {
            display: inline-block;
            padding: 0 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* KARTU STATISTIK (8 Kolom) */
        .exec-card {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }

        .exec-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        }

        .exec-title {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            color: #64748b;
        }

        .exec-val {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
        }

        /* DATA TABLES: CARDS LAYOUT */
        .table-wrapper {
            padding: 1.8rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }

        #tableLive thead {
            display: none;
        }

        /* Sembunyikan header tabel */
        #tableLive tbody td {
            border: none !important;
            padding: 0 !important;
        }

        #tableLive tbody tr {
            background-color: transparent !important;
            animation: fadeInRow 0.5s ease forwards;
        }

        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
            border-color: #cbd5e1;
        }

        /* Filter Controls */
        .custom-select-light {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 5px 12px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6 !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
        }
    </style>
</head>

<body>

    <div class="live-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center mb-1">
                <span class="live-indicator"></span>
                <span class="text-danger fw-bold" style="font-size: 0.75rem; letter-spacing: 1.5px;">LIVE MONITORING</span>
            </div>
            <h1 class="fw-extrabold mb-1 text-dark" style="font-size: 1.8rem;">Presensi Polman 2.0 <span class="text-primary">Command Center</span></h1>
            <p class="mb-0 text-muted fw-medium" style="font-size: 0.85rem;"><i class="fas fa-calendar-alt me-2 text-primary"></i><?= date('l, d F Y') ?></p>
        </div>
        <div class="clock-container">
            <div class="digital-clock" id="jamDigital">00:00:00</div>
            <div class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 2px;">WITA ONLINE</div>
        </div>
    </div>

    <div class="ticker-wrap">
        <div class="ticker">
            <span class="ticker-item"><i class="fas fa-bolt text-warning me-1"></i> Selamat Datang di Command Center Presensi Polman 2.0</span>
            <span class="ticker-item"><i class="fas fa-users text-primary me-1"></i> Total Pegawai: <strong><?= number_format($stat_total) ?></strong></span>
            <span class="ticker-item"><i class="fas fa-star text-warning me-1"></i> Disiplin Istimewa Hari Ini: <strong><?= number_format($cat_istimewa) ?> Pegawai</strong></span>
            <span class="ticker-item"><i class="fas fa-check-circle text-success me-1"></i> Tepat Waktu: <strong><?= number_format($cat_baik) ?> Pegawai</strong></span>
            <span class="ticker-item"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Perlu Pembinaan: <strong><?= number_format($cat_bina) ?> Pegawai</strong></span>
            <span class="ticker-item"><i class="fas fa-times-circle text-danger me-1"></i> Kena Teguran: <strong><?= number_format($cat_teguran) ?> Pegawai</strong></span>
            <span class="ticker-item"><i class="fas fa-plane-departure text-info me-1"></i> Izin / Cuti / TL: <strong><?= number_format($stat_izin) ?> Pegawai</strong></span>
        </div>
    </div>

    <div class="container-fluid px-4 pb-5">
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-4 col-6">
                <div class="exec-card border-start border-4 border-primary">
                    <div class="exec-title">Total Hadir</div>
                    <div class="exec-val text-primary"><?= number_format($stat_hadir) ?></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-4 col-6">
                <div class="exec-card border-start border-4 border-success">
                    <div class="exec-title">Disiplin Tepat Waktu</div>
                    <div class="exec-val text-success"><?= number_format($cat_baik + $cat_istimewa + $cat_cukup) ?></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-4 col-6">
                <div class="exec-card border-start border-4 border-warning">
                    <div class="exec-title">Terlambat / Pembinaan</div>
                    <div class="exec-val text-warning"><?= number_format($cat_telat + $cat_bina) ?></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-4 col-6">
                <div class="exec-card border-start border-4 border-danger">
                    <div class="exec-title">Teguran / Alpa</div>
                    <div class="exec-val text-danger"><?= number_format($cat_teguran + $stat_belum) ?></div>
                </div>
            </div>
        </div>

        <div class="table-wrapper mb-5">
            <div class="row align-items-center mb-4 pb-3 border-bottom g-3">
                <div class="col-md-5">
                    <h4 class="fw-extrabold mb-1 text-dark"><i class="fas fa-stream text-primary me-2"></i>Aktivitas Presensi Terkini</h4>
                    <p class="text-muted mb-0 fw-medium" style="font-size: 0.85rem;">Diperbarui otomatis setiap 15 detik</p>
                </div>

                <div class="col-md-7 d-flex justify-content-md-end gap-2 flex-wrap">
                    <div style="width: 250px;">
                        <select id="filter_opd" class="form-select custom-select-light shadow-none">
                            <option value="">Semua Instansi</option>
                            <?php foreach ($list_opd as $o): ?>
                                <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="width: 180px;">
                        <select id="filter_shift" class="form-select custom-select-light shadow-none">
                            <option value="">Semua Shift</option>
                            <?php foreach ($list_shift as $s): ?>
                                <option value="<?= $s['id_jam_kerja'] ?>"><?= $s['nama_jadwal'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="overflow-x: hidden;">
                <table id="tableLive" class="table w-100">
                    <thead>
                        <tr>
                            <th>DATA PEGAWAI</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let streamTable;

        $(document).ready(function() {
            streamTable = $('#tableLive').DataTable({
                "processing": true,
                "serverSide": true,
                "ordering": false,
                "pageLength": 10,
                "ajax": {
                    "url": "<?= base_url('live/ajax_stream') ?>",
                    "type": "POST",
                    "data": function(d) {
                        d.filter_opd = $('#filter_opd').val();
                        d.filter_shift = $('#filter_shift').val();
                        d.<?= csrf_token() ?> = "<?= csrf_hash() ?>";
                    }
                },
                "language": {
                    "search": "Cari NIP/Nama:",
                    "lengthMenu": "Tampil _MENU_ data",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data terkini",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            $('#filter_opd, #filter_shift').change(function() {
                streamTable.ajax.reload();
            });

            // Refresh tabel tanpa mengedipkan background
            setInterval(function() {
                streamTable.ajax.reload(null, false);
            }, 300000);

            // Jam Digital
            setInterval(() => {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                document.getElementById('jamDigital').innerText = `${hours}:${minutes}:${seconds}`;
            }, 1000);
        });
    </script>
</body>

</html>