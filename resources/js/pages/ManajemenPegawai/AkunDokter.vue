<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2, UserRoundX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { destroy, index, store } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunDokterController';
import AccountFilterToolbar from '@/components/manajemen-pegawai/ToolbarFilterAkun.vue';
import DoctorAccountTable from '@/components/manajemen-pegawai/TableAkunDokter.vue';
import DoctorProfileDialog from '@/components/manajemen-pegawai/DialogProfilDokter.vue';
import LegacyAccountDialog from '@/components/manajemen-pegawai/DialogAkunLegacy.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { dashboard } from '@/routes';
import type { DoctorAccount, Paginated, SpecialistOption } from '@/types';

const props = defineProps<{
    doctors: Paginated<DoctorAccount>;
    filters: {
        search?: string;
        account?: string;
        status?: string;
    };
    specialists: SpecialistOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen Pegawai', href: index() },
            { title: 'Akun Dokter', href: index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const account = ref(props.filters.account ?? '');
const status = ref(props.filters.status ?? '');
const selectedAccount = ref<{ id: string; name: string; password: string | null } | null>(null);
const selectedDoctorProfile = ref<DoctorAccount | null>(null);
const doctorProfileDialogOpen = ref(false);
const isFilterLoading = ref(false);
const { openDeleteDialog } = useDeleteDialog();
const total = computed(() => props.doctors.total);

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        account.value = filters.account ?? '';
        status.value = filters.status ?? '';
    },
);

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            account: account.value || undefined,
            status: status.value || undefined,
        },
        {
            only: ['doctors'],
            preserveState: true,
            reset: ['doctors'],
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
    search.value = '';
    account.value = '';
    status.value = '';
    applyFilters();
}

function editAccount(doctor: DoctorAccount): void {
    selectedAccount.value = {
        id: doctor.kd_dokter,
        name: doctor.nm_dokter,
        password: doctor.password_decrypted,
    };
}

function createDoctor(): void {
    selectedDoctorProfile.value = null;
    doctorProfileDialogOpen.value = true;
}

function editDoctorProfile(doctor: DoctorAccount): void {
    selectedDoctorProfile.value = doctor;
    doctorProfileDialogOpen.value = true;
}

function deleteAccount(doctor: DoctorAccount): void {
    openDeleteDialog({
        level: 'warning',
        icon: UserRoundX,
        actionIcon: Trash2,
        title: 'Hapus akun dokter?',
        description: `Akun legacy ${doctor.nm_dokter} akan dihapus dari tabel user.`,
        actionLabel: 'Hapus akun',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroy.url(doctor.kd_dokter), {
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
                });
            }),
    });
}
</script>

<template>
    <Head title="Akun Dokter" />

    <div class="contents">
        <PageHeader
            title="Akun Dokter"
            description="Kelola profil dokter, status aktif, dan akun login legacy."
        >
            <template #actions>
                <div class="flex flex-col items-end gap-3">
                    <div class="text-right">
                        <p class="text-3xl font-bold leading-none text-primary">{{ total }}</p>
                        <p class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Data Dokter
                        </p>
                    </div>
                    <Button @click="createDoctor">
                        <Plus class="size-4" />
                        Tambah Dokter
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3">
            <AccountFilterToolbar
                v-model:search="search"
                v-model:account="account"
                v-model:status="status"
                :loading="isFilterLoading"
                @apply="applyFilters"
                @clear="bersihkanFilter"
            />
            <DoctorAccountTable
                :doctors="doctors"
                :loading="isFilterLoading"
                @edit-profile="editDoctorProfile"
                @edit-account="editAccount"
                @delete-account="deleteAccount"
            />
        </div>

        <DoctorProfileDialog
            v-model:open="doctorProfileDialogOpen"
            v-model:doctor="selectedDoctorProfile"
            :specialists="specialists"
        />

        <LegacyAccountDialog
            v-model:account="selectedAccount"
            :store-url="store.url()"
            title="Kelola Akun Dokter"
            description="Atur password legacy untuk"
            id-label="Kode Dokter"
        />
    </div>
</template>
