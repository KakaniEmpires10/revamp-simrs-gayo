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

        .wristband {
            position: relative;
            width: 250mm;
            height: 22mm;
            padding: 2mm 4mm 2mm 150mm;
            overflow: hidden;
            font-size: 10px;
            line-height: 1.3;
        }

        .qr {
            position: absolute;
            left: 130mm;
            top: 2mm;
            width: 18mm;
            height: 18mm;
        }

        .hospital {
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 1mm;
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
            width: 16mm;
            font-weight: 700;
        }

        .name {
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <main class="wristband">
        <img class="qr" src="{{ $qrCode }}" alt="QR No RM">

        <div class="hospital">{{ $rumahSakit['nama'] }}</div>
        <div class="row"><span>No.RM</span><span>: {{ $pasien->no_rkm_medis }} &nbsp;&nbsp; Tgl.Msk. {{ $pasien->tanggal_registrasi_label }}</span></div>
        <div class="row"><span>Nama</span><span class="name">: {{ $pasien->nama_pasien }} ({{ $pasien->jk }})</span></div>
        <div class="row"><span>Tgl.Lahir</span><span>: {{ $pasien->tanggal_lahir_label }} ({{ $pasien->umur_label }})</span></div>
    </main>
</body>
</html>
