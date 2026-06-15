<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import DialogTtvPasienRme from '@/components/rme/daftar-pasien/DialogTtvPasienRme.vue';
import FilterRujukanInternalRme from '@/components/rme/daftar-pasien/FilterRujukanInternalRme.vue';
import TabelRujukanInternalRme from '@/components/rme/daftar-pasien/TabelRujukanInternalRme.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import DialogPindahRawatInapPasien from '@/components/shared/pendaftaran/DialogPindahRawatInapPasien.vue';
import { simpanUrlListRme } from '@/composables/useRmeListNavigation';
import { cppt as pemeriksaanCppt } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import { index as rujukanInternalIndex } from '@/routes/rme/rujukan-internal';
import type { FilterKunjunganRme as FilterRme, PaginatedRme, PasienDialogTtvRme, PasienRujukanInternalRme, RegisteredPatientRow, RmeOption } from '@/types';

const props = defineProps<{
    filters: FilterRme;
    clinics: RmeOption[];
    patients: PaginatedRme<PasienRujukanInternalRme>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Rujukan Internal', href: rujukanInternalIndex() },
        ],
    },
});

const isFilterLoading = ref(false);
const selectedPatient = ref<RegisteredPatientRow | null>(null);
const selectedTtvPatient = ref<PasienDialogTtvRme | null>(null);
const pindahRawatInapDialogOpen = ref(false);
const ttvDialogOpen = ref(false);
const total = computed(() => props.patients.total);

onMounted(() => {
    simpanUrlListRme('rujukan-internal');
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
        rujukanInternalIndex.url(),
        {
            tgl_awal: filters.tgl_awal,
            tgl_akhir: filters.tgl_akhir,
            kd_poli_asal: filters.kd_poli_asal || undefined,
            kd_poli: filters.kd_poli || undefined,
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

function pasienTerdaftar(patient: PasienRujukanInternalRme): RegisteredPatientRow {
    return {
        no_reg: '-',
        no_rawat: patient.no_rawat,
        tgl_registrasi: patient.tgl_registrasi,
        jam_reg: patient.jam_reg,
        kd_dokter: patient.kd_dokter_tujuan,
        no_rkm_medis: patient.no_rkm_medis,
        kd_poli: patient.kd_poli_tujuan,
        nm_poli: patient.nm_poli_tujuan,
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
        no_tlp: null,
        no_peserta: patient.no_peserta,
        no_ktp: patient.no_ktp,
        tgl_lahir: patient.tgl_lahir,
        png_jawab: patient.png_jawab,
        nm_dokter: patient.nm_dokter_tujuan,
        perujuk: patient.nm_dokter_awal,
        kategori_rujuk: 'Internal',
        no_sep: null,
        tgl_sep: null,
        klsrawat: null,
        diagawal: null,
        nmdiagnosaawal: null,
        diagnosa_ranap_awal: null,
        tgl_masuk_ranap: null,
        is_ranap: patient.is_ranap === true || patient.is_ranap === 1,
        is_mjkn: false,
    };
}

function bukaDialogPindahRawatInap(patient: PasienRujukanInternalRme): void {
    selectedPatient.value = pasienTerdaftar(patient);
    pindahRawatInapDialogOpen.value = true;
}

function bukaDialogTtv(patient: PasienRujukanInternalRme): void {
    selectedTtvPatient.value = patient;
    ttvDialogOpen.value = true;
}

function bukaPemeriksaan(patient: PasienRujukanInternalRme): void {
    simpanUrlListRme('rujukan-internal');

    router.get(pemeriksaanCppt.url({
        query: {
            no_rawat: patient.no_rawat,
            fr: 'rp',
        },
    }));
}
</script>

<template>
    <Head title="RME Rujukan Internal" />

    <div class="contents">
        <PageHeader
            title="RME Rujukan Internal"
            description="Daftar pasien yang dirujuk antar poli atau unit internal."
        >
            <template #actions>
                <div class="text-right">
                    <p class="text-3xl font-bold leading-none text-primary">{{ total }}</p>
                    <p class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                        Rujukan
                    </p>
                </div>
            </template>
        </PageHeader>

        <div class="flex min-w-0 flex-col gap-3">
            <FilterRujukanInternalRme
                :filters="filters"
                :clinics="clinics"
                search-placeholder="Cari no rawat, no RM, nama pasien, poli asal/tujuan, dokter..."
                :processing="isFilterLoading"
                @terapkan="terapkanFilter"
                @bersihkan="terapkanFilter"
            />

            <TabelRujukanInternalRme
                :patients="patients"
                :loading="isFilterLoading"
                :order="filters.order === 'desc' ? 'desc' : 'asc'"
                @ubah-order="terapkanFilter({ ...filters, order: $event })"
                @periksa="bukaPemeriksaan"
                @pindah-rawat-inap="bukaDialogPindahRawatInap"
                @ttv="bukaDialogTtv"
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
