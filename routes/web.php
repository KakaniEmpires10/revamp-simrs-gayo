<?php

use App\Http\Controllers\ManajemenPegawai\AkunDokterController;
use App\Http\Controllers\ManajemenPegawai\AkunPetugasController;
use App\Http\Controllers\ManajemenPegawai\JadwalPraktekController;
use App\Http\Controllers\ManajemenUser\AksesUserController;
use App\Http\Controllers\ManajemenUser\GrupUserController;
use App\Http\Controllers\ManajemenUser\IzinGrupController;
use App\Http\Controllers\Pendaftaran\CetakBerkasPasienController;
use App\Http\Controllers\Pendaftaran\DaftarPasienController;
use App\Http\Controllers\Pendaftaran\DataPasienController;
use App\Http\Controllers\Rme\DaftarPasienRmeController;
use App\Http\Controllers\Rme\PemeriksaanPasienController;
use App\Modules\Bpjs\Http\Controllers\BpjsDashboardController;
use App\Modules\Bpjs\Http\Controllers\VClaimController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::inertia('/', 'Dashboard')->name('dashboard');

    Route::redirect('dashboard', '/');

    Route::prefix('manajemen-user')->name('manajemen-user.')->group(function () {
        Route::redirect('/', '/manajemen-user/grup-user');

        Route::get('grup-user', [GrupUserController::class, 'index'])->name('grup-user.index');
        Route::post('grup-user', [GrupUserController::class, 'store'])->name('grup-user.store');
        Route::patch('grup-user/{authGroup}', [GrupUserController::class, 'update'])->name('grup-user.update');
        Route::delete('grup-user/{authGroup}', [GrupUserController::class, 'destroy'])->name('grup-user.destroy');

        Route::get('grup-user/{authGroup}/izin', [IzinGrupController::class, 'edit'])->name('izin-grup.edit');
        Route::patch('grup-user/{authGroup}/izin', [IzinGrupController::class, 'update'])->name('izin-grup.update');

        Route::get('akses-user', [AksesUserController::class, 'index'])->name('akses-user.index');
        Route::patch('akses-user/{idUser}', [AksesUserController::class, 'update'])->name('akses-user.update');
        Route::delete('akses-user/{idUser}', [AksesUserController::class, 'destroy'])->name('akses-user.destroy');
    });

    Route::prefix('manajemen-pegawai')->name('manajemen-pegawai.')->group(function () {
        Route::redirect('/', '/manajemen-pegawai/akun-dokter');

        Route::get('akun-dokter', [AkunDokterController::class, 'index'])->name('akun-dokter.index');
        Route::post('akun-dokter', [AkunDokterController::class, 'store'])->name('akun-dokter.store');
        Route::post('akun-dokter/profil', [AkunDokterController::class, 'storeProfile'])->name('akun-dokter.profil.store');
        Route::patch('akun-dokter/{doctor}/status', [AkunDokterController::class, 'updateStatus'])->name('akun-dokter.status.update');
        Route::patch('akun-dokter/{doctor}/profil', [AkunDokterController::class, 'updateProfile'])->name('akun-dokter.profil.update');
        Route::delete('akun-dokter/{doctor}', [AkunDokterController::class, 'destroy'])->name('akun-dokter.destroy');

        Route::get('akun-petugas', [AkunPetugasController::class, 'index'])->name('akun-petugas.index');
        Route::post('akun-petugas', [AkunPetugasController::class, 'store'])->name('akun-petugas.store');
        Route::post('akun-petugas/profil', [AkunPetugasController::class, 'storeProfile'])->name('akun-petugas.profil.store');
        Route::patch('akun-petugas/{staff}/status', [AkunPetugasController::class, 'updateStatus'])->name('akun-petugas.status.update');
        Route::patch('akun-petugas/{staff}/profil', [AkunPetugasController::class, 'updateProfile'])->name('akun-petugas.profil.update');
        Route::delete('akun-petugas/{staff}', [AkunPetugasController::class, 'destroy'])->name('akun-petugas.destroy');

        Route::get('jadwal-praktek', [JadwalPraktekController::class, 'index'])->name('jadwal-praktek.index');
        Route::post('jadwal-praktek', [JadwalPraktekController::class, 'store'])->name('jadwal-praktek.store');
        Route::patch('jadwal-praktek/{doctor}/{day}/{start}', [JadwalPraktekController::class, 'update'])->name('jadwal-praktek.update');
        Route::delete('jadwal-praktek/{doctor}/{day}/{start}', [JadwalPraktekController::class, 'destroy'])->name('jadwal-praktek.destroy');
    });

    Route::prefix('pendaftaran')->name('pendaftaran.')->group(function () {
        Route::redirect('/', '/pendaftaran/daftar-pasien');

        Route::get('daftar-pasien', [DaftarPasienController::class, 'index'])->name('daftar-pasien.index');
        Route::post('daftar-pasien', [DaftarPasienController::class, 'store'])->name('daftar-pasien.store');
        Route::post('daftar-pasien/{noRawat}/rujuk-internal', [DaftarPasienController::class, 'storeInternalReferral'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.rujuk-internal.store');
        Route::post('daftar-pasien/{noRawat}/pindah-rawat-inap', [DaftarPasienController::class, 'storeInpatientTransfer'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.pindah-rawat-inap.store');
        Route::get('daftar-pasien/{noRawat}/cetak/no-antrean-poli', [CetakBerkasPasienController::class, 'noAntreanPoli'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.cetak.no-antrean-poli');
        Route::get('daftar-pasien/{noRawat}/cetak/label-gelang', [CetakBerkasPasienController::class, 'labelGelang'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.cetak.label-gelang');
        Route::get('daftar-pasien/{noRawat}/cetak/gelang-pasien', [CetakBerkasPasienController::class, 'gelangPasien'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.cetak.gelang-pasien');
        Route::get('daftar-pasien/cari-pasien', [DaftarPasienController::class, 'search'])->name('daftar-pasien.search');
        Route::get('daftar-pasien/referensi', [DaftarPasienController::class, 'reference'])->name('daftar-pasien.reference');
        Route::patch('daftar-pasien/{noRawat}/batal', [DaftarPasienController::class, 'cancel'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.cancel');
        Route::patch('daftar-pasien/{noRawat}', [DaftarPasienController::class, 'update'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.update');
        Route::delete('daftar-pasien/{noRawat}', [DaftarPasienController::class, 'destroy'])
            ->where('noRawat', '.*')
            ->name('daftar-pasien.destroy');

        Route::get('data-pasien', [DataPasienController::class, 'index'])->name('data-pasien.index');
        Route::get('data-pasien/cari-pasien', [DataPasienController::class, 'search'])->name('data-pasien.search');
        Route::get('data-pasien/autofill-bpjs', [DataPasienController::class, 'bpjsAutofill'])->name('data-pasien.bpjs-autofill');
        Route::get('data-pasien/wilayah', [DataPasienController::class, 'wilayahSearch'])->name('data-pasien.wilayah.search');
        Route::get('data-pasien/tambah', [DataPasienController::class, 'create'])->name('data-pasien.create');
        Route::post('data-pasien', [DataPasienController::class, 'store'])->name('data-pasien.store');
        Route::post('data-pasien/gabung-rm', [DataPasienController::class, 'mergeMedicalRecord'])->name('data-pasien.gabung-rm.store');
        Route::get('data-pasien/{noRkmMedis}/edit', [DataPasienController::class, 'edit'])->name('data-pasien.edit');
        Route::patch('data-pasien/{noRkmMedis}', [DataPasienController::class, 'update'])->name('data-pasien.update');
        Route::delete('data-pasien/{noRkmMedis}', [DataPasienController::class, 'destroy'])->name('data-pasien.destroy');
    });

    Route::prefix('rme')->name('rme.')->group(function () {
        Route::redirect('/', '/rme/rawat-jalan');

        Route::get('rawat-jalan', [DaftarPasienRmeController::class, 'rawatJalan'])->name('rawat-jalan.index');
        Route::get('rawat-inap', [DaftarPasienRmeController::class, 'rawatInap'])->name('rawat-inap.index');
        Route::patch('rawat-inap/dpjp', [DaftarPasienRmeController::class, 'updateDpjpRanap'])->name('rawat-inap.dpjp.update');
        Route::post('rawat-inap/pindah-kamar', [DaftarPasienRmeController::class, 'pindahKamarRanap'])->name('rawat-inap.pindah-kamar.store');
        Route::delete('rawat-inap/batal', [DaftarPasienRmeController::class, 'batalRanap'])->name('rawat-inap.batal.destroy');
        Route::post('rawat-inap/pulang', [DaftarPasienRmeController::class, 'pulangkanRanap'])->name('rawat-inap.pulang.store');
        Route::patch('rawat-inap/pulang', [DaftarPasienRmeController::class, 'updatePulangRanap'])->name('rawat-inap.pulang.update');
        Route::patch('rawat-inap/status-pulang', [DaftarPasienRmeController::class, 'updateStatusPulangRanap'])->name('rawat-inap.status-pulang.update');
        Route::get('ttv-pasien', [DaftarPasienRmeController::class, 'ttvPasien'])->name('ttv-pasien.show');
        Route::put('ttv-pasien', [DaftarPasienRmeController::class, 'simpanTtvPasien'])->name('ttv-pasien.update');
        Route::get('igd', [DaftarPasienRmeController::class, 'igd'])->name('igd.index');
        Route::patch('igd/dokter', [DaftarPasienRmeController::class, 'updateDokterIgd'])->name('igd.dokter.update');
        Route::patch('igd/diagnosa', [DaftarPasienRmeController::class, 'updateDiagnosaIgd'])->name('igd.diagnosa.update');
        Route::get('rujukan-internal', [DaftarPasienRmeController::class, 'rujukanInternal'])->name('rujukan-internal.index');

        Route::prefix('pemeriksaan')->name('pemeriksaan.')->group(function () {
            Route::get('/', [PemeriksaanPasienController::class, 'index'])->name('index');
            Route::get('cppt', [PemeriksaanPasienController::class, 'cppt'])->name('cppt');
            Route::post('cppt', [PemeriksaanPasienController::class, 'storeCppt'])->name('cppt.store');
            Route::patch('cppt', [PemeriksaanPasienController::class, 'updateCppt'])->name('cppt.update');
            Route::delete('cppt', [PemeriksaanPasienController::class, 'destroyCppt'])->name('cppt.destroy');
            Route::get('riwayat', [PemeriksaanPasienController::class, 'riwayat'])->name('riwayat');
            Route::get('{menu}', [PemeriksaanPasienController::class, 'placeholder'])->name('placeholder');
        });
    });

    Route::prefix('integrasi-eksternal')->name('integrasi-eksternal.')->group(function () {
        Route::get('bpjs', [BpjsDashboardController::class, 'index'])->name('bpjs.index');

        // vclaim
        Route::get('bpjs/cek-koneksi-bpjs', [VClaimController::class, 'connection'])->name('bpjs.vclaim.connection');
        Route::get('bpjs/vclaim-monitoring-data-kunjungan', [VClaimController::class, 'monitoringVisits'])->name('bpjs.vclaim.monitoring-visits');
        Route::get('bpjs/cek-rujukan-noka', [VClaimController::class, 'referralCheck'])->name('bpjs.vclaim.referral-check');
        Route::get('bpjs/list-rujukan-keluar', [VClaimController::class, 'outboundReferrals'])->name('bpjs.vclaim.outbound-referrals');
        Route::post('bpjs/list-rujukan-keluar', [VClaimController::class, 'storeOutboundReferral'])->name('bpjs.vclaim.outbound-referrals.store');
        Route::get('bpjs/list-rujukan-keluar/{noRujukan}/detail', [VClaimController::class, 'outboundReferralDetail'])->name('bpjs.vclaim.outbound-referrals.detail');
        Route::patch('bpjs/list-rujukan-keluar/{noRujukan}', [VClaimController::class, 'updateOutboundReferral'])->name('bpjs.vclaim.outbound-referrals.update');
        Route::delete('bpjs/list-rujukan-keluar/{noRujukan}', [VClaimController::class, 'destroyOutboundReferral'])->name('bpjs.vclaim.outbound-referrals.destroy');
        Route::get('bpjs/data-prb', [VClaimController::class, 'prbData'])->name('bpjs.vclaim.prb-data');
        Route::get('bpjs/list-persetujuan-sep', [VClaimController::class, 'sepApprovals'])->name('bpjs.vclaim.sep-approvals');
        Route::get('bpjs/list-finger-print', [VClaimController::class, 'fingerprints'])->name('bpjs.vclaim.fingerprints');
        Route::get('bpjs/monitoring-klaim', [VClaimController::class, 'claimMonitoring'])->name('bpjs.vclaim.claim-monitoring');
        Route::post('bpjs/vclaim-monitoring-data-kunjungan/tarik-sep', [VClaimController::class, 'pullSep'])->name('bpjs.vclaim.monitoring-visits.pull-sep');
        Route::delete('bpjs/vclaim-monitoring-data-kunjungan/{noSep}', [VClaimController::class, 'destroySep'])->name('bpjs.vclaim.monitoring-visits.destroy-sep');
        Route::get('bpjs/vclaim-rencana-kontrol', [VClaimController::class, 'controlPlans'])->name('bpjs.vclaim.control-plans');
        Route::get('bpjs/vclaim-rencana-kontrol/referensi-poli', [VClaimController::class, 'controlPlanSpecialists'])->name('bpjs.vclaim.control-plans.specialists');
        Route::get('bpjs/vclaim-rencana-kontrol/referensi-dokter', [VClaimController::class, 'controlPlanDoctors'])->name('bpjs.vclaim.control-plans.doctors');
        Route::get('bpjs/vclaim-rencana-kontrol/sep/{noSep}/detail', [VClaimController::class, 'controlPlanBySepDetail'])->name('bpjs.vclaim.control-plans.sep.detail');
        Route::get('bpjs/vclaim-rencana-kontrol/{noSurat}/detail', [VClaimController::class, 'controlPlanDetail'])->name('bpjs.vclaim.control-plans.detail');
        Route::post('bpjs/vclaim-rencana-kontrol', [VClaimController::class, 'storeControlPlan'])->name('bpjs.vclaim.control-plans.store');
        Route::patch('bpjs/vclaim-rencana-kontrol/{noSurat}', [VClaimController::class, 'updateControlPlan'])->name('bpjs.vclaim.control-plans.update');
        Route::delete('bpjs/vclaim-rencana-kontrol/{noSurat}', [VClaimController::class, 'destroyControlPlan'])->name('bpjs.vclaim.control-plans.destroy');
        Route::get('bpjs/vclaim-rencana-rawat-inap', [VClaimController::class, 'inpatientPlans'])->name('bpjs.vclaim.inpatient-plans');
        Route::get('bpjs/vclaim-rencana-rawat-inap/referensi-poli', [VClaimController::class, 'inpatientPlanSpecialists'])->name('bpjs.vclaim.inpatient-plans.specialists');
        Route::get('bpjs/vclaim-rencana-rawat-inap/referensi-dokter', [VClaimController::class, 'inpatientPlanDoctors'])->name('bpjs.vclaim.inpatient-plans.doctors');
        Route::get('bpjs/vclaim-rencana-rawat-inap/{noSurat}/detail', [VClaimController::class, 'inpatientPlanDetail'])->name('bpjs.vclaim.inpatient-plans.detail');
        Route::post('bpjs/vclaim-rencana-rawat-inap/{noRawat}/spri', [VClaimController::class, 'storeRegistrationInpatientPlan'])
            ->where('noRawat', '.*')
            ->name('bpjs.vclaim.inpatient-plans.registration.store');
        Route::post('bpjs/vclaim-rencana-rawat-inap', [VClaimController::class, 'storeInpatientPlan'])->name('bpjs.vclaim.inpatient-plans.store');
        Route::patch('bpjs/vclaim-rencana-rawat-inap/{noSurat}', [VClaimController::class, 'updateInpatientPlan'])->name('bpjs.vclaim.inpatient-plans.update');
        Route::delete('bpjs/vclaim-rencana-rawat-inap/{noSurat}', [VClaimController::class, 'destroyInpatientPlan'])->name('bpjs.vclaim.inpatient-plans.destroy');

        // referensi vclaim
        Route::get('bpjs/referensi/diagnosa', [VClaimController::class, 'diagnosisReferences'])->name('bpjs.vclaim.references.diagnosis');
        Route::get('bpjs/referensi/poli', [VClaimController::class, 'specialistReferences'])->name('bpjs.vclaim.references.specialists');
        Route::get('bpjs/referensi/faskes', [VClaimController::class, 'providerReferences'])->name('bpjs.vclaim.references.providers');

        Route::get('bpjs/{menu}', [BpjsDashboardController::class, 'show'])->name('bpjs.submenu.show');

    });
});

require __DIR__.'/settings.php';
