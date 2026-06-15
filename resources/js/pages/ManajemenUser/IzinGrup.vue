<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckSquare, Save, Search, Square } from '@lucide/vue';
import { computed, ref } from 'vue';
import { edit, update } from '@/actions/App/Http/Controllers/ManajemenUser/IzinGrupController';
import { index as userGroupIndex } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import type { GrupAuth, RuteGrupAuth } from '@/types';

const props = defineProps<{
    group: GrupAuth;
    routeGroups: RuteGrupAuth[];
    checkedPermissions: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Manajemen User', href: userGroupIndex() },
        ],
    },
});

setLayoutProps({
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Manajemen User', href: userGroupIndex() },
        { title: 'Permission Group', href: edit(props.group.id) },
    ],
});

const search = ref('');
const selectedPermissions = ref<string[]>([...props.checkedPermissions]);
const processing = ref(false);

const filteredRouteGroups = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) {
        return props.routeGroups;
    }

    return props.routeGroups
        .map((group) => ({
            ...group,
            routes: group.routes.filter((route) =>
                [group.title, route.url, route.type].some((value) =>
                    value.toLowerCase().includes(keyword),
                ),
            ),
        }))
        .filter((group) => group.routes.length);
});

const totalRoutes = computed(() =>
    props.routeGroups.reduce((total, group) => total + group.routes.length, 0),
);

function isChecked(url: string): boolean {
    return selectedPermissions.value.includes(url);
}

function togglePermission(url: string, checked: boolean | 'indeterminate'): void {
    if (checked === true && !isChecked(url)) {
        selectedPermissions.value.push(url);
    }

    if (checked === false) {
        selectedPermissions.value = selectedPermissions.value.filter(
            (permission) => permission !== url,
        );
    }
}

function toggleGroup(group: RuteGrupAuth): void {
    const everyChecked = group.routes.every((route) => isChecked(route.url));
    const urls = group.routes.map((route) => route.url);

    selectedPermissions.value = everyChecked
        ? selectedPermissions.value.filter((permission) => !urls.includes(permission))
        : [...new Set([...selectedPermissions.value, ...urls])];
}

function savePermissions(): void {
    processing.value = true;

    router.patch(
        update.url(props.group.id),
        {
            permissions: selectedPermissions.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Permission Group - ${group.name}`" />

    <div class="contents">
        <PageHeader
            title="Permission Group"
            :description="`Kelola permission untuk level akses ${group.name}.`"
        >
            <template #actions>
                <Button :disabled="processing" @click="savePermissions">
                    <Spinner v-if="processing" />
                    <Save v-else class="size-4" />
                    Simpan Permission
                </Button>
            </template>
        </PageHeader>

        <section class="grid gap-4 lg:grid-cols-[280px_1fr]">
            <aside class="rounded-lg border bg-card p-4 max-h-fit">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium">{{ group.name }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ group.alias }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-md bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Terpilih</p>
                            <p class="text-xl font-semibold">{{ selectedPermissions.length }}</p>
                        </div>
                        <div class="rounded-md bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Total</p>
                            <p class="text-xl font-semibold">{{ totalRoutes }}</p>
                        </div>
                    </div>
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" class="pl-9" placeholder="Cari route" />
                    </div>
                </div>
            </aside>

            <div class="space-y-3">
                <section
                    v-for="routeGroup in filteredRouteGroups"
                    :key="routeGroup.title"
                    class="rounded-lg border bg-card"
                >
                    <header class="flex items-center justify-between gap-3 border-b p-4">
                        <div>
                            <h2 class="font-medium">{{ routeGroup.title }}</h2>
                            <p class="text-xs text-muted-foreground">
                                {{ routeGroup.routes.length }} route
                            </p>
                        </div>
                        <Button size="sm" variant="outline" @click="toggleGroup(routeGroup)">
                            <component
                                :is="routeGroup.routes.every((route) => isChecked(route.url)) ? CheckSquare : Square"
                                class="size-4"
                            />
                            Pilih Grup
                        </Button>
                    </header>

                    <div class="grid gap-0 md:grid-cols-2 xl:grid-cols-3">
                        <label
                            v-for="route in routeGroup.routes"
                            :key="route.id"
                            class="flex cursor-pointer items-start gap-3 border-b p-4 transition-colors hover:bg-muted/40 md:border-r xl:[&:nth-child(3n)]:border-r-0"
                        >
                            <Checkbox
                                :model-value="isChecked(route.url)"
                                @update:model-value="togglePermission(route.url, $event)"
                            />
                            <span class="min-w-0 space-y-1">
                                <span class="block truncate font-mono text-xs">{{ route.url }}</span>
                                <span class="block text-xs text-muted-foreground">{{ route.type || 'route' }}</span>
                            </span>
                        </label>
                    </div>
                </section>

                <div
                    v-if="!filteredRouteGroups.length"
                    class="rounded-lg border bg-card p-10 text-center text-sm text-muted-foreground"
                >
                    Route tidak ditemukan.
                </div>
            </div>
        </section>
    </div>
</template>
