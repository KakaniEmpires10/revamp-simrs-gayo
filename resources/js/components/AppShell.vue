<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    forceSidebarCollapsed?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
    forceSidebarCollapsed: false,
});

const isOpen = usePage().props.sidebarOpen;
const sidebarOpen = ref(Boolean(isOpen));

watch(
    () => props.forceSidebarCollapsed,
    (forceCollapsed) => {
        if (forceCollapsed) {
            sidebarOpen.value = false;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else v-model:open="sidebarOpen" :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
