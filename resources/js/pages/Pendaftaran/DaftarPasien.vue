<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { RotateCcw, Table2, Trash2, TriangleAlert } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    cancel as batalPendaftaran,
    destroy as hapusPendaftaran,
    index as daftarPasienIndex,
} from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { index as dataPasienIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import DialogBuatSpriPasien from '@/components/pendaftaran/daftar-pasien/DialogBuatSpriPasien.vue';
import DialogEditPendaftaranPasien from '@/components/pendaftaran/daftar-pasien/DialogEditPendaftaranPasien.vue';
import DialogPendaftaranPasien from '@/components/pendaftaran/daftar-pasien/DialogPendaftaranPasien.vue';
import FilterPendaftaranPasien from '@/components/pendaftaran/daftar-pasien/FilterPendaftaranPasien.vue';
import JenisPendaftaranTabs from '@/components/pendaftaran/daftar-pasien/JenisPendaftaranTabs.vue';
import ModePencarianTabs from '@/components/pendaftaran/daftar-pasien/ModePencarianTabs.vue';
import PencarianPasien from '@/components/pendaftaran/daftar-pasien/PencarianPasien.vue';
import TabelPendaftaranPasien from '@/components/pendaftaran/daftar-pasien/TabelPendaftaranPasien.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import DialogPindahRawatInapPasien from '@/components/shared/pendaftaran/DialogPindahRawatInapPasien.vue';
import DialogRujukInternalPasien from '@/components/shared/pendaftaran/DialogRujukInternalPasien.vue';
import { Button } from '@/components/ui/button';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { tanggalHariIni } from '@/lib/pasien';
import { dashboard } from '@/routes';
import type {
    PaginatedResponse,
    PatientSearchMode,
    RegisteredPatientRow,
    RegistrationOption,
    RegistrationPatient,
    RegistrationTableFilters,
    RegistrationType,
} from '@/types';

const props = defineProps<{
    doctors: RegistrationOption[];
    clinics: RegistrationOption[];
    payments: RegistrationOption[];
    igdClinicCode: string;
    defaultPaymentCode: string;
    view: 'registration' | 'table';
    filters: RegistrationTableFilters;
    registeredPatients: PaginatedResponse<RegisteredPatientRow> | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Pendaftaran', href: daftarPasienIndex() },
            { title: 'Daftar Pasien', href: daftarPasienIndex() },
        ],
    },
});

const storageKey = 'simrs.pendaftaran.jenis';
const tableFilterStorageKey = 'simrs.pendaftaran.table.filters';
const registrationType = ref<RegistrationType>('rawat_jalan');
const searchMode = ref<PatientSearchMode>('nik');
const selectedPatient = ref<RegistrationPatient | null>(null);
const selectedRegisteredPatient = ref<RegisteredPatientRow | null>(null);
const dialogOpen = ref(false);
const editDialogOpen = ref(false);
const spriDialogOpen = ref(false);
const pindahRawatInapDialogOpen = ref(false);
const rujukInternalDialogOpen = ref(false);
const filterProcessing = ref(false);
const pencarianPasien = ref<InstanceType<typeof PencarianPasien> | null>(null);
const { openDeleteDialog } = useDeleteDialog();

if (typeof window !== 'undefined') {
    const storedType = window.localStorage.getItem(storageKey);

    if (props.view === 'table') {
        registrationType.value = props.filters.jenis_pendaftaran;
    } else if (storedType === 'rawat_jalan' || storedType === 'igd') {
        registrationType.value = storedType;
    }
}

watch(registrationType, (value) => {
    window.localStorage.setItem(storageKey, value);

    if (props.view === 'table') {
        openTable(value);
    }
});

watch(
    () => props.filters.jenis_pendaftaran,
    (value) => {
        if (registrationType.value !== value) {
            registrationType.value = value;
        }
    },
);

function openRegistration(patient: RegistrationPatient): void {
    selectedPatient.value = patient;
    dialogOpen.value = true;
}

function bukaDialogEditPendaftaran(patient: RegisteredPatientRow): void {
    selectedRegisteredPatient.value = patient;
    editDialogOpen.value = true;
}

function bukaDialogRujukInternal(patient: RegisteredPatientRow): void {
    selectedRegisteredPatient.value = patient;
    rujukInternalDialogOpen.value = true;
}

function bukaDialogPindahRawatInap(patient: RegisteredPatientRow): void {
    selectedRegisteredPatient.value = patient;
    pindahRawatInapDialogOpen.value = true;
}

function bukaDialogSpri(patient: RegisteredPatientRow): void {
    selectedRegisteredPatient.value = patient;
    spriDialogOpen.value = true;
}

function bersihkanPencarianSetelahDaftar(): void {
    selectedPatient.value = null;
    pencarianPasien.value?.clearSearch();
}

