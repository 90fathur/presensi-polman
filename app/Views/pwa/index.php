<?php

/**
 * ============================================================================
 * PROYEK     : SISTEM PRESENSI BIOMETRIK PWA (PREMIUM EDITION)
 * INSTANSI   : PEMKAB POLEWALI MANDAR
 * DEVELOPER  : FATHURRAHMAN MUHIDDIN
 * FILE       : index.php (Main Dashboard)
 * ============================================================================
 */

function getStatusAbsen($jam, $status_db = '')
{
    if (!$jam || $jam == '00:00:00' || $jam == null) {
        if ($status_db && $status_db !== 'Hadir' && $status_db !== 'Pending') {
            return ['teks' => $status_db, 'class' => 'status-badge state-info'];
        }
        return ['teks'  => '-', 'class' => 'status-badge state-empty'];
    }

    if ($status_db == 'Sangat Baik / Istimewa') {
        return ['teks' => 'Sangat Baik', 'class' => 'status-badge state-success'];
    } elseif ($status_db == 'Baik') {
        return ['teks' => 'Baik', 'class' => 'status-badge state-info'];
    } elseif ($status_db == 'Cukup Baik') {
        return ['teks' => 'Cukup Baik', 'class' => 'status-badge state-warning'];
    } elseif ($status_db == 'Terlambat') {
        return ['teks' => 'Terlambat', 'class' => 'status-badge state-danger'];
    } elseif ($status_db == 'Pembinaan') {
        return ['teks' => 'Pembinaan', 'class' => 'status-badge state-danger'];
    } elseif ($status_db == 'Teguran') {
        return ['teks' => 'Teguran', 'class' => 'status-badge state-dark'];
    } elseif ($status_db == 'Hadir (Apel)' || strpos($status_db, 'Apel') !== false) {
        return ['teks' => 'Hadir (Apel)', 'class' => 'status-badge state-primary'];
    } else {
        return ['teks' => $status_db ?: 'Hadir', 'class' => 'status-badge state-primary'];
    }
}

$jam_sekarang = date('H:i:s');

$tampil_jam_masuk = '--:--';
$tampil_jam_pulang = '--:--';
$nama_hari_tampil = '';

if (!empty($jadwal_hari_ini)) {
    $nama_hari_tampil = $jadwal_hari_ini['hari'] ?? '';
    if (isset($jadwal_hari_ini['jam_masuk']) && $jadwal_hari_ini['jam_masuk'] == 'LEPAS') {
        $tampil_jam_masuk = 'LEPAS';
        $tampil_jam_pulang = 'JAGA';
    } else {
        $tampil_jam_masuk = isset($jadwal_hari_ini['jam_masuk']) ? date('H:i', strtotime(str_replace('.', ':', $jadwal_hari_ini['jam_masuk']))) : '--:--';
        $tampil_jam_pulang = isset($jadwal_hari_ini['jam_pulang']) ? date('H:i', strtotime(str_replace('.', ':', $jadwal_hari_ini['jam_pulang']))) : '--:--';
    }
}

