<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2, UserRoundX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { destroy, index, store } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunPetugasController';
import LegacyAccountDialog from '@/components/manajemen-pegawai/DialogAkunLegacy.vue';
import StaffProfileDialog from '@/components/manajemen-pegawai/DialogProfilPetugas.vue';
import StaffAccountTable from '@/components/manajemen-pegawai/TableAkunPetugas.vue';
import AccountFilterToolbar from '@/components/manajemen-pegawai/ToolbarFilterAkun.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { dashboard } from '@/routes';
import type { Paginated, PositionOption, StaffAccount } from '@/types';

const props = defineProps<{
    staff: Paginated<StaffAccount>;
    filters: {
        search?: string;
        account?: string;
        status?: string;
    };
    positions: PositionOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen Pegawai', href: index() },
            { title: 'Akun Petugas', href: index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const account = ref(props.filters.account ?? '');
const status = ref(props.filters.status ?? '');
const selectedAccount = ref<{ id: string; name: string; password: string | null } | null>(null);
const selectedStaffProfile = ref<StaffAccount | null>(null);
const staffProfileDialogOpen = ref(false);
const isFilterLoading = ref(false);
const { openDeleteDialog } = useDeleteDialog();
const total = computed(() => props.staff.total);

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
            only: ['staff'],
            preserveState: true,
            reset: ['staff'],
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

function editAccount(staff: StaffAccount): void {
    selectedAccount.value = {
        id: staff.nip,
        name: staff.nama,
        password: staff.password_decrypted,
    };
}

function createStaff(): void {
    selectedStaffProfile.value = null;
    staffProfileDialogOpen.value = true;
}

function editStaffProfile(staff: StaffAccount): void {
    selectedStaffProfile.value = staff;
    staffProfileDialogOpen.value = true;
}

function deleteAccount(staff: StaffAccount): void {
    openDeleteDialog({
        level: 'warning',
        icon: UserRoundX,
        actionIcon: Trash2,
        title: 'Hapus akun petugas?',
        description: `Akun legacy ${staff.nama} akan dihapus dari tabel user.`,
        actionLabel: 'Hapus akun',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroy.url(staff.nip), {
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
    <Head title="Akun Petugas" />

    <div class="contents">
        <PageHeader
            title="Akun Petugas"
            description="Kelola profil petugas, status aktif, dan akun login legacy."
        >
            <template #actions>
                <div class="flex flex-col items-end gap-3">
                    <div class="text-right">
                        <p class="text-3xl font-bold leading-none text-primary">{{ total }}</p>
                        <p class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Data Petugas
                        </p>
                    </div>
                    <Button @click="createStaff">
                        <Plus class="size-4" />
                        Tambah Petugas
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
            <StaffAccountTable
                :staff="staff"
                :loading="isFilterLoading"
                @edit-profile="editStaffProfile"
                @edit-account="editAccount"
                @delete-account="deleteAccount"
            />
        </div>

        <StaffProfileDialog
            v-model:open="staffProfileDialogOpen"
            v-model:staff="selectedStaffProfile"
            :positions="positions"
        />

        <LegacyAccountDialog
            v-model:account="selectedAccount"
            :store-url="store.url()"
            title="Kelola Akun Petugas"
            description="Atur password legacy untuk"
            id-label="NIP"
        />
    </div>
</template>
