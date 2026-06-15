<script setup lang="ts">
import NavigasiPemeriksaan from '@/components/rme/pemeriksaan/NavigasiPemeriksaan.vue';
import ToggleNavigasiPemeriksaan from '@/components/rme/pemeriksaan/ToggleNavigasiPemeriksaan.vue';
import type { PemeriksaanMenu } from '@/types';
import type { PemeriksaanNavigationMode } from '@/types/ui';

const mode = defineModel<PemeriksaanNavigationMode>('mode', { required: true });

defineProps<{
    menus: PemeriksaanMenu[];
    activeMenu: string;
}>();
</script>

<template>
    <div v-if="mode === 'sidebar-tree'" class="hidden min-w-0 gap-4 lg:grid lg:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="min-w-0 rounded-lg border border-border/70 bg-background/70 p-3 shadow-sm backdrop-blur lg:sticky lg:top-36 lg:self-start">
            <div class="mb-3 flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-muted-foreground uppercase">Menu Pemeriksaan</p>
                </div>
                <ToggleNavigasiPemeriksaan v-model:mode="mode" compact />
            </div>
            <NavigasiPemeriksaan :menus="menus" :active-menu="activeMenu" orientation="vertical" />
        </aside>

        <main class="min-w-0">
            <slot />
        </main>
    </div>

    <div v-if="mode === 'sidebar-tree'" class="flex min-w-0 flex-col gap-4 lg:hidden">
        <div class="flex min-w-0 flex-col gap-3 rounded-lg border border-border/70 bg-background/70 px-3 pt-2 shadow-sm backdrop-blur">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-medium text-muted-foreground uppercase">Menu Pemeriksaan</p>
                <ToggleNavigasiPemeriksaan v-model:mode="mode" compact />
            </div>
            <NavigasiPemeriksaan :menus="menus" :active-menu="activeMenu" orientation="horizontal" />
        </div>

        <slot />
    </div>

    <div v-else class="flex min-w-0 flex-col gap-4">
        <div class="flex min-w-0 flex-col gap-3 rounded-lg border border-border/70 bg-background/70 px-3 pt-2 shadow-sm backdrop-blur">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-medium text-muted-foreground uppercase">Menu Pemeriksaan</p>
                </div>
                <ToggleNavigasiPemeriksaan v-model:mode="mode" compact />
            </div>
            <NavigasiPemeriksaan :menus="menus" :active-menu="activeMenu" orientation="horizontal" />
        </div>

        <slot />
    </div>
</template>
