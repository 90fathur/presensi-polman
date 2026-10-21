<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Web Check-in Apel - <?= esc($apel['nama_apel']) ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-gradient: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .checkin-wrapper {
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .checkin-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 1);
            padding: 35px 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-container img {
            width: 65px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 0;
        }

        .apel-badge {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .apel-badge h5 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .apel-meta {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .apel-meta div i {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .radius-alert {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
        }

        .form-floating>.form-control {
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            padding: 1rem 1rem 1rem 3rem;
            font-weight: 500;
            font-size: 16px;
            height: calc(3.5rem + 2px);
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }

        .form-floating>.form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
            z-index: 10;
        }

        .btn-checkin {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-checkin:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }

        .btn-checkin:disabled {
            background: #cbd5e1;
            box-shadow: none;
            transform: none;
            color: #f8fafc;
        }

        .status-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .status-loading {
            color: #f59e0b;
            background: #fef3c7;
        }

        .status-success {
            color: #059669;
            background: #d1fae5;
        }

        .status-error {
            color: #dc2626;
            background: #fee2e2;
        }

        .spinner-grow {
            width: 1rem;
            height: 1rem;
        }

        .alert {
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            margin-top: 15px;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="checkin-wrapper">
        <div class="checkin-card">

            <div class="logo-container">
                <img src="<?= base_url('assets/img/logo-192.png') ?>" alt="Logo Polman">
                <h3 class="header-title">Presensi Polman</h3>
                <p class="header-subtitle">Web Check-in Terintegrasi</p>
            </div>

            <div class="apel-badge">
                <h5><?= esc($apel['nama_apel']) ?></h5>
                <div class="apel-meta">
                    <div><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($apel['tanggal'])) ?></div>
                    <div><i class="far fa-clock"></i> <?= substr($apel['jam_mulai'], 0, 5) ?> WITA</div>
                </div>
                <div class="radius-alert">
                    <i class="fas fa-map-marker-alt"></i> Radius Maksimal: <?= esc($apel['radius'] ?? 100) ?>m
                </div>
            </div>

            <form id="formCheckin">
                <?= csrf_field() ?>
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <div class="position-relative mb-4">
                    <i class="fas fa-id-badge input-icon"></i>
                    <div class="form-floating">
                        <input type="number" class="form-control" id="nip" name="nip" placeholder="NIP" required autocomplete="off">
                        <label for="nip" style="padding-left: 3rem;">Nomor Induk Pegawai (NIP)</label>
                    </div>
                </div>

                <div id="locationStatus" class="status-box status-loading">
                    <div class="spinner-grow" role="status"></div>
                    <span>Mengunci Koordinat GPS Anda...</span>
                </div>

                <button type="submit" id="btnSubmit" class="btn-checkin" disabled>
                    <i class="fas fa-fingerprint me-2"></i>Kirim Kehadiran Apel
                </button>
            </form>

            <div id="alertMsg" class="alert" style="display:none;" role="alert"></div>
        </div>

        <div class="footer-text">
            &copy; <?= date('Y') ?> Pemerintah Kabupaten Polewali Mandar
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const latInput = $('#latitude');
            const lngInput = $('#longitude');
            const btnSubmit = $('#btnSubmit');
            const locStatus = $('#locationStatus');
            const alertMsg = $('#alertMsg');

            // GPS Logic
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    latInput.val(position.coords.latitude);
                    lngInput.val(position.coords.longitude);

                    locStatus.removeClass('status-loading status-error').addClass('status-success');
                    locStatus.html('<i class="fas fa-check-circle fs-5"></i><span>Lokasi GPS Terkunci. Siap Absen!</span>');
                    btnSubmit.prop('disabled', false);

                }, function(error) {
                    locStatus.removeClass('status-loading status-success').addClass('status-error');
                    let msg = 'Gagal membaca GPS. Pastikan Izin Lokasi aktif.';
                    if (error.code === 1) msg = 'Akses Lokasi GPS Anda Ditolak!';
                    locStatus.html('<i class="fas fa-exclamation-triangle fs-5"></i><span>' + msg + '</span>');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                locStatus.removeClass('status-loading').addClass('status-error');
                locStatus.html('<i class="fas fa-times-circle fs-5"></i><span>Browser tidak mendukung GPS.</span>');
            }

            // Submit Handler
            $('#formCheckin').on('submit', function(e) {
                e.preventDefault();

                if (!latInput.val() || !lngInput.val()) {
                    alertMsg.removeClass('alert-success').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-circle me-2"></i>Koordinat GPS belum ditemukan. Harap tunggu.')
                        .slideDown();
                    return;
                }

                const originalBtnText = btnSubmit.html();
                btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Memproses...');
                alertMsg.slideUp();

                $.ajax({
                    url: '<?= base_url("apelweb/process_checkin/" . $apel['id_apel']) ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            alertMsg.removeClass('alert-danger').addClass('alert-success')
                                .html('<i class="fas fa-check-circle me-2"></i>' + res.message)
                                .slideDown();

                            btnSubmit.html('<i class="fas fa-check me-2"></i>Berhasil Check-in')
                                .css('background', 'linear-gradient(135deg, #059669 0%, #10b981 100%)');

                            $('#formCheckin')[0].reset();

                            // Prevent double submit safely
                            setTimeout(() => {
                                btnSubmit.html(originalBtnText).css('background', '').prop('disabled', false);
                                alertMsg.slideUp();
                            }, 5000);

                        } else {
                            alertMsg.removeClass('alert-success').addClass('alert-danger')
                                .html('<i class="fas fa-exclamation-circle me-2"></i>' + res.message)
                                .slideDown();
                            btnSubmit.prop('disabled', false).html(originalBtnText);
                        }
                    },
                    error: function(xhr) {
                        let errMsg = 'Terjadi kesalahan jaringan/server. Silakan coba lagi.';
                        if (xhr.status === 403) errMsg = 'Sesi / Keamanan kadaluwarsa (CSRF). Silakan refresh halaman.';

                        alertMsg.removeClass('alert-success').addClass('alert-danger')
                            .html('<i class="fas fa-exclamation-circle me-2"></i>' + errMsg)
                            .slideDown();
                        btnSubmit.prop('disabled', false).html(originalBtnText);
                    }
                });
            });
        });
    </script>
</body>

</html>