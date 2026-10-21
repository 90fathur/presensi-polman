<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Presensi Polman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .card-update {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 350px;
            width: 100%;
            text-align: center;
        }

        .logo-pemkab {
            width: 60px;
            margin-bottom: 15px;
        }

        .btn-utama {
            background-color: #155724;
            color: white !important;
            padding: 15px;
            border-radius: 12px;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
        }

        .btn-kedua {
            background-color: #ffffff;
            color: #155724 !important;
            border: 2px solid #155724;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            text-decoration: none;
            display: block;
        }
    </style>
</head>

<body>

    <div class="card-update">
        <img src="https://polmankab.go.id//assets/uploads/summernote/SUMMER_0_1734504076.png" class="logo-pemkab">

        <h5 class="fw-bold">Aplikasi Perlu Update</h5>
        <p class="small text-muted">Versi ini sudah tidak berlaku. Pilih salah satu cara di bawah:</p>

        <a href="https://presensi.polmankab.go.id/download/presensinew.apk" class="btn-utama">
            <i class="fas fa-download me-2"></i> 1. Download APK Baru
        </a>

        <a href="https://presensi.polmankab.go.id" target="_blank" class="btn-kedua">
            <i class="fas fa-external-link-alt me-2"></i> 2. Buka di Chrome dan ketik https://presensi.polmankab.go.id
        </a>

        <div class="mt-4 small text-muted">
            Dinas Kominfo SP <br> Polewali Mandar
        </div>
    </div>

</body>

</html>