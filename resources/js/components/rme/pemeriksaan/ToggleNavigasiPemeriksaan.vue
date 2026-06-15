<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ListTree, PanelTop } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/navigasi-pemeriksaan';
import type { PemeriksaanNavigationMode } from '@/types/ui';

const page = usePage();
const model = defineModel<PemeriksaanNavigationMode | undefined>('mode');
defineProps<{
    compact?: boolean;
}>();
const activeMode = computed<PemeriksaanNavigationMode>(() => model.value ?? (page.props.pemeriksaanNavigationMode as PemeriksaanNavigationMode) ?? 'sidebar-tree');

const options = [
    {
        value: 'sidebar-tree',
        Icon: ListTree,
        label: 'Sidebar Tree',
    },
    {
        value: 'top-tab',
        Icon: PanelTop,
        label: 'Tab',
    },
] as const;

function updateMode(value: PemeriksaanNavigationMode): void {
    if (activeMode.value === value) {
        return;
    }

    model.value = value;

    router.patch(
        update.url(),
        { pemeriksaan_navigation_mode: value },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
        <Button
            v-for="{ value, Icon, label } in options"
            :key="value"
            type="button"
            variant="ghost"
            size="sm"
            :class="[
                'h-8 rounded-md px-3',
                activeMode === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
            @click="updateMode(value)"
        >
            <component :is="Icon" class="size-4" />
            <span v-if="!compact">{{ label }}</span>
        </Button>
    </div>
</template>
