<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { destroy, index } from '@/actions/App/Http/Controllers/ManajemenPegawai/JadwalPraktekController';
import ScheduleFilterToolbar from '@/components/manajemen-pegawai/ToolbarFilterJadwal.vue';
import ScheduleFormDialog from '@/components/manajemen-pegawai/DialogFormJadwal.vue';
import ScheduleTable from '@/components/manajemen-pegawai/TableJadwal.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { dashboard } from '@/routes';
import type { ClinicOption, DoctorOption, Paginated, PracticeSchedule } from '@/types';

const props = defineProps<{
    schedules: Paginated<PracticeSchedule>;
    doctors: DoctorOption[];
    clinics: ClinicOption[];
    days: string[];
    filters: {
        day?: string;
        doctor?: string;
        clinic?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen Pegawai', href: index() },
            { title: 'Jadwal Praktek', href: index() },
        ],
    },
});

const day = ref(props.filters.day ?? '');
const doctor = ref(props.filters.doctor ?? '');
const clinic = ref(props.filters.clinic ?? '');
const selectedSchedule = ref<PracticeSchedule | null>(null);
const scheduleDialogOpen = ref(false);
const isFilterLoading = ref(false);
const { openDeleteDialog } = useDeleteDialog();

watch(
    () => props.filters,
    (filters) => {
        day.value = filters.day ?? '';
        doctor.value = filters.doctor ?? '';
        clinic.value = filters.clinic ?? '';
    },
);

function applyFilters(): void {
    router.get(
        index.url(),
        {
            day: day.value || undefined,
            doctor: doctor.value || undefined,
            clinic: clinic.value || undefined,
        },
        {
            only: ['schedules'],
            preserveState: true,
            reset: ['schedules'],
            onStart: () => {
                isFilterLoading.value = true;
            },
            onFinish: () => {
                isFilterLoading.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    day.value = '';
    doctor.value = '';
    clinic.value = '';
    applyFilters();
}

function openCreateDialog(): void {
    selectedSchedule.value = null;
    scheduleDialogOpen.value = true;
}

function openEditDialog(schedule: PracticeSchedule): void {
    selectedSchedule.value = schedule;
    scheduleDialogOpen.value = true;
}

function deleteSchedule(schedule: PracticeSchedule): void {
    openDeleteDialog({
        level: 'warning',
        icon: Trash2,
        actionIcon: Trash2,
        title: 'Hapus jadwal praktek?',
        description: `${schedule.nm_dokter} - ${schedule.hari_kerja} ${schedule.jam_mulai.slice(0, 5)} akan dihapus.`,
        actionLabel: 'Hapus jadwal',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(
                    destroy.url({
                        doctor: schedule.kd_dokter,
                        day: schedule.hari_kerja,
                        start: schedule.jam_mulai.slice(0, 5),
                    }),
                    {
                        preserveScroll: true,
                        onError: () => {
                            hasError = true;
                            reject();
                        },
                        onFinish: () => {
                            if (!hasError) {
                                resolve();
                            }
                        },
                    },
                );
            }),
    });
}
</script>

<template>
    <Head title="Jadwal Praktek" />

    <div class="contents">
        <PageHeader
            title="Jadwal Praktek"
            description="Kelola jadwal praktek dokter berdasarkan tabel legacy jadwal, dokter, dan poliklinik."
        >
            <template #actions>
                <Button @click="openCreateDialog">
                    <Plus class="size-4" />
                    Tambah Jadwal
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3">
            <ScheduleFilterToolbar
                v-model:day="day"
                v-model:doctor="doctor"
                v-model:clinic="clinic"
                :days="days"
                :doctors="doctors"
                :clinics="clinics"
                :loading="isFilterLoading"
                @apply="applyFilters"
                @clear="bersihkanFilter"
            />
            <ScheduleTable
                :schedules="schedules"
                :clinics="clinics"
                :loading="isFilterLoading"
                @edit="openEditDialog"
                @delete="deleteSchedule"
            />
        </div>

        <ScheduleFormDialog
            v-model:open="scheduleDialogOpen"
            v-model:schedule="selectedSchedule"
            :doctors="doctors"
            :clinics="clinics"
            :days="days"
        />
    </div>
</template>
