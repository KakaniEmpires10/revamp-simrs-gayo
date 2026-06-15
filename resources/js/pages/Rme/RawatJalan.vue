<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import DialogTtvPasienRme from '@/components/rme/daftar-pasien/DialogTtvPasienRme.vue';
import FilterRawatJalanRme from '@/components/rme/daftar-pasien/FilterRawatJalanRme.vue';
import TabelPasienRalanRme from '@/components/rme/daftar-pasien/TabelPasienRalanRme.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import DialogPindahRawatInapPasien from '@/components/shared/pendaftaran/DialogPindahRawatInapPasien.vue';
import DialogRujukInternalPasien from '@/components/shared/pendaftaran/DialogRujukInternalPasien.vue';
import { simpanUrlListRme } from '@/composables/useRmeListNavigation';
import { cppt as pemeriksaanCppt } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import type { FilterKunjunganRme as FilterRme, PaginatedRme, PasienDialogTtvRme, PasienRalanRme, RegisteredPatientRow, RmeOption } from '@/types';

const props = defineProps<{
    filters: FilterRme;
    clinics: RmeOption[];
    payments: RmeOption[];
    statuses: RmeOption[];
    patients: PaginatedRme<PasienRalanRme>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Rawat Jalan', href: rawatJalanIndex() },
        ],
    },
});

const isFilterLoading = ref(false);
const selectedPatient = ref<RegisteredPatientRow | null>(null);
const selectedTtvPatient = ref<PasienDialogTtvRme | null>(null);
const pindahRawatInapDialogOpen = ref(false);
const rujukInternalDialogOpen = ref(false);
const ttvDialogOpen = ref(false);
const total = computed(() => props.patients.total);
const igdClinicCode = 'IGDK';

onMounted(() => {
    simpanUrlListRme('rawat-jalan');
});

watch(
    () => props.filters,
    () => {
        isFilterLoading.value = false;
    },
);

function terapkanFilter(filters: FilterRme): void {
    isFilterLoading.value = true;

    router.get(
        rawatJalanIndex.url(),
        {
            tgl_awal: filters.tgl_awal,
            tgl_akhir: filters.tgl_akhir,
            kd_poli: filters.kd_poli || undefined,
            kd_pj: filters.kd_pj || undefined,
            status: filters.status || undefined,
            search: filters.search || undefined,
            order: filters.order === 'desc' ? 'desc' : undefined,
        },
        {
            only: ['filters', 'patients'],
            preserveScroll: true,
            preserveState: true,
            reset: ['patients'],
            onFinish: () => {
                isFilterLoading.value = false;
            },
        },
    );
}

function pasienTerdaftar(patient: PasienRalanRme): RegisteredPatientRow {
    return {
        no_reg: patient.no_reg,
        no_rawat: patient.no_rawat,
        tgl_registrasi: patient.tgl_registrasi,
        jam_reg: patient.jam_reg,
        kd_dokter: patient.kd_dokter,
        no_rkm_medis: patient.no_rkm_medis,
        kd_poli: patient.kd_poli,
        nm_poli: patient.nm_poli,
        p_jawab: '-',
        almt_pj: '-',
        hubunganpj: '-',
        stts: patient.stts,
        stts_daftar: patient.stts_daftar,
        status_lanjut: patient.status_lanjut,
        status_bayar: patient.status_bayar,
        kd_pj: patient.kd_pj,
        nm_pasien: patient.nm_pasien,
        jk: patient.jk,
        no_tlp: patient.no_tlp,
        no_peserta: patient.no_peserta,
        no_ktp: patient.no_ktp,
        tgl_lahir: patient.tgl_lahir,
        png_jawab: patient.png_jawab,
        nm_dokter: patient.nm_dokter,
        perujuk: null,
        kategori_rujuk: null,
        no_sep: patient.no_sep,
        tgl_sep: patient.tglsep,
        klsrawat: patient.klsrawat,
        diagawal: patient.diagawal,
        nmdiagnosaawal: patient.nmdiagnosaawal,
        diagnosa_ranap_awal: null,
        tgl_masuk_ranap: null,
        is_ranap: patient.is_ranap === true || patient.is_ranap === 1,
        is_mjkn: false,
    };
}

function bukaDialogPindahRawatInap(patient: PasienRalanRme): void {
    selectedPatient.value = pasienTerdaftar(patient);
    pindahRawatInapDialogOpen.value = true;
}

function bukaDialogRujukInternal(patient: PasienRalanRme): void {
    selectedPatient.value = pasienTerdaftar(patient);
    rujukInternalDialogOpen.value = true;
}

function bukaDialogTtv(patient: PasienRalanRme): void {
    selectedTtvPatient.value = patient;
    ttvDialogOpen.value = true;
}

function bukaPemeriksaan(patient: PasienRalanRme): void {
    simpanUrlListRme('rawat-jalan');

    router.get(pemeriksaanCppt.url({
        query: {
            no_rawat: patient.no_rawat,
            fr: 'rj',
        },
    }));
}
</script>

<template>
    <Head title="RME Rawat Jalan" />

    <div class="contents">
        <PageHeader
            title="RME Rawat Jalan"
            description="Daftar pasien rawat jalan untuk akses rekam medis elektronik."
        >
            <template #actions>
                <div class="text-right">
                    <p class="text-3xl font-bold leading-none text-primary">{{ total }}</p>
                    <p class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                        Pasien Ralan
                    </p>
                </div>
            </template>
        </PageHeader>

        <div class="flex min-w-0 flex-col gap-3">
            <FilterRawatJalanRme
                :filters="filters"
                :options="clinics"
                :status-options="statuses"
                :payment-options="payments"
                option-key="kd_poli"
                option-label="Poliklinik"
                option-placeholder="Semua poliklinik"
                :processing="isFilterLoading"
                @terapkan="terapkanFilter"
                @bersihkan="terapkanFilter"
            />

            <TabelPasienRalanRme
                :patients="patients"
                :loading="isFilterLoading"
                :order="filters.order === 'desc' ? 'desc' : 'asc'"
                @ubah-order="terapkanFilter({ ...filters, order: $event })"
                @periksa="bukaPemeriksaan"
                @pindah-rawat-inap="bukaDialogPindahRawatInap"
                @rujuk-internal="bukaDialogRujukInternal"
                @ttv="bukaDialogTtv"
            />

            <DialogRujukInternalPasien
                v-model:open="rujukInternalDialogOpen"
                :patient="selectedPatient"
                :clinics="clinics"
                :igd-clinic-code="igdClinicCode"
                :reload-props="['patients', 'filters']"
                :reset-props="['patients']"
            />

            <DialogPindahRawatInapPasien
                v-model:open="pindahRawatInapDialogOpen"
                :patient="selectedPatient"
                :reload-props="['patients', 'filters']"
                :reset-props="['patients']"
            />

            <DialogTtvPasienRme
                v-model:open="ttvDialogOpen"
                :patient="selectedTtvPatient"
            />
        </div>
    </div>
</template>
