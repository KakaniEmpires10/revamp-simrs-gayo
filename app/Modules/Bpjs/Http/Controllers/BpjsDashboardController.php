<?php

namespace App\Modules\Bpjs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bpjs\Infrastructure\AntrolClient;
use App\Modules\Bpjs\Infrastructure\VClaimClient;
use Inertia\Inertia;
use Inertia\Response;

class BpjsDashboardController extends Controller
{
    public function index(AntrolClient $antrolClient, VClaimClient $vClaimClient): Response
    {
        return Inertia::render('IntegrasiEksternal/Bpjs/Index', [
            'antrolConfigured' => $antrolClient->isConfigured(),
            'vclaimConfigured' => $vClaimClient->isConfigured(),
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    public function show(string $menu): Response
    {
        $item = $this->findMenuItem($menu);

        abort_if($item === null, 404);

        return Inertia::render('IntegrasiEksternal/Bpjs/DetailSubmenu', [
            'menu' => $item,
        ]);
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, href: string, icon: string, badge: string, badgeVariant: string}>}>
     */
    private function menuGroups(): array
    {
        return [
            [
                'title' => 'VClaim',
                'items' => [
                    $this->menuItem('Cek Koneksi BPJS', 'cek-koneksi-bpjs', 'Monitoring koneksi layanan BPJS dari aplikasi pendaftaran.', 'Wifi', 'VClaim', 'soft-warning'),
                    $this->menuItem('Monitoring Data Kunjungan', 'vclaim-monitoring-data-kunjungan', 'Data kunjungan peserta berdasarkan SEP dan tanggal pelayanan.', 'ActivitySquare', 'VClaim'),
                    $this->menuItem('Cek Rujukan', 'cek-rujukan-noka', 'Pencarian rujukan berdasarkan nomor kartu atau NIK.', 'SearchCheck', 'VClaim'),
                    $this->menuItem('Data Rujukan', 'list-rujukan-keluar', 'Daftar rujukan keluar BPJS.', 'Route', 'VClaim'),
                    $this->menuItem('Rencana Kontrol / SKDP', 'vclaim-rencana-kontrol', 'Pembuatan dan monitoring surat kontrol rawat jalan.', 'ClipboardList', 'VClaim'),
                    $this->menuItem('Rencana Rawat Inap / SPRI', 'vclaim-rencana-rawat-inap', 'Pembuatan rencana rawat inap dan SPRI.', 'BedDouble', 'VClaim'),
                    $this->menuItem('Data PRB', 'data-prb', 'Daftar Program Rujuk Balik BPJS.', 'Pill', 'VClaim'),
                    // $this->menuItem('Data Persetujuan SEP', 'list-persetujuan-sep', 'Daftar persetujuan SEP.', 'BadgeCheck', 'VClaim'),
                    $this->menuItem('Data Finger Print', 'list-finger-print', 'Data validasi finger print peserta.', 'Fingerprint', 'VClaim'),
                    $this->menuItem('Monitoring Klaim', 'monitoring-klaim', 'Monitoring klaim BPJS.', 'ChartNoAxesCombined', 'VClaim'),
                ],
            ],
            [
                'title' => 'JKN Online / Antrol',
                'items' => [
                    $this->menuItem('Antrian dan Kirim Task ID Manual', 'manual-taskid-bpjs', 'Kirim ulang antrean dan Task ID Antrol secara manual.', 'Send', 'Antrol', 'soft-info'),
                    $this->menuItem('Dashboard Antrian Harian', 'dashboard-antrian-bpjs-perhari', 'Ringkasan antrean online BPJS per hari.', 'CalendarDays', 'Antrol', 'soft-info'),
                    $this->menuItem('Dashboard Antrian Bulanan', 'dashboard-antrian-bpjs-perbulan', 'Ringkasan antrean online BPJS per bulan.', 'CalendarRange', 'Antrol', 'soft-info'),
                    $this->menuItem('Jadwal Dokter HFIS', 'jadwal-dokter-hfis-bpjs-v2', 'Sinkronisasi dan pengecekan jadwal dokter HFIS versi 2.', 'CalendarSync', 'HFIS', 'soft-indigo'),
                    $this->menuItem('List Task ID Pertanggal', 'get-list-taskid-pertanggal-bpjs', 'Ambil daftar Task ID Antrol berdasarkan tanggal.', 'ListFilter', 'Antrol', 'soft-info'),
                    $this->menuItem('Antrian Belum Terlayani', 'get-antrian-belum-terlayani-bpjs', 'Daftar antrean BPJS yang belum terlayani.', 'ClockAlert', 'Antrol', 'soft-warning'),
                    $this->menuItem('Mapping Dokter', 'mapping-dokter-dpjp-vclaim', 'Mapping dokter SIMRS dengan kode dokter BPJS.', 'UserRoundCog', 'Mapping', 'soft-success'),
                    $this->menuItem('Mapping Poliklinik', 'mapping-poliklinik-vclaim', 'Mapping poliklinik SIMRS dengan kode poli BPJS.', 'MapPinned', 'Mapping', 'soft-success'),
                    $this->menuItem('Referensi Pendaftaran Mobile JKN', 'ref-pendaftaran-antrol', 'Validasi dan referensi pendaftaran Mobile JKN.', 'Smartphone', 'Mobile JKN', 'soft-primary'),
                    $this->menuItem('Batal Pendaftaran Mobile JKN', 'ref-pendaftaran-antrol-batal', 'Daftar pembatalan pendaftaran Mobile JKN.', 'SmartphoneNfc', 'Mobile JKN', 'soft-primary'),
                    $this->menuItem('Task ID Mobile JKN', 'get-list-taskid-bpjs', 'Daftar Task ID untuk antrean Mobile JKN.', 'ListTodo', 'Mobile JKN', 'soft-primary'),
                    $this->menuItem('Manage Antrian Online BPJS', 'antrian-online-bpjs', 'Pengelolaan antrean online BPJS.', 'MonitorCog', 'Antrol', 'soft-info'),
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, href: string, icon: string, badge: string, badgeVariant: string}
     */
    private function menuItem(string $title, string $slug, string $description, string $icon, string $badge, string $badgeVariant = 'soft-primary'): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'href' => route('integrasi-eksternal.bpjs.submenu.show', ['menu' => $slug]),
            'icon' => $icon,
            'badge' => $badge,
            'badgeVariant' => $badgeVariant,
        ];
    }

    /**
     * @return array{title: string, description: string, href: string, icon: string, badge: string, badgeVariant: string}|null
     */
    private function findMenuItem(string $slug): ?array
    {
        foreach ($this->menuGroups() as $group) {
            foreach ($group['items'] as $item) {
                if (str($item['href'])->endsWith('/'.$slug)) {
                    return $item;
                }
            }
        }

        return null;
    }
}
