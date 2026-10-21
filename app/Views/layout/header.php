<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard' ?> | Presensi AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    

<link rel="apple-touch-icon" href="<?= base_url('assets/img/logo_180.png') ?>">
    <style>
    :root {
            --primary: #111827;
            --sidebar-width: 210px;
            --bg-light: #f0f2f5;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
        }

        /* SIDEBAR RESPONSIVE */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--primary);
            color: white;
            padding: 20px 10px;
            z-index: 1050;
            transition: all 0.3s;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        /* KONDISI LAYAR HP (di bawah 768px) */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                /* Sembunyikan sidebar ke kiri */
            }

            .main-content {
                margin-left: 0;
                /* Konten jadi full layar */
                padding: 15px;
            }

            /* Tambahkan class ini nanti via JS untuk buka tutup sidebar di HP */
            .sidebar.active {
                margin-left: 0;
            }
        }

        /* Card Stats agar tidak numpuk jelek di HP */
        .card {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>