// Cek apakah nomor HP kosong
$hp_kosong = empty($pegawai['nomor_hp']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Presensi ASN - Kab. Polewali Mandar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#064E3B">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/logo_180.png') ?>">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <style>
        :root {
            --primary-dark: #022C22;
            --primary: #059669;
            --primary-light: #10B981;
            --accent: #F59E0B;
            --accent-glow: rgba(245, 158, 11, 0.4);
            --bg-app: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --text-xs: #94A3B8;
            --shadow-soft: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            --shadow-hard: 0 20px 40px -15px rgba(5, 150, 105, 0.2);
            --shadow-inner: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            --radius-box: 28px;
            --radius-btn: 16px;
        }

        * {
            box-sizing: border-box;
            outline: none !important;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-app);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            padding-bottom: calc(90px + env(safe-area-inset-bottom));
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .font-num {
            font-family: 'Outfit', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        /* HERO SECTION */
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 320px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border-radius: 0 0 45px 45px;
            z-index: 0;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3);
        }

        .hero-bg::after {
            content: '';
            position: absolute;
            right: -20%;
            top: -10%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            left: -10%;
            bottom: -20%;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            border: 2px dashed rgba(255, 255, 255, 0.05);
        }

        .header-content {
            position: relative;
            z-index: 5;
            padding: max(25px, 4vh) 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .user-profile .greeting {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .user-profile .name {
            font-size: clamp(1.2rem, 5vw, 1.5rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .user-profile .nip-opd {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }

        .time-pill {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 8px 14px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* CENTRAL HUB */
        .main-container {
            position: relative;
            z-index: 10;
            margin-top: 3vh;
            padding: 0 5vw;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .hub-card {
            background: var(--card-bg);
            border-radius: var(--radius-box);
            box-shadow: var(--shadow-soft);
            padding: 20px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .hub-schedule {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-app);
            border-radius: var(--radius-btn);
            padding: 12px 16px;
            margin-bottom: 25px;
            border: 1px dashed rgba(0, 0, 0, 0.05);
        }

        .hub-schedule .day-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hub-schedule .day-value {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .hub-schedule .time-col {
            text-align: right;
        }

        .hub-schedule .time-col div {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-muted);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 5px;
        }

        .hub-schedule .time-col span {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        /* SCANNER */
        .hub-scanner-area {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 10px 0 30px;
        }

        .btn-biometric {
            width: clamp(130px, 35vw, 160px);
            height: clamp(130px, 35vw, 160px);
            border-radius: 50%;
            border: none;
            cursor: pointer;
            position: relative;
            z-index: 2;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(255, 255, 255, 0.5);
            padding: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
        }

        .btn-biometric:active {
            transform: scale(0.92);
        }

        .btn-biometric .core {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            box-shadow: var(--shadow-inner);
        }

        .core-gold {
            background: linear-gradient(145deg, var(--accent), #D97706);
        }

        .core-green {
            background: linear-gradient(145deg, var(--primary-light), var(--primary));
        }

        .core-disabled {
            background: linear-gradient(145deg, #CBD5E1, #94A3B8);
            color: #475569;
        }

        .btn-biometric i {
            font-size: clamp(2.5rem, 8vw, 3rem);
            margin-bottom: 5px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .btn-biometric span {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .radar-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100%;
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }

        .core-gold~.radar-ring {
            animation: ping-gold 2.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        .core-green~.radar-ring {
            animation: ping-green 2.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes ping-gold {

            75%,
            100% {
                transform: translate(-50%, -50%) scale(1.6);
                opacity: 0;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.5);
            }
        }

        @keyframes ping-green {

            75%,
            100% {
                transform: translate(-50%, -50%) scale(1.6);
                opacity: 0;
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.5);
            }
        }

        /* LOG */
        .hub-log {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .hub-log::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 10%;
            right: 10%;
            height: 1px;
            background: dashed 1px #CBD5E1;
            z-index: 0;
        }

        .log-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            background: var(--card-bg);
            padding: 0 10px;
        }

        .log-node .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-bottom: 8px;
            border: 3px solid var(--card-bg);
        }

        .log-node .dot.done {
            background: var(--primary);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        .log-node .dot.pending {
            background: #E2E8F0;
        }

        .log-node .label {
            font-size: 0.6rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .log-node .time {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .log-node .time.empty {
            color: var(--text-xs);
        }

        .status-badge {
            font-size: 0.55rem;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }

        .state-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }

        .state-info {
            background: rgba(59, 130, 246, 0.1);
            color: #2563EB;
        }

        .state-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #D97706;
        }

        .state-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
        }

        .state-primary {
            background: var(--primary);
            color: #fff;
        }

        .state-dark {
            background: var(--text-dark);
            color: #fff;
        }

        .state-empty {
            background: transparent;
            color: transparent;
            padding: 0;
        }

        /* ALERT CARDS */
        .alert-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            border-radius: var(--radius-btn);
            padding: 14px 18px;
            box-shadow: var(--shadow-soft);
            position: relative;
            overflow: hidden;
        }

        .alert-card::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
        }

        .alert-apel::after {
            background: var(--primary);
        }

        .alert-izin::after {
            background: var(--accent);
        }

        .alert-card .header {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .alert-apel .header {
            color: var(--primary);
        }

        .alert-izin .header {
            color: var(--accent);
        }

        .alert-card .title {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .alert-card .desc {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* QUICK SERVICES */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin: 10px 5px 5px;
        }

        .section-header h6 {
            font-size: 0.9rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-dark);
        }

        .btn-text-action {
            background: transparent;
            border: none;
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--primary);
            padding: 0;
            cursor: pointer;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .service-box {
            background: var(--card-bg);
            border-radius: var(--radius-btn);
            padding: 16px 5px;
            text-align: center;
            box-shadow: var(--shadow-soft);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .service-box:active {
            transform: scale(0.95);
        }

        .service-box .icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
        }

        .service-box.sakit .icon-wrap {
            background: linear-gradient(135deg, #FCA5A5, #EF4444);
            color: #fff;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }

        .service-box.izin .icon-wrap {
            background: linear-gradient(135deg, #93C5FD, #3B82F6);
            color: #fff;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

        .service-box.dl .icon-wrap {
            background: linear-gradient(135deg, #FCD34D, #F59E0B);
            color: #fff;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
        }

        .service-box.cito .icon-wrap {
            background: linear-gradient(135deg, #A855F7, #7E22CE);
            color: #fff;
            box-shadow: 0 4px 10px rgba(168, 85, 247, 0.3);
        }

        .service-box .txt {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        /* HISTORY */
        .history-list {
            background: var(--card-bg);
            border-radius: var(--radius-box);
            padding: 15px;
            box-shadow: var(--shadow-soft);
            max-height: clamp(200px, 35vh, 400px);
            overflow-y: auto;
        }

        .history-list::-webkit-scrollbar {
            display: none;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            position: relative;
            padding-bottom: 15px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 19px;
            top: 35px;
            bottom: -5px;
            width: 2px;
            background: var(--bg-app);
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .tl-date {
            min-width: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
            background: var(--card-bg);
            padding-top: 5px;
        }

        .tl-date .d {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }

        .tl-date .m {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .tl-content {
            flex: 1;
            background: var(--bg-app);
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tl-content .time-range {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .tl-content .time-range i {
            font-size: 0.7rem;
            color: var(--primary);
        }

        /* NAV ISLAND */
        .island-nav {
            position: fixed;
            bottom: calc(20px + env(safe-area-inset-bottom));
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 400px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 10px 5px;
            border-radius: 100px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .island-btn {
            text-decoration: none;
            color: var(--text-xs);
            font-size: 1.2rem;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
            position: relative;
        }

        .island-btn.active {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .island-btn.danger {
            color: #FCA5A5;
        }

        .island-btn:active {
            transform: scale(0.9);
        }

        /* CAMERA & UI */
        #camera-verification-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: #000;
            z-index: 99999;
        }

        #camera-stream {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            opacity: 0.85;
        }

        .camera-mask-circle {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(75vw, 320px);
            height: min(75vw, 320px);
            border: 3px solid var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.85);
            pointer-events: none;
            animation: pulse-cam 2s infinite;
        }

        @keyframes pulse-cam {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4), 0 0 0 2000px rgba(0, 0, 0, 0.85);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(245, 158, 11, 0), 0 0 0 2000px rgba(0, 0, 0, 0.85);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0), 0 0 0 2000px rgba(0, 0, 0, 0.85);
            }
        }

        .camera-ui-panel {
            position: absolute;
            bottom: max(50px, 8vh);
            left: 20px;
            right: 20px;
            text-align: center;
        }

        .camera-ui-panel .alert {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            color: var(--text-dark);
            font-weight: 800;
            font-size: 0.9rem;
            padding: 16px;
            margin-bottom: 15px;
        }

        /* MODALS */
        .modal-content {
            border-radius: var(--radius-box);
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px 24px 10px;
            border-bottom: none;
        }

        .modal-header h6 {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .modal-body {
            padding: 10px 24px 24px;
        }

        .form-control,
        .form-select {
            border-radius: var(--radius-btn);
            padding: 12px 16px;
            background: var(--bg-app);
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .form-control:focus,
        .form-select:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            font-size: 0.9rem;
            padding: 14px;
            font-weight: 800;
            border-radius: var(--radius-btn);
            transition: background 0.3s;
        }

        .btn-primary:active {
            background: var(--primary-dark);
            transform: scale(0.98);
        }

        .form-label-mini {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* PULL TO REFRESH */
        #ptr-indicator {
            position: fixed;
            top: -60px;
            left: 0;
            width: 100%;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            transition: transform 0.2s ease;
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .ptr-icon {
            width: 35px;
            height: 35px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .ptr-refreshing .ptr-icon {
            animation: rotate-ptr 0.8s linear infinite;
        }

        @keyframes rotate-ptr {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="ptr-indicator">
        <div class="ptr-icon"><i class="fas fa-sync-alt"></i></div>
    </div>

    <div id="camera-verification-overlay">
        <video id="camera-stream" autoplay muted playsinline></video>
        <div class="camera-mask-circle"></div>
        <div class="camera-ui-panel">
            <div id="ai-status-label" class="alert border-0 shadow">
                <i class="fas fa-circle-notch fa-spin me-2 text-primary"></i> Menyiapkan Sensor AI...
            </div>
            <button class="btn btn-light text-danger w-100 rounded-pill fw-bold shadow" style="font-size: 0.85rem; padding: 14px;" onclick="tutupKameraTanpaReload();">BATALKAN PROSES</button>
        </div>
    </div>

    <div class="hero-bg"></div>

    <div class="header-content">
        <div class="user-profile">
            <div class="greeting">
                <?php
                $hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $bulan_indo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                echo $hari_indo[date('w')] . ', ' . date('d') . ' ' . $bulan_indo[date('n')] . ' ' . date('Y');
                ?>
            </div>
            <div class="name"><?= $pegawai['nama_lengkap'] ?></div>
            <div class="nip-opd">NIP. <?= $pegawai['nip'] ?> &bull; <?= $pegawai['nama_opd'] ?? '-' ?></div>
        </div>
        <div class="time-pill" onclick="handleUIRefresh(this)">
            <i class="far fa-clock text-warning"></i>
            <span id="pwa-digital-clock" class="font-num">00:00:00</span>
        </div>
    </div>

    <main class="main-container">

        <div class="alert alert-light border shadow-sm mb-0 p-3 d-flex align-items-center justify-content-between rounded-4">
            <div>
                <div class="fw-bold text-dark" style="font-size: 0.8rem;"><i class="fas fa-map-marker-alt text-danger me-1"></i> Titik Lokasi Anda</div>
                <div id="gps-status-text" class="text-muted" style="font-size: 0.65rem;">Mendeteksi satelit...</div>
            </div>
            <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold shadow-sm" onclick="forceRefreshGPS()" style="font-size: 0.7rem;">
                <i class="fas fa-sync-alt me-1"></i> Perbarui
            </button>
        </div>

        <?php if ($terakhir && isset($terakhir['status_verifikasi']) && $terakhir['status_verifikasi'] == 'Ditangguhkan'): ?>
            <div class="hub-card mt-3 text-center py-5" style="border: 2px solid #EF4444; background: #FEF2F2;">
                <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 4rem;"></i>
                <h5 class="fw-bold text-danger mb-2">PRESENSI DITANGGUHKAN</h5>
                <p class="text-muted small px-3">Absen Anda hari ini telah ditangguhkan oleh Admin dengan alasan:</p>
                <div class="bg-white border border-danger rounded-3 p-3 mx-4 mb-3 shadow-sm">
                    <span class="fw-bold text-dark text-center">"<?= htmlspecialchars($terakhir['alasan_tangguh']) ?>"</span>
                </div>
                <div class="bg-danger text-white px-4 py-2 mx-4 rounded-pill shadow-sm" style="font-size: 0.75rem; font-weight: 700;">
                    <i class="fas fa-info-circle me-1"></i> Harap segera menghadap pimpinan.
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size: 0.65rem;">Anda tidak dapat melakukan aktivitas presensi apapun sampai status dipulihkan.</p>
            </div>
        <?php else: ?>
            <div class="hub-card mt-3">
                <div class="hub-schedule">
                    <div>
                        <div class="day-label">Jadwal Anda</div>
                        <div class="day-value"><?= $nama_hari_tampil ?: $nama_hari_indo ?></div>
                    </div>
                    <div class="time-col">
                        <div>Masuk <i class="fas fa-sign-in-alt ms-1 text-success"></i></div>
                        <span class="font-num"><?= $tampil_jam_masuk ?></span>
                    </div>
                    <div class="time-col">
                        <div>Pulang <i class="fas fa-sign-out-alt ms-1 text-danger"></i></div>
                        <span class="font-num"><?= $tampil_jam_pulang ?></span>
                    </div>
                </div>

                <div class="hub-scanner-area">
                    <?php
                    $is_night_lock = ($jam_sekarang >= '00:00:00' && $jam_sekarang < '07:00:00');

                    // [PENGAMAN VS CODE]: Cek eksistensi array sebelum dibaca
                    $jam_limit_target = (!empty($tugas_apel) && isset($tugas_apel['jam_akhir'])) ? $tugas_apel['jam_akhir'] : '08:00:00';
                    $is_apel_currently_valid = (!empty($tugas_apel) && $jam_sekarang <= $jam_limit_target);
                    $sudah_absen_apel = (!empty($tugas_apel) && !empty($tugas_apel['sudah_absen_apel'])) || ($terakhir && strpos($terakhir['status_masuk'] ?? '', 'Apel') !== false && !empty($terakhir['jam_masuk_aktual']));
                    ?>

                    <?php if ($libur): ?>
                        <button class="btn-biometric" onclick="Swal.fire('Hari Libur Nasional','<?= $libur['keterangan'] ?>','info')">
                            <div class="core core-disabled"><i class="fas fa-calendar-times"></i><span>LIBUR</span></div>
                        </button>
                    <?php elseif ($is_night_lock): ?>
                        <button class="btn-biometric" onclick="Swal.fire('Sistem Tertutup','Presensi dimulai pukul 07:00 WITA.','warning')">
                            <div class="core core-disabled"><i class="fas fa-moon"></i><span>TUTUP</span></div>
                        </button>
                    <?php elseif ($is_apel_currently_valid && !$sudah_absen_apel): ?>
                        <button class="btn-biometric" id="btn-trigger-attendance-apel">
                            <div class="core core-green"><i class="fas fa-user-shield"></i><span>APEL</span></div>
                            <div class="radar-ring"></div>
                        </button>
                    <?php else: ?>
                        <button class="btn-biometric" id="btn-trigger-attendance">
                            <div class="core core-gold"><i class="fas fa-fingerprint"></i><span>SCAN</span></div>
                            <div class="radar-ring"></div>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="hub-log">
                    <?php
                    $dt_m = ($terakhir && !empty($terakhir['jam_masuk_aktual']) && $terakhir['jam_masuk_aktual'] !== '00:00:00') ? date('H:i', strtotime($terakhir['jam_masuk_aktual'])) : null;
                    $st_m = ($dt_m) ? getStatusAbsen($terakhir['jam_masuk_aktual'], $terakhir['status_masuk'], $terakhir['kategori'] ?? '') : null;
                    $dt_s = ($terakhir && !empty($terakhir['jam_siang']) && $terakhir['jam_siang'] !== '00:00:00') ? date('H:i', strtotime($terakhir['jam_siang'])) : null;
                    $dt_p = ($terakhir && !empty($terakhir['jam_pulang_aktual']) && $terakhir['jam_pulang_aktual'] !== '00:00:00') ? date('H:i', strtotime($terakhir['jam_pulang_aktual'])) : null;

                    $st_p_label = '';
                    $st_p_class = 'state-empty';
                    if ($dt_p) {
                        $db_st_p = strtolower(trim($terakhir['status_pulang'] ?? ''));
                        if ($db_st_p == 'cepat pulang') {
                            $st_p_label = 'Cepat Pulang';
                            $st_p_class = 'state-danger';
                        } else {
                            $st_p_label = 'Selesai';
                            $st_p_class = 'state-info';
                        }
                    }
                    ?>

                    <div class="log-node">
                        <div class="dot <?= $dt_m ? 'done' : 'pending' ?>"></div>
                        <div class="label">Masuk</div>
                        <div class="time font-num <?= $dt_m ? '' : 'empty' ?>"><?= $dt_m ?? '--:--' ?></div>
                        <div class="<?= $dt_m ? $st_m['class'] : 'status-badge state-empty' ?>"><?= $dt_m ? $st_m['teks'] : '' ?></div>
                    </div>

                    <div class="log-node">
                        <div class="dot <?= $dt_s ? 'done' : 'pending' ?>"></div>
                        <div class="label">Siang</div>
                        <div class="time font-num <?= $dt_s ? '' : 'empty' ?>"><?= $dt_s ?? '--:--' ?></div>
                        <div class="status-badge <?= $dt_s ? 'state-warning' : 'state-empty' ?>"><?= $dt_s ? 'Ishoma' : '' ?></div>
                    </div>

                    <div class="log-node">
                        <div class="dot <?= $dt_p ? 'done' : 'pending' ?>"></div>
                        <div class="label">Pulang</div>
                        <div class="time font-num <?= $dt_p ? '' : 'empty' ?>"><?= $dt_p ?? '--:--' ?></div>
                        <div class="status-badge <?= $st_p_class ?>"><?= $dt_p ? $st_p_label : '' ?></div>
                    </div>
                </div>
            </div>
            </div>
        <?php endif; ?>

        <?php
        $libur_7_hari = [];
        if (!empty($libur_mendatang)) {
            $tgl_batas = date('Y-m-d', strtotime('+7 days'));
            foreach ($libur_mendatang as $lm) {
                if ($lm['tanggal'] <= $tgl_batas) {
                    $libur_7_hari[] = $lm;
                }
            }
        }
        ?>

        <?php if (!empty($libur_7_hari)): ?>
            <div class="mb-4 px-1">
                <?php foreach ($libur_7_hari as $lm): ?>
                    <?php
                    $tgl_libur = new DateTime($lm['tanggal']);
                    $tgl_skrg = new DateTime(date('Y-m-d'));
                    $diff = $tgl_skrg->diff($tgl_libur)->days;
                    $waktu_teks = ($diff == 1) ? 'BESOK!' : "$diff HARI LAGI";
                    ?>
                    <div class="d-flex align-items-center bg-danger bg-opacity-10 rounded-4 p-2 mb-2 border border-danger border-opacity-25 shadow-sm">
                        <div class="text-danger fs-3 px-3"><i class="fas fa-umbrella-beach"></i></div>
                        <div>
                            <div style="font-size: 0.65rem; font-weight: 800; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px;">Persiapan Libur &bull; <span class="badge bg-danger rounded-pill px-2 py-0"><?= $waktu_teks ?></span></div>
                            <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-dark); margin-top: 2px;"><?= $lm['keterangan'] ?></div>
                            <div style="font-size: 0.7rem; font-weight: 600; color: var(--text-muted);"><i class="far fa-calendar-alt"></i>
                                <?php
                                $bulan_indo_singkat = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                                echo date('d', strtotime($lm['tanggal'])) . ' ' . $bulan_indo_singkat[date('n', strtotime($lm['tanggal']))] . ' ' . date('Y', strtotime($lm['tanggal']));
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        $jam_hadir_apel = (!empty($tugas_apel['sudah_absen_apel'])) ? $tugas_apel['sudah_absen_apel'] : (($terakhir && strpos($terakhir['status_masuk'] ?? '', 'Apel') !== false && !empty($terakhir['jam_masuk_aktual'])) ? $terakhir['jam_masuk_aktual'] : null);
        ?>
        <?php if (!empty($tugas_apel) && ($is_apel_currently_valid || !empty($jam_hadir_apel))): ?>
            <div class="alert-card alert-apel">
                <div>
                    <div class="header">Wajib Apel Pagi</div>
                    <div class="title"><?= $tugas_apel['nama_apel'] ?></div>
                    <?php if (!empty($jam_hadir_apel)): ?>
                        <div class="desc text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Hadir Apel: <?= substr($jam_hadir_apel, 0, 5) ?> WITA (Tercatat Hadir Masuk)</div>
                    <?php else: ?>
                        <div class="desc"><i class="far fa-clock me-1"></i> Batas: <?= date('H:i', strtotime($jam_limit_target)) ?> WITA</div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($jam_hadir_apel)): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-bold"><i class="fas fa-check me-1"></i> Selesai</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($pengajuan_aktif): ?>
            <div class="alert-card alert-izin">
                <div>
                    <div class="header">Izin / Sakit Aktif</div>
                    <div class="title"><?= $pengajuan_aktif['kategori'] ?></div>
                    <div class="desc"><i class="far fa-calendar"></i> S/d <?= date('d M', strtotime($pengajuan_aktif['tanggal_selesai'])) ?></div>
                </div>
                <div>
                    <?php if ($pengajuan_aktif['status_approval'] == 'Pending'): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                    <?php elseif ($pengajuan_aktif['status_approval'] == 'Disetujui'): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Disetujui</span>
                    <?php else: ?>
                        <span class="badge bg-danger"><i class="fas fa-times"></i> Ditolak</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <div class="section-header">
                <h6>Layanan Presensi</h6>
            </div>

            <?php if (isset($pegawai['is_cito']) && $pegawai['is_cito'] == 1 && isset($is_hari_ini_cito) && $is_hari_ini_cito): ?>
                <div class="alert alert-warning mb-2 border-0 shadow-sm p-3 d-flex align-items-center justify-content-between rounded-4" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.8rem;"><i class="fas fa-ambulance text-danger me-1"></i> Mode Darurat (Cito)</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Tap tombol di samping saat mulai/selesai.</div>
                    </div>
                    <button class="btn btn-sm <?= $cito_aktif ? 'btn-danger' : 'btn-warning' ?> rounded-pill fw-bold shadow-sm" onclick="handleCito(<?= $cito_aktif ? 'true' : 'false' ?>)">
                        <?= $cito_aktif ? '<i class="fas fa-stop me-1"></i> Akhiri' : '<i class="fas fa-play me-1"></i> Mulai' ?>
                    </button>
                </div>

                <?php
                $db_cito = \Config\Database::connect();
                $riwayat_cito = $db_cito->table('presensi_darurat')
                    ->where('id_pegawai', $pegawai['id_pegawai'])
                    ->where('MONTH(tanggal)', $bulan_aktif ?? date('m'))
                    ->where('YEAR(tanggal)', $tahun_aktif ?? date('Y'))
                    ->orderBy('tanggal', 'DESC')
                    ->orderBy('jam_mulai', 'DESC')
                    ->get()->getResultArray();
                ?>

                <?php if (!empty($riwayat_cito)): ?>
                    <div class="mb-4 mt-3">
                        <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                            <i class="fas fa-history me-1"></i> Riwayat Cito Bulan Ini
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($riwayat_cito as $rc): ?>
                                <div class="bg-white p-3 rounded-4 shadow-sm border position-relative overflow-hidden">
                                    <div style="position: absolute; left:0; top:0; bottom:0; width: 5px; background: <?= $rc['status_approval'] == 'Disetujui' ? 'var(--primary)' : 'var(--accent)' ?>;"></div>
                                    <div class="ms-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark" style="font-size: 0.8rem;"><i class="far fa-calendar-alt text-muted me-1"></i> <?= date('d/m/Y', strtotime($rc['tanggal'])) ?></span>
                                            <?php if ($rc['status_approval'] == 'Disetujui'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.65rem;"><i class="fas fa-check"></i> Disetujui</span>
                                            <?php elseif ($rc['status_approval'] == 'Ditolak'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.65rem;"><i class="fas fa-times"></i> Ditolak</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size: 0.65rem;"><i class="fas fa-clock"></i> Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light">
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">Mulai: <b class="text-dark font-num" style="font-size: 0.85rem;"><?= date('H:i', strtotime($rc['jam_mulai'])) ?></b></span>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">Selesai:
                                                <?php if (!empty($rc['jam_selesai']) && $rc['jam_selesai'] != '00:00:00'): ?>
                                                    <b class="text-dark font-num" style="font-size: 0.85rem;"><?= date('H:i', strtotime($rc['jam_selesai'])) ?></b>
                                                <?php else: ?>
                                                    <b class="text-warning" style="font-size: 0.8rem;"><i class="fas fa-spinner fa-spin"></i> Berjalan</b>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-dark); font-weight: 600; display:flex; align-items:flex-start; gap: 8px;">
                                            <i class="fas fa-comment-medical text-danger mt-1"></i>
                                            <span style="flex: 1;"><?= htmlspecialchars($rc['keterangan']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="services-grid">
                <div class="service-box sakit" onclick="handleFormSpecial('Sakit')">
                    <div class="icon-wrap"><i class="fas fa-briefcase-medical"></i></div><span class="txt">Izin Sakit</span>
                </div>
                <div class="service-box izin" onclick="handleFormSpecial('Izin')">
                    <div class="icon-wrap"><i class="fas fa-envelope-open-text"></i></div><span class="txt">Izin Absen</span>
                </div>
                <div class="service-box dl" onclick="handleFormSpecial('Tugas Luar')">
                    <div class="icon-wrap"><i class="fas fa-plane-departure"></i></div><span class="txt">Tugas Luar</span>
                </div>
            </div>
        </div>

        <div>
            <div class="section-header">
                <h6>Riwayat Bulan Ini</h6>
                <button class="btn-text-action" onclick="handleOpenFilterModal()"><i class="fas fa-filter"></i> Ganti Bulan</button>
            </div>

            <div class="history-list">
                <?php if (empty($riwayat)): ?>
                    <div class="text-center py-4 opacity-50"><i class="fas fa-history mb-2 fs-3"></i><span class="d-block" style="font-size: 0.8rem; font-weight: 600;">Belum ada catatan presensi</span></div>
                <?php else: ?>
                    <?php foreach ($riwayat as $row): ?>
                        <?php
                        $status_res = ($row['kategori'] == 'Hadir') ? getStatusAbsen($row['jam_masuk_aktual'], $row['status_masuk']) : ['teks' => $row['kategori'], 'class' => 'status-badge state-info'];
                        ?>
                        <div class="timeline-item">
                            <div class="tl-date">
                                <span class="d font-num"><?= date('d', strtotime($row['tanggal'])) ?></span>
                                <span class="m"><?= date('M', strtotime($row['tanggal'])) ?></span>
                            </div>
                            <div class="tl-content shadow-sm">
                                <div>
                                    <div class="time-range font-num">
                                        <?= ($row['jam_masuk_aktual']) ? date('H:i', strtotime($row['jam_masuk_aktual'])) : '--:--' ?>
                                        <i class="fas fa-arrow-right"></i>
                                        <?= ($row['jam_pulang_aktual']) ? date('H:i', strtotime($row['jam_pulang_aktual'])) : '--:--' ?>
                                    </div>
                                    <span class="<?= $status_res['class'] ?>"><?= $status_res['teks'] ?></span>
                                </div>
                                <?php if ($row['kategori'] != 'Hadir'): ?><i class="fas fa-file-alt text-muted opacity-50"></i><?php else: ?><i class="fas fa-fingerprint text-muted opacity-50"></i><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <nav class="island-nav">
        <a href="<?= base_url('pwa') ?>" class="island-btn active"><i class="fas fa-home"></i></a>
        <a href="javascript:void(0)" onclick="handleOpenFilterModal()" class="island-btn"><i class="fas fa-calendar-alt"></i></a>
        <a href="javascript:void(0)" onclick="handleOpenPassModal()" class="island-btn"><i class="fas fa-user-cog"></i></a>
        <a href="<?= base_url('logoutpwa') ?>" class="island-btn danger"><i class="fas fa-power-off"></i></a>
    </nav>

    <div class="modal fade" id="modal-special-permit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mx-3">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="mb-0" id="permit-label-header">Form Pengajuan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-submit-special-permit" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="kategori" id="permit-hidden-kategori">
                        <div class="mb-3">
                            <label class="form-label-mini">Pilih Jenis Spesifik</label>
                            <select name="sub_kategori" id="permit-sub-kategori" class="form-select" onchange="togglePermitLogic()" required></select>
                        </div>
                        <div class="mb-3" id="permit-wrapper-slot" style="display: none;">
                            <label class="form-label-mini">Waktu Yang Diajukan</label>
                            <select name="slot_waktu" class="form-select">
                                <option value="Penuh">Satu Hari Penuh</option>
                                <option value="Pagi">Sesi Awal (Absen Masuk)</option>
                                <option value="Siang">Sesi Siang (Absen Ishoma)</option>
                                <option value="Sore">Sesi Akhir (Absen Pulang)</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label-mini">Dari Tanggal</label><input type="date" name="tgl_mulai" class="form-control font-num" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-6" id="permit-wrapper-tgl-selesai"><label class="form-label-mini">Sampai Tanggal</label><input type="date" name="tgl_selesai" class="form-control font-num" value="<?= date('Y-m-d') ?>"></div>
                        </div>
                        <div class="mb-3"><label class="form-label-mini">Alasan Lengkap</label><textarea name="keterangan" class="form-control" rows="2" placeholder="Tulis alasan..." required></textarea></div>
                        <div class="mb-4"><label class="form-label-mini">Lampirkan Bukti (Opsional, JPG/PDF)</label><input type="file" name="file_bukti" class="form-control" accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"></div>
                        <button type="submit" class="btn btn-primary w-100 text-white shadow-sm">KIRIM PENGAJUAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-pwa-password" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mx-3">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="mb-0">Profil & Keamanan</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-pwa-update-pass">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label-mini">Nomor HP / WhatsApp Aktif</label><input type="number" name="nomor_hp" class="form-control font-num" value="<?= $pegawai['nomor_hp'] ?? '' ?>" placeholder="Contoh: 081234..." required></div>
                        <hr class="text-muted opacity-25">
                        <p class="text-muted mb-2" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">Ganti Sandi (Kosongkan jika tidak diubah)</p>
                        <div class="mb-3"><label class="form-label-mini">Sandi Saat Ini</label><input type="password" name="lama" class="form-control" placeholder="••••••••"></div>
                        <div class="mb-3"><label class="form-label-mini">Sandi Baru</label><input type="password" name="baru" class="form-control" placeholder="Min. 6 karakter" minlength="6"></div>
                        <div class="mb-4"><label class="form-label-mini">Konfirmasi Sandi Baru</label><input type="password" name="konfirmasi" class="form-control" placeholder="Ulangi sandi baru"></div>
                        <button type="submit" class="btn btn-primary w-100 text-white shadow-sm">SIMPAN PERUBAHAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-pwa-filter" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mx-3">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="mb-0">Filter Riwayat Data</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('pwa') ?>" method="GET">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label-mini">Pilih Bulan</label>
                            <select name="bulan" class="form-select" required>
                                <?php foreach ($bulan_indo as $num => $txt): if ($num == 0) continue; ?>
                                    <option value="<?= $num ?>" <?= (date('n') == $num) ? 'selected' : '' ?>><?= $txt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-mini">Pilih Tahun</label>
                            <select name="tahun" class="form-select" required>
                                <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 text-white shadow-sm">TAMPILKAN DATA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($hp_kosong): ?>
        <div class="modal fade" id="modal-force-phone" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered mx-3">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning bg-opacity-10 border-0 pb-0">
                        <h6 class="mb-0 text-warning fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Nomor WA Belum Diisi</h6>
                    </div>
                    <form id="form-force-phone">
                        <div class="modal-body pt-3">
                            <p class="text-muted" style="font-size: 0.8rem;">Mohon lengkapi nomor WhatsApp Anda untuk keperluan keamanan akun dan informasi kepegawaian.</p>
                            <div class="mb-4"><label class="form-label-mini">Nomor HP / WhatsApp Aktif</label><input type="number" name="nomor_hp" class="form-control form-control-lg font-num" placeholder="Contoh: 0812..." required></div>
                            <button type="submit" class="btn btn-warning w-100 text-dark fw-bold shadow-sm">SIMPAN NOMOR WA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Deklarasi Variabel Global dari PHP ke JS (Agar VS Code tidak error merah)
        const GLOBAL_NIP = "<?= $pegawai['nip'] ?>";

        document.addEventListener("DOMContentLoaded", function() {
            // Beri jeda 2 detik supaya WebView benar-benar siap
            setTimeout(function() {
                if (typeof Android !== 'undefined') {
                    if (GLOBAL_NIP && GLOBAL_NIP !== "") {
                        // Panggil fungsi Android
                        Android.setNipToNotif(GLOBAL_NIP);
                        console.log("NIP dikirim ke Android: " + GLOBAL_NIP);
                    }
                } else {
                    console.log("Objek Android tidak ditemukan (Bukan dibuka via App)");
                }
            }, 2000);
        });
    </script>
    <script>
        // =========================================================================
        // [FITUR BARU]: VARIABEL UNTUK PRE-WARMING GPS & KALKULASI JARAK
        // =========================================================================
        let currentLat = null;
        let currentLng = null;
        let currentAccuracy = 9999;
        let gpsWatcher = null;
        let isWithinRadius = false;

        const bebasRadius = <?= $bebas_radius ? 'true' : 'false' ?>;
        const allowedLocations = <?= json_encode($multi_lokasi) ?>;
        const apelLokasi = <?= (!empty($tugas_apel) && $is_apel_currently_valid && !$sudah_absen_apel) ? json_encode(['lat' => (float)$tugas_apel['latitude'], 'lng' => (float)$tugas_apel['longitude'], 'rad' => (float)($tugas_apel['radius'] ?? 100)]) : 'null' ?>;

        let pwaFaceDetectorReady = false;
        let pwaFaceMatcherObject = null;
        let pwaActiveAbsenMode = 'reguler';
        let pwaActiveApelId = null;
        let isBiometricProcessing = false;
        let isSendingToServer = false;
        let pwaActiveStream = null;

        function getOrCreateDeviceId() {
            let deviceId = localStorage.getItem('sipakatau_device_id');
            if (!deviceId) {
                deviceId = 'F4t-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
                localStorage.setItem('sipakatau_device_id', deviceId);
            }
            return deviceId;
        }

        // Fungsi Haversine untuk menghitung jarak dalam meter
        function hitungJarak(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const p1 = lat1 * Math.PI / 180;
            const p2 = lat2 * Math.PI / 180;
            const dp = (lat2 - lat1) * Math.PI / 180;
            const dl = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dp / 2) * Math.sin(dp / 2) + Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) * Math.sin(dl / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // =========================================================================
        // [FITUR BARU]: FUNGSI PELACAKAN GPS LIVE DI FRONTEND
        // =========================================================================
        function updateGpsStatusUI() {
            let statusElem = document.getElementById('gps-status-text');
            const btnScan = document.getElementById('btn-trigger-attendance');
            const btnApel = document.getElementById('btn-trigger-attendance-apel');

            let btnToControl = btnScan || btnApel;
            let btnDisabled = true;

            let jarakTerdekat = 9999999;
            let radiusTerdekat = 60;

            // Cek apakah mode Apel sedang aktif
            if (apelLokasi) {
                jarakTerdekat = hitungJarak(currentLat, currentLng, apelLokasi.lat, apelLokasi.lng);
                radiusTerdekat = apelLokasi.rad;
            } else if (allowedLocations.length > 0) {
                allowedLocations.forEach(lok => {
                    let jarak = hitungJarak(currentLat, currentLng, lok.lat, lok.lng);
                    if (jarak < jarakTerdekat) {
                        jarakTerdekat = jarak;
                        radiusTerdekat = lok.rad;
                    }
                });
            } else if (!bebasRadius) {
                statusElem.innerHTML = `<span class="text-danger fw-bold">Titik Kantor Belum Diatur</span>`;
                return;
            }

            jarakTerdekat = Math.round(jarakTerdekat);

            if (currentAccuracy > 250) {
                statusElem.innerHTML = `<span class="text-warning fw-bold"><i class="fas fa-spinner fa-spin"></i> Menunggu Satelit (Akurasi ${Math.round(currentAccuracy)}m)...</span>`;
                isWithinRadius = false;
            } else {
                if (bebasRadius) {
                    // MODE SILUMAN VIP: Pura-pura GPS Biasa tanpa sebut kata VIP
                    statusElem.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-satellite-dish"></i> Sinyal Stabil (${Math.round(currentAccuracy)}m)</span>`;
                    isWithinRadius = true;
                    btnDisabled = false;
                } else if (jarakTerdekat <= (radiusTerdekat + 20)) {
                    statusElem.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Di Area Kantor (${jarakTerdekat}m)</span>`;
                    isWithinRadius = true;
                    btnDisabled = false;
                } else {
                    statusElem.innerHTML = `<span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> Jarak ${jarakTerdekat}m (Batas: ${radiusTerdekat}m)</span>`;
                    isWithinRadius = false;
                }
            }

            // Mengontrol Tombol Scan Secara Live
            if (btnToControl) {
                if (btnDisabled) {
                    btnToControl.style.opacity = '0.4';
                    btnToControl.onclick = () => {
                        Swal.fire('Belum Bisa Absen', 'Sistem mendeteksi Anda belum berada di dalam radius kantor atau akurasi GPS HP Anda masih lemah. Silakan dekati area kantor dan pastikan status GPS di layar berwarna HIJAU.', 'error');
                    };
                } else {
                    btnToControl.style.opacity = '1';
                    btnToControl.onclick = () => {
                        if (btnToControl.id === 'btn-trigger-attendance-apel') {
                            pwaActiveAbsenMode = 'apel';
                            pwaActiveApelId = <?= (!empty($tugas_apel)) ? ($tugas_apel['id_apel'] ?? 'null') : 'null' ?>;
                            startBiometricFlow();
                        } else {
                            pwaActiveAbsenMode = 'reguler';
                            startBiometricFlow();
                        }
                    };
                }
            }
        }

        function startGPSTracking() {
            if (navigator.geolocation) {
                gpsWatcher = navigator.geolocation.watchPosition(
                    (pos) => {
                        currentLat = pos.coords.latitude;
                        currentLng = pos.coords.longitude;
                        currentAccuracy = pos.coords.accuracy;
                        updateGpsStatusUI();
                    },
                    (err) => {
                        document.getElementById('gps-status-text').innerHTML = `<span class="text-danger fw-bold">Izin GPS Ditolak / Mati</span>`;
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }
        }

        function forceRefreshGPS() {
            document.getElementById('gps-status-text').innerHTML = 'Memperbarui...';
            navigator.geolocation.getCurrentPosition((pos) => {
                currentLat = pos.coords.latitude;
                currentLng = pos.coords.longitude;
                currentAccuracy = pos.coords.accuracy;
                updateGpsStatusUI();
            }, (err) => {
                document.getElementById('gps-status-text').innerHTML = `<span class="text-danger fw-bold">Gagal mendeteksi lokasi</span>`;
            }, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        }
        // =========================================================================

        function tutupKameraTanpaReload() {
            if (pwaActiveStream) {
                pwaActiveStream.getTracks().forEach(track => track.stop());
                pwaActiveStream = null;
            }
            document.getElementById('camera-verification-overlay').style.display = 'none';
            isBiometricProcessing = false;
            isSendingToServer = false;
            document.getElementById('ai-status-label').innerHTML = '<i class="fas fa-circle-notch fa-spin me-2 text-primary"></i> Menyiapkan Sensor AI...';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Jalankan GPS Pre-Warming & Live Indicator sejak aplikasi dibuka
            startGPSTracking();

            setInterval(() => {
                document.getElementById('pwa-digital-clock').innerText = new Date().toLocaleTimeString('id-ID', {
                    hour12: false
                });
            }, 1000);

            <?php if ($hp_kosong): ?>
                const modalForcePhone = new bootstrap.Modal(document.getElementById('modal-force-phone'));
                modalForcePhone.show();
                document.getElementById('form-force-phone').onsubmit = function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    const fd = new FormData(this);
                    fetch('<?= base_url('pwa/update_profile') ?>', {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json()).then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        }).catch(() => Swal.fire('Error', 'Gagal memproses data', 'error'));
                }
            <?php endif; ?>

            const formProfile = document.getElementById('form-pwa-update-pass');
            if (formProfile) {
                formProfile.onsubmit = function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    const fd = new FormData(this);
                    fetch('<?= base_url('pwa/update_profile') ?>', {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json()).then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        }).catch(() => Swal.fire('Error', 'Gagal memproses data', 'error'));
                }
            }
        });

        async function loadBiometricModels() {
            const URL_MODELS = 'https://justadudewhohacks.github.io/face-api.js/models';
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(URL_MODELS),
                    faceapi.nets.faceLandmark68Net.loadFromUri(URL_MODELS),
                    faceapi.nets.faceRecognitionNet.loadFromUri(URL_MODELS),
                    faceapi.nets.faceExpressionNet.loadFromUri(URL_MODELS)
                ]);

                const rawVector = '<?= $face_data ?>';
                if (rawVector) {
                    const parsed = new Float32Array(JSON.parse(rawVector));
                    pwaFaceMatcherObject = new faceapi.FaceMatcher(new faceapi.LabeledFaceDescriptors('ASN_USER', [parsed]), 0.45);
                }
                pwaFaceDetectorReady = true;
            } catch (err) {
                console.error("AI Error:", err);
            }
        }
        loadBiometricModels();

        async function startBiometricFlow() {
            if (!pwaFaceDetectorReady) return Swal.fire('Tunggu Sebentar', 'Sensor sedang menyiapkan modul AI...', 'info');

            // Jaga-jaga: Pastikan radius sudah dicek live di frontend
            if (!isWithinRadius) {
                return Swal.fire('Di Luar Radius', 'GPS Anda terdeteksi berada di luar jangkauan kantor. Silakan perbarui lokasi Anda.', 'warning');
            }

            isBiometricProcessing = false;
            isSendingToServer = false;

            const overlay = document.getElementById('camera-verification-overlay');
            const video = document.getElementById('camera-stream');
            const statusLabel = document.getElementById('ai-status-label');
            let isEnrollmentMode = false;

            if (!pwaFaceMatcherObject) {
                isEnrollmentMode = true;
                Swal.fire({
                    title: 'Wajah Belum Terdaftar',
                    text: 'Sistem mendeteksi Anda belum mendaftarkan wajah. Posisikan wajah ke kamera lalu TERSENYUM LEBAR.',
                    icon: 'info',
                    confirmButtonText: 'Mulai Rekam Wajah',
                    confirmButtonColor: '#10B981',
                    customClass: {
                        popup: 'rounded-4'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        statusLabel.innerHTML = '<i class="fas fa-smile me-2 text-warning"></i> Posisikan Wajah & TERSENYUM...';
                        overlay.style.display = 'block';
                        mulaiKamera(video, statusLabel, isEnrollmentMode);
                    }
                });
            } else {
                statusLabel.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2 text-primary"></i> Menyiapkan Kamera...';
                overlay.style.display = 'block';
                mulaiKamera(video, statusLabel, isEnrollmentMode);
            }
        }

        async function mulaiKamera(video, statusLabel, isEnrollmentMode) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: {
                            ideal: 640
                        },
                        height: {
                            ideal: 480
                        }
                    }
                });
                pwaActiveStream = stream;
                video.srcObject = stream;
                video.onloadedmetadata = () => video.play();

                // =========================================================================
                // [KUNCI ANTI JOKI]: SENSOR TRANSISI WAJAH (DATAR -> SENYUM)
                // Fase 1: Wajib Datar. Fase 2: Wajib Senyum.
                // =========================================================================
                let livenessStep = 1;

                const detectionInterval = setInterval(async () => {
                    if (isBiometricProcessing) return;
                    try {
                        const result = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                                inputSize: 160
                            }))
                            .withFaceLandmarks().withFaceDescriptor().withFaceExpressions();

                        if (result) {
                            if (isEnrollmentMode) {
                                if (result.detection.score > 0.80 && result.expressions.happy > 0.80) {
                                    isBiometricProcessing = true;
                                    clearInterval(detectionInterval);
                                    statusLabel.innerHTML = '<i class="fas fa-spinner fa-spin me-2 text-warning"></i> Menyimpan Wajah Anda...';
                                    const descriptorArray = Array.from(result.descriptor);
                                    const faceDataJSON = JSON.stringify(descriptorArray);
                                    stream.getTracks().forEach(t => t.stop());
                                    const fd = new FormData();
                                    fd.append('face_vector', faceDataJSON);
                                    fetch('<?= base_url('pwa/simpan_wajah_pwa') ?>', {
                                            method: 'POST',
                                            body: fd,
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest'
                                            }
                                        })
                                        .then(r => r.json()).then(res => {
                                            tutupKameraTanpaReload();
                                            if (res.status === 'success') {
                                                Swal.fire({
                                                    title: 'Berhasil!',
                                                    text: 'Wajah terdaftar. Silakan tekan tombol SCAN kembali untuk absen.',
                                                    icon: 'success',
                                                    confirmButtonColor: '#10B981'
                                                });
                                            } else {
                                                Swal.fire('Gagal', res.message, 'error');
                                            }
                                        }).catch(() => {
                                            tutupKameraTanpaReload();
                                            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                                        });
                                } else {
                                    if (!statusLabel.innerText.includes("TERSENYUM")) {
                                        statusLabel.innerHTML = '<i class="fas fa-smile me-2 text-warning"></i> TERSENYUM LEBAR untuk Daftar...';
                                    }
                                }
                            } else {
                                // MODE ABSEN: Identifikasi Identitas dulu
                                const match = pwaFaceMatcherObject.findBestMatch(result.descriptor);
                                if (match.label !== 'unknown') {

                                    // EKSEKUSI LIVENESS TRANSISI
                                    if (livenessStep === 1) {
                                        // Fase 1: Cek apakah wajah sedang Datar/Netral (Happy index sangat rendah)
                                        if (result.expressions.happy < 0.1) {
                                            livenessStep = 2; // Berhasil Datar, Lanjut Minta Senyum
                                            statusLabel.innerHTML = '<i class="fas fa-smile-beam text-warning me-2" style="transform:scale(1.2);"></i> SEKARANG TERSENYUM LEBAR!';
                                        } else {
                                            statusLabel.innerHTML = '<i class="fas fa-meh text-primary me-2"></i> Mohon wajah DATAR (Jangan Senyum) dulu...';
                                        }
                                    } else if (livenessStep === 2) {
                                        // Fase 2: Wajah sudah Datar tadi, sekarang wajib Senyum Lebar
                                        if (result.expressions.happy > 0.85) {
                                            isBiometricProcessing = true;
                                            clearInterval(detectionInterval);
                                            statusLabel.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> BERHASIL! Menyimpan Presensi...';
                                            setTimeout(() => finalizeAttendance(stream), 1000);
                                        } else {
                                            if (!statusLabel.innerText.includes("SEKARANG")) {
                                                statusLabel.innerHTML = '<i class="fas fa-smile-beam text-warning me-2" style="transform:scale(1.2);"></i> Ayo TERSENYUM LEBAR sekarang!';
                                            }
                                        }
                                    }

                                } else {
                                    statusLabel.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> WAJAH TIDAK DIKENALI!</span>';
                                    livenessStep = 1; // Reset Fase Liveness kalau wajah diganti joki
                                }
                            }
                        } else {
                            if (!statusLabel.innerText.includes("Tengah")) {
                                statusLabel.innerHTML = '<i class="fas fa-expand me-2 text-dark"></i> Posisikan Wajah ke Tengah...';
                            }
                            livenessStep = 1; // Reset Fase Liveness kalau wajah keluar dari kamera
                        }
                    } catch (e) {}
                }, 600);
            } catch (err) {
                tutupKameraTanpaReload();
                Swal.fire('Akses Ditolak', 'Gagal akses kamera. Pastikan izin kamera browser aktif.', 'error');
            }
        }

        function finalizeAttendance(stream) {
            if (isSendingToServer) return;
            isSendingToServer = true;
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
            }

            const fd = new FormData();
            fd.append('latitude', currentLat);
            fd.append('longitude', currentLng);
            fd.append('nip', GLOBAL_NIP);
            fd.append('device_info', getOrCreateDeviceId());

            let url = '<?= base_url('pwa/submit_absen') ?>';

            // [LOGIKA MODE PENGIRIMAN]: Reguler, Apel, atau Cito
            if (pwaActiveAbsenMode === 'apel') {
                url = '<?= base_url('pwa/submit_absen_apel') ?>';
                fd.append('id_apel', pwaActiveApelId);
            } else if (pwaActiveAbsenMode === 'cito_mulai' || pwaActiveAbsenMode === 'cito_akhir') {
                url = '<?= base_url('pwa/submit_cito') ?>';
                if (pwaActiveAbsenMode === 'cito_mulai') {
                    fd.append('keterangan', window.citoKeteranganTeks);
                }
            }

            fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async (r) => {
                    if (!r.ok) throw new Error('Kesalahan server.');
                    return r.json();
                })
                .then(data => {
                    document.getElementById('camera-verification-overlay').style.display = 'none';

                    if (data.status === 'confirm_pulang_cepat') {
                        Swal.fire({
                            title: 'Konfirmasi Pulang Cepat',
                            html: data.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Tetap Pulang',
                            cancelButtonText: 'Batal',
                            customClass: {
                                popup: 'rounded-4'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Memproses...',
                                    allowOutsideClick: false,
                                    didOpen: () => Swal.showLoading()
                                });
                                fd.append('confirm_pulang', '1');
                                fetch(url, {
                                        method: 'POST',
                                        body: fd,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(r2 => r2.json()).then(resFinal => {
                                        if (resFinal.status === 'success') {
                                            Swal.fire({
                                                title: 'Sukses',
                                                html: resFinal.message,
                                                icon: 'success',
                                                confirmButtonColor: '#10B981',
                                                customClass: {
                                                    popup: 'rounded-4'
                                                }
                                            }).then(() => {
                                                window.location.replace('<?= base_url('pwa') ?>');
                                            });
                                        } else {
                                            tutupKameraTanpaReload();
                                            Swal.fire('Ditolak', resFinal.message, 'warning');
                                        }
                                    });
                            } else {
                                tutupKameraTanpaReload();
                            }
                        });
                    } else if (data.status === 'confirm_double') {
                        Swal.fire({
                            title: 'Konfirmasi Double Shift',
                            html: data.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#10B981',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, Lanjut Shift 2',
                            cancelButtonText: 'Batal',
                            customClass: {
                                popup: 'rounded-4'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Memproses...',
                                    allowOutsideClick: false,
                                    didOpen: () => Swal.showLoading()
                                });
                                fd.append('confirm_double', '1');
                                fetch(url, {
                                        method: 'POST',
                                        body: fd,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(r2 => r2.json()).then(resFinal => {
                                        if (resFinal.status === 'success') {
                                            Swal.fire({
                                                title: 'Sukses',
                                                html: resFinal.message,
                                                icon: 'success',
                                                confirmButtonColor: '#10B981',
                                                customClass: {
                                                    popup: 'rounded-4'
                                                }
                                            }).then(() => {
                                                window.location.replace('<?= base_url('pwa') ?>');
                                            });
                                        } else {
                                            tutupKameraTanpaReload();
                                            Swal.fire('Ditolak', resFinal.message, 'warning');
                                        }
                                    }).catch(() => {
                                        tutupKameraTanpaReload();
                                        Swal.fire('Error', 'Gagal memproses konfirmasi shift ganda.', 'error');
                                    });
                            } else {
                                tutupKameraTanpaReload();
                            }
                        });
                    } else if (data.status === 'error_device_conflict') {
                        Swal.fire({
                            title: 'HP Sudah Terdaftar',
                            html: data.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#10B981',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Paksa Daftar',
                            cancelButtonText: 'Batal',
                            customClass: {
                                popup: 'rounded-4'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Mendaftarkan...',
                                    allowOutsideClick: false,
                                    didOpen: () => Swal.showLoading()
                                });
                                fd.append('force_device_register', '1');
                                fetch(url, {
                                    method: 'POST',
                                    body: fd,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                }).then(r3 => r3.json()).then(resForce => {
                                    if (resForce.status === 'success') {
                                        Swal.fire({
                                            title: 'Sukses',
                                            html: resForce.message,
                                            icon: 'success'
                                        }).then(() => location.reload());
                                    } else {
                                        tutupKameraTanpaReload();
                                        Swal.fire('Ditolak', resForce.message, 'error');
                                    }
                                });
                            } else {
                                tutupKameraTanpaReload();
                            }
                        });
                    } else if (data.status === 'success') {
                        Swal.fire({
                            title: 'Sukses',
                            html: data.message,
                            icon: 'success',
                            confirmButtonColor: '#10B981',
                            customClass: {
                                popup: 'rounded-4'
                            }
                        }).then(() => {
                            window.location.replace('<?= base_url('pwa') ?>');
                        });
                    } else {
                        tutupKameraTanpaReload();
                        Swal.fire('Ditolak', data.message, 'warning');
                    }
                }).catch(() => {
                    tutupKameraTanpaReload();
                    Swal.fire('Error', 'Gagal mengirim data ke server.', 'error');
                });
        }

        let isCitoProcessing = false;
        window.citoKeteranganTeks = ''; // Simpan keterangan sementara sebelum scan wajah

        // =====================================================================
        // [UPDATE FIX]: FUNGSI HANDLE CITO (WAJIB RADIUS & WAJIB SCAN WAJAH)
        // =====================================================================
        function handleCito(isAktif) {
            if (isCitoProcessing) return;

            // 1. GEMBOK RADIUS: Tolak kalau sedang di rumah / luar RSUD
            if (!isWithinRadius) {
                Swal.fire('Di Luar Radius', 'Anda terdeteksi berada di luar area Rumah Sakit. Silakan masuk ke area RSUD untuk melakukan tindakan CITO.', 'warning');
                return;
            }

            if (!isAktif) {
                Swal.fire({
                    title: 'Mulai Darurat / Cito',
                    input: 'text',
                    inputLabel: 'Masukkan Keterangan / Tindakan:',
                    inputPlaceholder: 'Cth: Panggilan Operasi Darurat...',
                    showCancelButton: true,
                    confirmButtonText: 'Lanjut Scan Wajah',
                    cancelButtonText: 'Batal',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Keterangan tidak boleh kosong!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 2. SIMPAN KETERANGAN LALU LEMPAR KE KAMERA AI
                        window.citoKeteranganTeks = result.value;
                        pwaActiveAbsenMode = 'cito_mulai';
                        startBiometricFlow();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Akhiri Darurat / Cito',
                    text: 'Anda yakin ingin mengakhiri tindakan darurat ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    confirmButtonText: 'Ya, Scan Wajah',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 3. LEMPAR KE KAMERA AI UNTUK MENGAKHIRI
                        window.citoKeteranganTeks = '';
                        pwaActiveAbsenMode = 'cito_akhir';
                        startBiometricFlow();
                    }
                });
            }
        }

        function handleFormSpecial(kat) {
            const hidden = document.getElementById('permit-hidden-kategori');
            const label = document.getElementById('permit-label-header');
            const subSelect = document.getElementById('permit-sub-kategori');
            hidden.value = kat;
            label.innerText = "Pengajuan " + kat;

            let options = "";
            if (kat === 'Sakit') {
                options = '<option value="Sakit Penuh">Sakit (Satu Hari Penuh)</option>';
            } else if (kat === 'Izin') {
                options = '<option value="Izin Penuh">Izin Penuh</option><option value="Setengah Hari">Izin Setengah Hari</option><option value="Koreksi Shift">Koreksi Shift (Dinas Malam RSUD)</option>';
            } else if (kat === 'Tugas Luar') {
                options = '<option value="Luar Daerah">TL Luar Daerah</option><option value="Dalam Daerah">TL Dalam Daerah</option>';
            }

            subSelect.innerHTML = options;
            togglePermitLogic();
            new bootstrap.Modal(document.getElementById('modal-special-permit')).show();
        }

        function togglePermitLogic() {
            const sub = document.getElementById('permit-sub-kategori').value;
            document.getElementById('permit-wrapper-slot').style.display = (sub === 'Setengah Hari' || sub === 'Dalam Daerah') ? 'block' : 'none';
            document.getElementById('permit-wrapper-tgl-selesai').style.display = (sub === 'Setengah Hari' || sub === 'Koreksi Shift') ? 'none' : 'block';
        }

        // =====================================================================
        // FUNGSI KOMPRESI FOTO OTOMATIS LANGSUNG DI HP PEGAWAI (FRONTEND)
        // =====================================================================
        async function compressImage(file, maxWidth, maxHeight, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = event => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;

                        // Kalkulasi rasio agar proporsional
                        if (width > height) {
                            if (width > maxWidth) {
                                height = Math.round(height *= maxWidth / width);
                                width = maxWidth;
                            }
                        } else {
                            if (height > maxHeight) {
                                width = Math.round(width *= maxHeight / height);
                                height = maxHeight;
                            }
                        }

                        // Gambar ulang di Canvas HP
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Ekspor menjadi file JPG yang super ringan
                        canvas.toBlob(blob => {
                            resolve(new File([blob], file.name.replace(/\.[^/.]+$/, ".jpg"), {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = error => reject(error);
                };
                reader.onerror = error => reject(error);
            });
        }

        // =====================================================================
        // PROSES SUBMIT PENGAJUAN (DENGAN INTERCEPT KOMPRESI)
        // =====================================================================
        document.getElementById('form-submit-special-permit').onsubmit = async function(e) {
            e.preventDefault();

            const fileInput = this.querySelector('input[type="file"]');
            const file = fileInput.files[0];
            let fileToSend = file;

            // Jika file adalah FOTO dan ukurannya lebih dari 1 MB, kompres dulu!
            if (file && file.type.startsWith('image/') && file.size > 1024 * 1024) {
                Swal.fire({
                    title: 'Memproses Foto...',
                    html: 'Sedang mengecilkan ukuran gambar secara otomatis...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    // Paksa kompres ke resolusi max 1200px dan kualitas 70%
                    fileToSend = await compressImage(file, 1200, 1200, 0.7);
                } catch (err) {
                    console.error("Gagal kompresi di HP:", err);
                    // Jika gagal kompres karena HP tidak support, tetap gunakan file asli
                }
            }

            Swal.fire({
                title: 'Mengirim...',
                html: 'Sedang mengunggah data pengajuan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData(this);
            // Timpa file asli dengan file yang sudah dikompres (untuk PDF dan file normal tetap membawa nama aslinya)
            if (file && fileToSend) {
                formData.set('file_bukti', fileToSend, file.name);
            }
            formData.append('nip_manual', GLOBAL_NIP);
            formData.append('device_info', getOrCreateDeviceId());

            fetch('<?= base_url('pwa/submit_izin_pwa') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async (r) => {
                    if (!r.ok) throw new Error('Kesalahan server.');
                    return r.json();
                })
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({
                            title: 'Berhasil',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#10B981'
                        }).then(() => window.location.replace('<?= base_url('pwa') ?>'));
                    } else {
                        Swal.fire('Gagal', res.message, 'warning');
                    }
                }).catch((err) => {
                    Swal.fire('Koneksi Gagal', 'Gagal mengirim data. Pastikan internet Anda aktif dan stabil.', 'error');
                });
        };

        function handleUIRefresh(btn) {
            btn.querySelector('i').classList.add('fa-spin');
            setTimeout(() => location.reload(), 300);
        }

        function handleOpenPassModal() {
            new bootstrap.Modal(document.getElementById('modal-pwa-password')).show();
        }

        function handleOpenFilterModal() {
            new bootstrap.Modal(document.getElementById('modal-pwa-filter')).show();
        }

        let touchStart = 0;
        let touchMove = 0;
        const ptrIndicator = document.getElementById('ptr-indicator');
        const ptrIcon = ptrIndicator.querySelector('.ptr-icon');

        window.addEventListener('touchstart', (e) => {
            touchStart = e.touches[0].pageY;
        }, {
            passive: true
        });
        window.addEventListener('touchmove', (e) => {
            touchMove = e.touches[0].pageY;
            const distance = touchMove - touchStart;
            if (distance > 0 && window.scrollY <= 0) {
                const drive = Math.min(distance * 0.4, 100);
                ptrIndicator.style.transform = `translateY(${drive}px)`;
                ptrIcon.style.transform = `rotate(${distance * 2}deg)`;
                ptrIcon.style.background = (drive > 70) ? 'var(--accent)' : 'var(--primary)';
            }
        }, {
            passive: true
        });

        window.addEventListener('touchend', (e) => {
            const distance = touchMove - touchStart;
            if (window.scrollY <= 0 && distance > 150) {
                ptrIndicator.classList.add('ptr-refreshing');
                ptrIndicator.style.transform = `translateY(80px)`;
                if (navigator.vibrate) navigator.vibrate(20);
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                ptrIndicator.style.transform = `translateY(0)`;
            }
            touchStart = 0;
            touchMove = 0;
        });
    </script>
</body>

</html>