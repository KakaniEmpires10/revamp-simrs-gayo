<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
        }

        .label {
            position: relative;
            width: 65mm;
            height: 40mm;
            padding: 2mm 2mm 2mm 17mm;
            overflow: hidden;
            font-size: 9px;
            line-height: 1.35;
        }

        .qr {
            position: absolute;
            left: 1mm;
            top: 10mm;
            width: 15mm;
            height: 15mm;
        }

        .row {
            display: table;
            width: 100%;
        }

        .row span {
            display: table-cell;
            vertical-align: top;
        }

        .row span:first-child {
            width: 15mm;
            font-weight: 700;
        }

        .name {
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <main class="label">
        <img class="qr" src="{{ $qrCode }}" alt="QR No RM">

        <div class="row"><span>No.RM</span><span>: {{ $pasien->no_rkm_medis }}</span></div>
        <div class="row"><span>Nama</span><span class="name">: {{ $pasien->nama_pasien }}</span></div>
        <div class="row"><span>Peserta</span><span>: {{ $pasien->no_peserta ?: '-' }}</span></div>
        <div class="row"><span>Kelamin</span><span>: {{ $pasien->jenis_kelamin }}</span></div>
        <div class="row"><span>Tgl.Lahir</span><span>: {{ $pasien->tanggal_lahir_label }}</span></div>
        <div class="row"><span>Alamat</span><span>: {{ $pasien->alamat ?: '-' }}</span></div>
        <div class="row"><span>Tgl.Msk</span><span>: {{ $pasien->tanggal_registrasi_label }}</span></div>
        <div class="row"><span>Bayar</span><span>: {{ $pasien->png_jawab }}</span></div>
    </main>
</body>
</html>
