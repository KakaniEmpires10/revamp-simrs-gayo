<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CetakBerkasPasienController extends Controller
{
    public function noAntreanPoli(string $noRawat): Response
    {
        $pasien = $this->pasienTerdaftar($noRawat);

        return Pdf::loadView('pendaftaran.cetak.no-antrean-poli', [
            'pasien' => $pasien,
            'barcodeBars' => $this->barcodeBars($pasien->no_rkm_medis),
            'rumahSakit' => $this->rumahSakit(),
        ])
            ->setPaper($this->paperFromMillimeter(80, 120), 'portrait')
            ->stream("No Antrean Poli {$this->namaFileNomorRawat($pasien->no_rawat)}.pdf");
    }

    public function labelGelang(string $noRawat): Response
    {
        $pasien = $this->pasienTerdaftar($noRawat);

        return Pdf::loadView('pendaftaran.cetak.label-gelang', [
            'pasien' => $pasien,
            'qrCode' => $this->qrCodeDataUri($pasien->no_rkm_medis),
        ])
            ->setPaper($this->paperFromMillimeter(65, 40), 'landscape')
            ->stream("Label Gelang {$this->namaFileNomorRawat($pasien->no_rawat)}.pdf");
    }

    public function gelangPasien(string $noRawat): Response
    {
        $pasien = $this->pasienTerdaftar($noRawat);

        return Pdf::loadView('pendaftaran.cetak.gelang-pasien', [
            'pasien' => $pasien,
            'qrCode' => $this->qrCodeDataUri($pasien->no_rkm_medis),
            'rumahSakit' => $this->rumahSakit(),
        ])
            ->setPaper($this->paperFromMillimeter(250, 22), 'landscape')
            ->stream("Gelang Pasien {$this->namaFileNomorRawat($pasien->no_rawat)}.pdf");
    }

    private function pasienTerdaftar(string $noRawat): object
    {
        $pasien = DB::table('reg_periksa as reg')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg.no_rkm_medis')
            ->join('poliklinik as poli', 'poli.kd_poli', '=', 'reg.kd_poli')
            ->join('dokter', 'dokter.kd_dokter', '=', 'reg.kd_dokter')
            ->join('penjab', 'penjab.kd_pj', '=', 'reg.kd_pj')
            ->where('reg.no_rawat', $noRawat)
            ->first([
                'reg.no_reg',
                'reg.no_rawat',
                'reg.tgl_registrasi',
                'reg.jam_reg',
                'reg.umurdaftar',
                'reg.sttsumur',
                'reg.status_bayar',
                'reg.stts',
                'pasien.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_peserta',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.alamat',
                'poli.nm_poli',
                'dokter.nm_dokter',
                'penjab.png_jawab',
            ]);

        if ($pasien === null) {
            throw new NotFoundHttpException("Pendaftaran {$noRawat} tidak ditemukan.");
        }

        $pasien->nama_pasien = $this->titleName((string) $pasien->nm_pasien);
        $pasien->jenis_kelamin = $pasien->jk === 'P' ? 'Perempuan' : 'Laki-laki';
        $pasien->tanggal_lahir_label = $this->tanggalIndonesia((string) $pasien->tgl_lahir);
        $pasien->tanggal_registrasi_label = $this->tanggalIndonesia((string) $pasien->tgl_registrasi);
        $pasien->jam_reg_label = substr((string) $pasien->jam_reg, 0, 5);
        $pasien->umur_label = "{$pasien->umurdaftar} {$pasien->sttsumur}";

        return $pasien;
    }

    /**
     * @return array{nama:string,header:list<string>}
     */
    private function rumahSakit(): array
    {
        $name = (string) config('app.name', 'SIMRS');

        return [
            'nama' => $name,
            'header' => [
                $name,
                'BUKTI REGISTRASI PENDAFTARAN',
            ],
        ];
    }

    private function qrCodeDataUri(string $value): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(160, 1),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($value);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return list<int>
     */
    private function barcodeBars(string $value): array
    {
        return collect(str_split($value))
            ->flatMap(function (string $character): array {
                $number = ord($character);

                return [
                    ($number % 3) + 1,
                    1,
                    (($number >> 2) % 3) + 1,
                    1,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function paperFromMillimeter(float $width, float $height): array
    {
        return [0, 0, $width * 72 / 25.4, $height * 72 / 25.4];
    }

    private function namaFileNomorRawat(string $noRawat): string
    {
        return str_replace(['/', '\\'], '-', $noRawat);
    }

    private function titleName(string $name): string
    {
        return str($name)->lower()->title()->toString();
    }

    private function tanggalIndonesia(string $date): string
    {
        return Carbon::parse($date)->translatedFormat('d F Y');
    }
}
