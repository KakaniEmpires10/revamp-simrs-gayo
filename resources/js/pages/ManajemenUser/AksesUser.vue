<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2, UserX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { destroy, index } from '@/actions/App/Http/Controllers/ManajemenUser/AksesUserController';
import PageHeader from '@/components/shared/PageHeader.vue';
import AccessFormDialog from '@/components/manajemen-user/akses-user/DialogFormAkses.vue';
import FilterToolbar from '@/components/manajemen-user/akses-user/ToolbarFilter.vue';
import AksesUserTable from '@/components/manajemen-user/akses-user/TableAksesUser.vue';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { dashboard } from '@/routes';
import type { GrupAuth, Paginated, AksesUser } from '@/types';

const props = defineProps<{
    users: Paginated<AksesUser>;
    groups: Pick<GrupAuth, 'id' | 'name' | 'alias'>[];
    jenisAkun: {
        value: string;
        label: string;
    }[];
    filters: {
        alias_group?: string;
        status?: string;
        search?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen User', href: index() },
            { title: 'User Akses', href: index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const aliasGroup = ref(props.filters.alias_group ?? '');
const status = ref(props.filters.status ?? '');
const selectedUser = ref<AksesUser | null>(null);
const isTableLoading = ref(false);
const { openDeleteDialog } = useDeleteDialog();

const userTotal = computed(() => props.users.total);

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        aliasGroup.value = filters.alias_group ?? '';
        status.value = filters.status ?? '';
    },
);

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            alias_group: aliasGroup.value || undefined,
            status: status.value || undefined,
        },
        {
            only: ['users'],
            preserveState: true,
            preserveScroll: false,
            reset: ['users'],
            onStart: () => {
                isTableLoading.value = true;
            },
            onFinish: () => {
                isTableLoading.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    search.value = '';
    aliasGroup.value = '';
    status.value = '';
    applyFilters();
}

function openDeleteAccessDialog(user: AksesUser): void {
    openDeleteDialog({
        level: 'warning',
        icon: UserX,
        actionIcon: Trash2,
        title: 'Hapus level akses pengguna?',
        description: `Level akses untuk ${user.nama || user.id_user_decrypted} akan dikosongkan.`,
        actionLabel: 'Hapus akses',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroy.url(user.id_user_decrypted), {
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
    <Head title="User Akses" />

    <div class="contents">
        <PageHeader
            title="User Akses"
            description="Tetapkan user dokter dan petugas legacy ke level akses aplikasi. Integrasi data dari tabel legacy user, dokter, petugas, dan auth core."
        >
            <template #actions>
                <div class="text-right">
                    <p class="text-3xl font-bold leading-none text-primary">
                        {{ userTotal }}
                    </p>
                    <p
                        class="mt-1 text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                    >
                        Total Entitas User
                    </p>
                </div>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-4">
            <FilterToolbar
                v-model:search="search"
                v-model:alias-group="aliasGroup"
                v-model:status="status"
                :groups="groups"
                :jenis-akun="jenisAkun"
                :loading="isTableLoading"
                @apply="applyFilters"
                @clear="bersihkanFilter"
            />

            <AksesUserTable
                :users="users"
                :loading="isTableLoading"
                @edit="selectedUser = $event"
                @delete="openDeleteAccessDialog"
            />
        </div>

        <AccessFormDialog
            v-model:user="selectedUser"
            :groups="groups"
        />
    </div>
</template>
