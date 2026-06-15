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

        .ticket {
            width: 80mm;
            height: 120mm;
            padding: 6mm 4mm 4mm;
            overflow: hidden;
        }

        .center {
            text-align: center;
        }

        .hospital {
            font-size: 9px;
            line-height: 1.35;
            font-weight: 700;
            text-transform: uppercase;
        }

        .divider {
            margin: 5mm 0 3mm;
            border-top: 1px dashed #111827;
        }

        .title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .queue-label {
            margin-top: 6mm;
            font-size: 16px;
            font-weight: 700;
        }

        .queue-number {
            margin-top: 2mm;
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
        }

        .details {
            margin-top: 7mm;
            font-size: 8px;
            line-height: 1.75;
        }

        .details-row {
            display: table;
            width: 100%;
        }

        .details-row span {
            display: table-cell;
            vertical-align: top;
        }

        .details-row span:first-child {
            width: 23mm;
        }

        .barcode {
            margin: 6mm auto 2mm;
            height: 13mm;
            white-space: nowrap;
            text-align: center;
        }

        .bar {
            display: inline-block;
            height: 13mm;
            margin-right: .35mm;
            background: #111827;
            vertical-align: top;
        }

        .thanks {
            margin-top: 3mm;
            font-size: 7px;
            line-height: 1.45;
            font-style: italic;
        }
    </style>
</head>
<body>
    <main class="ticket">
        <section class="center hospital">
            @foreach ($rumahSakit['header'] as $line)
                <div>{{ $line }}</div>
            @endforeach
        </section>

        <div class="divider"></div>

        <section class="center">
            <div class="title">BUKTI REGISTRASI PENDAFTARAN</div>
            <div class="queue-label">NOMOR ANTREAN POLI</div>
            <div class="queue-number">{{ $pasien->no_reg }}</div>
        </section>

        <section class="details">
            <div class="details-row"><span>Tanggal</span><span>: {{ $pasien->tgl_registrasi }} {{ $pasien->jam_reg_label }}</span></div>
            <div class="details-row"><span>No Rawat</span><span>: {{ $pasien->no_rawat }}</span></div>
            <div class="details-row"><span>Nama Pasien</span><span>: {{ $pasien->nama_pasien }}</span></div>
            <div class="details-row"><span>No. RM</span><span>: {{ $pasien->no_rkm_medis }}</span></div>
            <div class="details-row"><span>Ruang / Poli</span><span>: {{ $pasien->nm_poli }}</span></div>
        </section>

        <div class="divider"></div>

        <section class="center thanks">
            <div>Terima Kasih Atas Kepercayaan Anda</div>
            <div>Bawalah Kartu Berobat Setiap Berkunjung</div>
            <div>Ke Rumah Sakit {{ $rumahSakit['nama'] }}</div>
        </section>

        <div class="barcode" aria-label="Barcode No RM">
            @foreach ($barcodeBars as $width)
                <span class="bar" style="width: {{ $width * 0.45 }}mm;"></span>
            @endforeach
        </div>

        <div class="center" style="font-size: 7px;">{{ $pasien->no_rkm_medis }}</div>
    </main>
</body>
</html>