type StoredTableFilters = {
    disimpanPada: string;
    filters: Partial<RegistrationTableFilters>;
};

function storedTableFilters(): Partial<RegistrationTableFilters> {
    const rawFilters = window.localStorage.getItem(tableFilterStorageKey);

    if (!rawFilters) {
        return {};
    }

    try {
        const storedFilters = JSON.parse(rawFilters) as StoredTableFilters | Partial<RegistrationTableFilters>;

        if ('disimpanPada' in storedFilters && 'filters' in storedFilters) {
            if (storedFilters.disimpanPada !== tanggalHariIni()) {
                window.localStorage.removeItem(tableFilterStorageKey);

                return {};
            }

            return storedFilters.filters;
        }

        window.localStorage.removeItem(tableFilterStorageKey);

        return {};
    } catch {
        window.localStorage.removeItem(tableFilterStorageKey);

        return {};
    }
}

function openTable(type = registrationType.value): void {
    const savedFilters = storedTableFilters();

    router.get(
        daftarPasienIndex.url(),
        {
            view: 'table',
            jenis_pendaftaran: type,
            tgl_awal: savedFilters.tgl_awal || tanggalHariIni(),
            tgl_akhir: savedFilters.tgl_akhir || tanggalHariIni(),
            kd_poli: type === 'igd' ? undefined : (savedFilters.kd_poli || undefined),
            search: savedFilters.search || undefined,
        },
        {
            preserveScroll: true,
            preserveState: false,
            // reset: ['registeredPatients'],
        },
    );
}

