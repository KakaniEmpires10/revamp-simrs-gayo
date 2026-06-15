<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw, TriangleAlert } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import DialogEditDpjpRanap from '@/components/rme/daftar-pasien/DialogEditDpjpRanap.vue';
import DialogPindahKamarRanap from '@/components/rme/daftar-pasien/DialogPindahKamarRanap.vue';
import DialogPulangRanap from '@/components/rme/daftar-pasien/DialogPulangRanap.vue';
import DialogTtvPasienRme from '@/components/rme/daftar-pasien/DialogTtvPasienRme.vue';
import FilterRawatInapRme from '@/components/rme/daftar-pasien/FilterRawatInapRme.vue';
import TabelPasienRanapRme from '@/components/rme/daftar-pasien/TabelPasienRanapRme.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { batalRanap, updateStatusPulangRanap } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { simpanUrlListRme } from '@/composables/useRmeListNavigation';
import { cppt as pemeriksaanCppt } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import { index as rawatInapIndex } from '@/routes/rme/rawat-inap';
import type { FilterKunjunganRme as FilterRme, PaginatedRme, PasienDialogTtvRme, PasienRanapRme, RmeOption } from '@/types';

const props = defineProps<{
    filters: FilterRme;
    rooms: RmeOption[];
    doctors: RmeOption[];
    availableRooms: (RmeOption & { description?: string; kelas?: string | null; tarif?: number; status?: string | null })[];
    dischargeStatuses: RmeOption[];
    patients: PaginatedRme<PasienRanapRme>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Rawat Inap', href: rawatInapIndex() },
        ],
    },
});

const isFilterLoading = ref(false);
const selectedPatient = ref<PasienRanapRme | null>(null);
const selectedTtvPatient = ref<PasienDialogTtvRme | null>(null);
const editDpjpDialogOpen = ref(false);
const pindahKamarDialogOpen = ref(false);
const pulangDialogOpen = ref(false);
const pulangDialogMode = ref<'create' | 'edit'>('create');
const ttvDialogOpen = ref(false);
const total = computed(() => props.patients.total);
const { openDeleteDialog } = useDeleteDialog();

onMounted(() => {
    simpanUrlListRme('rawat-inap');
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
        rawatInapIndex.url(),
        {
            tgl_awal: filters.tgl_awal,
            tgl_akhir: filters.tgl_akhir,
            kd_bangsal: filters.kd_bangsal || undefined,
            tipe_filter_ranap: filters.tipe_filter_ranap || undefined,
            search: filters.search || undefined,
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

function bukaDialogEditDpjp(patient: PasienRanapRme): void {
    selectedPatient.value = patient;
    editDpjpDialogOpen.value = true;
}

function bukaDialogPindahKamar(patient: PasienRanapRme): void {
    selectedPatient.value = patient;
    pindahKamarDialogOpen.value = true;
}

function bukaDialogPulangkan(patient: PasienRanapRme): void {
    selectedPatient.value = patient;
    pulangDialogMode.value = 'create';
    pulangDialogOpen.value = true;
}

function bukaDialogEditPulang(patient: PasienRanapRme): void {
    selectedPatient.value = patient;
    pulangDialogMode.value = 'edit';
    pulangDialogOpen.value = true;
}

function bukaDialogTtv(patient: PasienRanapRme): void {
    selectedTtvPatient.value = patient;
    ttvDialogOpen.value = true;
}

function ubahStatusPulang(patient: PasienRanapRme, status: string): void {
    router.patch(updateStatusPulangRanap.url(), {
        no_rawat: patient.no_rawat,
        stts_pulang: status,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['patients', 'filters', 'availableRooms']),
        reset: ['patients'],
    });
}

function bukaDialogBatalRanap(patient: PasienRanapRme): void {
    openDeleteDialog({
        level: 'warning',
        icon: TriangleAlert,
        actionIcon: RotateCcw,
        title: 'Batalkan rawat inap?',
        description: `Rawat inap ${patient.no_rawat} milik ${patient.nm_pasien} akan dibatalkan, data kamar dan DPJP ranap dilepas, lalu pendaftaran kembali ke rawat jalan.`,
        actionLabel: 'Batalkan Ranap',
        cancelLabel: 'Tutup',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;
                let berhasil = false;

                router.delete(batalRanap.url(), {
                    data: { no_rawat: patient.no_rawat },
                    preserveScroll: true,
                    preserveState: true,
                    only: feedbackOnly(['patients', 'filters', 'availableRooms']),
                    reset: ['patients'],
                    onSuccess: (page) => {
                        berhasil = isFeedbackSuccess(page);
                    },
                    onError: () => {
                        hasError = true;
                        reject();
                    },
                    onFinish: () => {
                        if (berhasil) {
                            resolve();
                        } else if (!hasError) {
                            reject();
                        }
                    },
                });
            }),
    });
}

function bukaPemeriksaan(patient: PasienRanapRme): void {
    simpanUrlListRme('rawat-inap');

    router.get(pemeriksaanCppt.url({
        query: {
            no_rawat: patient.no_rawat,
            fr: 'ri',
        },
    }));
}
</script>

<template>
    <Head title="RME Rawat Inap" />

    <div class="contents">
        <PageHeader
            title="RME Rawat Inap"
            description="Daftar pasien rawat inap berdasarkan tanggal masuk dan ruang perawatan."
        >
            <template #actions>
                <div class="text-right">
                    <p class="text-3xl font-bold leading-none text-primary">{{ total }}</p>
                    <p class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                        Pasien Ranap
                    </p>
                </div>
            </template>
        </PageHeader>

        <div class="flex min-w-0 flex-col gap-3">
            <FilterRawatInapRme
                :filters="filters"
                :rooms="rooms"
                search-placeholder="Cari no rawat, no RM, nama pasien, ruang, dokter, diagnosa..."
                :processing="isFilterLoading"
                @terapkan="terapkanFilter"
                @bersihkan="terapkanFilter"
            />

            <TabelPasienRanapRme
                :patients="patients"
                :loading="isFilterLoading"
                :discharge-statuses="dischargeStatuses"
                @periksa="bukaPemeriksaan"
                @edit-dpjp="bukaDialogEditDpjp"
                @pindah-kamar="bukaDialogPindahKamar"
                @batal-ranap="bukaDialogBatalRanap"
                @pulangkan="bukaDialogPulangkan"
                @edit-pulang="bukaDialogEditPulang"
                @ubah-status-pulang="ubahStatusPulang"
                @ttv="bukaDialogTtv"
            />

            <DialogEditDpjpRanap
                v-model:open="editDpjpDialogOpen"
                :patient="selectedPatient"
                :doctors="doctors"
            />

            <DialogPindahKamarRanap
                v-model:open="pindahKamarDialogOpen"
                :patient="selectedPatient"
                :available-rooms="availableRooms"
            />

            <DialogPulangRanap
                v-model:open="pulangDialogOpen"
                :patient="selectedPatient"
                :statuses="dischargeStatuses"
                :mode="pulangDialogMode"
            />

            <DialogTtvPasienRme
                v-model:open="ttvDialogOpen"
                :patient="selectedTtvPatient"
            />
        </div>
    </div>
</template>
