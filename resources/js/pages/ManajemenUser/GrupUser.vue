<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, ShieldAlert, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { destroy as destroyGroup, index } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AddGroupCard from '@/components/manajemen-user/grup-user/CardTambahGrup.vue';
import EmptyState from '@/components/manajemen-user/grup-user/KondisiKosong.vue';
import FilterCard from '@/components/manajemen-user/grup-user/CardFilter.vue';
import FormDialog from '@/components/manajemen-user/grup-user/DialogForm.vue';
import GroupCard from '@/components/manajemen-user/grup-user/CardGrup.vue';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { dashboard } from '@/routes';
import type { GrupAuth } from '@/types';

const props = defineProps<{
    groups: GrupAuth[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen User', href: index() },
            { title: 'User Group', href: index() },
        ],
    },
});

const search = ref('');
const selectedGroup = ref<GrupAuth | null>(null);
const isFormOpen = ref(false);
const { openDeleteDialog } = useDeleteDialog();

const filteredGroups = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) {
        return props.groups;
    }

    return props.groups.filter((group) =>
        [group.name, group.alias, group.keterangan]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(keyword)),
    );
});

function openCreateForm(): void {
    selectedGroup.value = null;
    isFormOpen.value = true;
}

function openEditForm(group: GrupAuth): void {
    selectedGroup.value = group;
    isFormOpen.value = true;
}

function openDeleteGroupDialog(group: GrupAuth): void {
    openDeleteDialog({
        level: 'danger',
        icon: ShieldAlert,
        actionIcon: Trash2,
        title: 'Hapus level akses?',
        description: `Level akses "${group.name}" akan dihapus jika belum memiliki permission.`,
        actionLabel: 'Hapus',
        cancelLabel: 'Batal',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroyGroup.url(group.id), {
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
    <Head title="User Group" />

    <div class="contents">
        <PageHeader
            title="User Group"
            description="Kelola level akses yang digunakan untuk mengelompokkan hak akses pengguna SIMRS."
        >
            <template #actions>
                <Button @click="openCreateForm">
                    <Plus class="size-4" />
                    Tambah Level Akses
                </Button>
            </template>
        </PageHeader>

        <FilterCard
            v-model:search="search"
            :total="groups.length"
            :filtered="filteredGroups.length"
        />

        <section
            v-if="filteredGroups.length"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <GroupCard
                v-for="group in filteredGroups"
                :key="group.id"
                :group="group"
                @edit="openEditForm"
                @delete="openDeleteGroupDialog"
            />

            <AddGroupCard @create="openCreateForm" />
        </section>

        <EmptyState
            v-else
            :has-search="Boolean(search.trim())"
            @clear="search = ''"
            @create="openCreateForm"
        />

        <FormDialog
            v-model:open="isFormOpen"
            :group="selectedGroup"
        />
    </div>
</template>
