<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2, UserPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { index as daftarPasienIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import {
    create as createDataPasien,
    destroy as destroyDataPasien,
    index as dataPasienIndex,
} from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import DialogGabungRekamMedis from '@/components/pendaftaran/data-pasien/DialogGabungRekamMedis.vue';
import PencarianDataPasien from '@/components/pendaftaran/data-pasien/PencarianDataPasien.vue';
import TabelDataPasien from '@/components/pendaftaran/data-pasien/TabelDataPasien.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { feedbackOnly } from '@/composables/useFeedback';
import { dashboard } from '@/routes';
import type { PaginatedResponse, RegistrationPatient } from '@/types';

const props = defineProps<{
    patients: PaginatedResponse<RegistrationPatient>;
    filters: {
        search?: string;
        jk?: string;
        create?: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Pendaftaran', href: daftarPasienIndex() },
            { title: 'Data Pasien', href: dataPasienIndex() },
        ],
    },
});

const filters = computed(() => ({
    search: props.filters.search ?? '',
    jk: props.filters.jk ?? '',
}));

const filterProcessing = ref(false);
const gabungDialogOpen = ref(false);
const selectedPatient = ref<RegistrationPatient | null>(null);
const { openDeleteDialog } = useDeleteDialog();

function terapkanFilter(payload: { search: string; jk: string }): void {
    filterProcessing.value = true;

    router.get(
        dataPasienIndex.url(),
        {
            search: payload.search || undefined,
            jk: payload.jk || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['patients', 'filters'],
            onFinish: () => {
                filterProcessing.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    terapkanFilter({ search: '', jk: '' });
}

function bukaGabungRm(patient: RegistrationPatient): void {
    selectedPatient.value = patient;
    gabungDialogOpen.value = true;
}

function konfirmasiHapus(patient: RegistrationPatient): void {
    openDeleteDialog({
        title: 'Hapus data pasien?',
        description: `Pasien ${patient.no_rkm_medis} - ${patient.nm_pasien} akan dihapus jika belum memiliki riwayat pendaftaran.`,
        actionLabel: 'Hapus pasien',
        icon: Trash2,
        actionIcon: Trash2,
        action: () => {
            router.delete(destroyDataPasien.url(patient.no_rkm_medis), {
                preserveScroll: true,
                only: feedbackOnly(['patients', 'filters']),
            });
        },
    });
}
</script>

<template>
    <Head title="Data Pasien" />

    <div class="contents">
        <PageHeader
            title="Data Pasien"
            description="Daftar pasien lokal dari tabel legacy pasien."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="createDataPasien()">
                        <UserPlus class="size-4" />
                        Tambah Pasien Baru
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4">
            <PencarianDataPasien
                :filters="filters"
                :processing="filterProcessing"
                @terapkan="terapkanFilter"
                @bersihkan="bersihkanFilter"
            />
            <TabelDataPasien
                :patients="patients"
                @hapus="konfirmasiHapus"
                @gabung-rm="bukaGabungRm"
            />
        </div>

        <DialogGabungRekamMedis
            v-model:open="gabungDialogOpen"
            :patient="selectedPatient"
        />
    </div>
</template>
