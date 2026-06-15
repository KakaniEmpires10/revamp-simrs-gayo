<?php

namespace App\Http\Controllers\ManajemenPegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenPegawai\SimpanJadwalPraktekRequest;
use App\Services\ManajemenPegawai\ManajemenPegawaiService;
use App\Support\Feedback;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JadwalPraktekController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'day' => $request->string('day')->toString(),
            'doctor' => $request->string('doctor')->toString(),
            'clinic' => $request->string('clinic')->toString(),
        ];

        return Inertia::render('ManajemenPegawai/JadwalPraktek', [
            'schedules' => Inertia::scroll(fn () => $this->listing($request, $filters)),
            'doctors' => fn () => DB::table('dokter')
                ->select(['kd_dokter', 'nm_dokter'])
                ->where('status', '1')
                ->orderBy('nm_dokter')
                ->get(),
            'clinics' => fn () => DB::table('poliklinik')
                ->select(['kd_poli', 'nm_poli'])
                ->orderBy('nm_poli')
                ->get(),
            'days' => fn () => ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD'],
            'filters' => fn () => $filters,
        ]);
    }

    public function store(SimpanJadwalPraktekRequest $request, ManajemenPegawaiService $service): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanJadwalPraktek($request->validated()),
            'Jadwal praktek berhasil disimpan.',
            'Jadwal praktek gagal disimpan.',
        );
    }

    public function update(
        SimpanJadwalPraktekRequest $request,
        ManajemenPegawaiService $service,
        string $doctor,
        string $day,
        string $start
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->ubahJadwalPraktek($request->validated(), $doctor, $day, $start),
            'Jadwal praktek berhasil diperbarui.',
            'Jadwal praktek gagal diperbarui.',
        );
    }

    public function destroy(
        ManajemenPegawaiService $service,
        string $doctor,
        string $day,
        string $start
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->hapusJadwalPraktek($doctor, $day, $start),
            'Jadwal praktek berhasil dihapus.',
            'Jadwal praktek gagal dihapus.',
        );
    }

    /**
     * @param  array{day: string, doctor: string, clinic: string}  $filters
     */
    private function listing(Request $request, array $filters): LengthAwarePaginator
    {
        return DB::table('jadwal')
            ->select([
                'jadwal.kd_dokter',
                'dokter.nm_dokter',
                'jadwal.hari_kerja',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kd_poli',
                'poliklinik.nm_poli',
                'jadwal.kuota',
            ])
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'jadwal.kd_poli')
            ->where('dokter.status', '1')
            ->when($filters['day'] !== '', fn (Builder $query) => $query->where('jadwal.hari_kerja', $filters['day']))
            ->when($filters['doctor'] !== '', fn (Builder $query) => $query->where('jadwal.kd_dokter', $filters['doctor']))
            ->when($filters['clinic'] !== '', fn (Builder $query) => $query->where('jadwal.kd_poli', $filters['clinic']))
            ->orderBy('jadwal.kd_dokter')
            ->orderByRaw("FIELD(jadwal.hari_kerja, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD')")
            ->orderBy('jadwal.jam_mulai')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();
    }
}