function closeTable(): void {
    router.get(
        daftarPasienIndex.url(),
        {},
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
}

function simpanFilterPendaftaran(filters: RegistrationTableFilters): void {
    window.localStorage.setItem(tableFilterStorageKey, JSON.stringify({
        disimpanPada: tanggalHariIni(),
        filters,
    }));
}

function terapkanFilterPendaftaran(filters: RegistrationTableFilters): void {
    simpanFilterPendaftaran(filters);
    filterProcessing.value = true;

    router.get(
        daftarPasienIndex.url(),
        {
            view: 'table',
            jenis_pendaftaran: filters.jenis_pendaftaran,
            tgl_awal: filters.tgl_awal,
            tgl_akhir: filters.tgl_akhir,
            kd_poli: filters.kd_poli || undefined,
            search: filters.search || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['registeredPatients', 'filters', 'view'],
            reset: ['registeredPatients'],
            onFinish: () => {
                filterProcessing.value = false;
            },
        },
    );
}

function bersihkanFilterPendaftaran(filters: RegistrationTableFilters): void {
    window.localStorage.removeItem(tableFilterStorageKey);
    filterProcessing.value = true;

    router.get(
        daftarPasienIndex.url(),
        {
            view: 'table',
            jenis_pendaftaran: filters.jenis_pendaftaran,
            tgl_awal: tanggalHariIni(),
            tgl_akhir: tanggalHariIni(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['registeredPatients', 'filters', 'view'],
            reset: ['registeredPatients'],
            onFinish: () => {
                filterProcessing.value = false;
            },
        },
    );
}

function bukaDialogHapusPendaftaran(patient: RegisteredPatientRow): void {
    openDeleteDialog({
        level: 'danger',
        icon: TriangleAlert,
        actionIcon: Trash2,
        title: 'Hapus pendaftaran?',
        description: `Pendaftaran ${patient.no_rawat} milik ${patient.nm_pasien} akan dihapus jika belum memiliki data pelayanan terkait.`,
        actionLabel: 'Hapus Pendaftaran',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;
                let berhasil = false;

                router.delete(hapusPendaftaran.url(patient.no_rawat), {
                    preserveScroll: true,
                    preserveState: true,
                    only: feedbackOnly(['registeredPatients', 'filters', 'view']),
                    reset: ['registeredPatients'],
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

function bukaDialogBatalPendaftaran(patient: RegisteredPatientRow): void {
    const sedangBatal = patient.stts === 'Batal';

    openDeleteDialog({
        level: sedangBatal ? 'info' : 'warning',
        icon: TriangleAlert,
        actionIcon: sedangBatal ? RotateCcw : TriangleAlert,
        title: sedangBatal ? 'Aktifkan kembali pendaftaran?' : 'Batalkan pendaftaran?',
        description: sedangBatal
            ? `Pendaftaran ${patient.no_rawat} milik ${patient.nm_pasien} akan diaktifkan kembali agar bisa diproses seperti pendaftaran biasa.`
            : `Pendaftaran ${patient.no_rawat} milik ${patient.nm_pasien} akan tetap tersimpan, tetapi status pendaftarannya diubah menjadi Batal.`,
        actionLabel: sedangBatal ? 'Aktifkan Kembali' : 'Batalkan Pendaftaran',
        cancelLabel: 'Tutup',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;
                let berhasil = false;

                router.patch(batalPendaftaran.url(patient.no_rawat), {}, {
                    preserveScroll: true,
                    preserveState: true,
                    only: feedbackOnly(['registeredPatients', 'filters', 'view']),
                    reset: ['registeredPatients'],
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
</script>

<template>
    <Head title="Daftar Pasien" />

    <div class="contents">
        <PageHeader
            :title="view === 'table' ? 'Data Pendaftaran' : 'Daftar Pasien'"
            :description="view === 'table'
                ? 'List pasien terdaftar berdasarkan filter rawat jalan atau IGD.'
                : 'Pendaftaran umum rawat jalan dan IGD berbasis data pasien lokal.'"
        >
            <template #actions>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <JenisPendaftaranTabs v-model="registrationType" />
                    <Button v-if="view !== 'table'" variant="outline" size="sm" @click="openTable()">
                        <Table2 class="size-4" />
                        List Pasien Terdaftar
                    </Button>
                    <Button v-else variant="outline" size="sm" @click="closeTable">
                        <ArrowLeft class="size-4" />
                        Pendaftaran
                    </Button>
                </div>
            </template>
        </PageHeader>

        <section v-if="view === 'table' && registeredPatients" class="grid w-full min-w-0 max-w-full gap-4 overflow-x-hidden">
            <FilterPendaftaranPasien
                :filters="filters"
                :clinics="clinics.filter((clinic) => clinic.value !== igdClinicCode)"
                :registration-type="registrationType"
                :processing="filterProcessing"
                @terapkan="terapkanFilterPendaftaran"
                @bersihkan="bersihkanFilterPendaftaran"
            />

            <TabelPendaftaranPasien
                :patients="registeredPatients"
                :loading="filterProcessing"
                @edit="bukaDialogEditPendaftaran"
                @hapus="bukaDialogHapusPendaftaran"
                @batal="bukaDialogBatalPendaftaran"
                @spri="bukaDialogSpri"
                @pindah-rawat-inap="bukaDialogPindahRawatInap"
                @rujuk-internal="bukaDialogRujukInternal"
            />
        </section>

        <section v-else class="relative mx-auto grid min-h-[calc(100vh-15rem)] w-full max-w-3xl min-w-0 content-start gap-5 overflow-hidden py-8">
            <div class="pointer-events-none absolute inset-0 -z-10 opacity-70">
                <svg class="h-full w-full" viewBox="0 0 760 420" fill="none" aria-hidden="true">
                    <path d="M50 260 C180 150 270 330 420 210 C520 130 610 170 720 90" stroke="currentColor" class="text-primary/15" stroke-width="2" />
                    <path d="M80 110 H240 V250 H380 V150 H560" stroke="currentColor" class="text-info/15" stroke-width="2" />
                    <circle cx="610" cy="260" r="82" stroke="currentColor" class="text-success/15" stroke-width="2" />
                    <path d="M610 220 V300 M570 260 H650" stroke="currentColor" class="text-success/20" stroke-width="8" stroke-linecap="round" />
                    <path d="M120 340 H660" stroke="currentColor" class="text-border" stroke-dasharray="8 10" />
                </svg>
            </div>

            <div class="border-border/70 bg-background/90 grid gap-3 rounded-lg border p-4 shadow-sm backdrop-blur">
                <ModePencarianTabs v-model="searchMode" />
                <PencarianPasien
                    ref="pencarianPasien"
                    :mode="searchMode"
                    :create-href="dataPasienIndex.url({ query: { create: 1 } })"
                    @select="openRegistration"
                />
            </div>
        </section>

        <DialogPendaftaranPasien
            v-model:open="dialogOpen"
            :patient="selectedPatient"
            :registration-type="registrationType"
            :doctors="props.doctors"
            :clinics="props.clinics"
            :payments="props.payments"
            :igd-clinic-code="props.igdClinicCode"
            :default-payment-code="props.defaultPaymentCode"
            @registered="bersihkanPencarianSetelahDaftar"
        />

        <DialogEditPendaftaranPasien
            v-model:open="editDialogOpen"
            :patient="selectedRegisteredPatient"
            :registration-type="registrationType"
            :doctors="props.doctors"
            :clinics="props.clinics"
            :payments="props.payments"
            :igd-clinic-code="props.igdClinicCode"
        />

        <DialogRujukInternalPasien
            v-model:open="rujukInternalDialogOpen"
            :patient="selectedRegisteredPatient"
            :clinics="props.clinics"
            :igd-clinic-code="props.igdClinicCode"
        />

        <DialogPindahRawatInapPasien
            v-model:open="pindahRawatInapDialogOpen"
            :patient="selectedRegisteredPatient"
        />

        <DialogBuatSpriPasien
            v-model:open="spriDialogOpen"
            :patient="selectedRegisteredPatient"
        />
    </div>
</template>
