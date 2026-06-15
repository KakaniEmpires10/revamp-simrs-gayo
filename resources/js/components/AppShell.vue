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

const page = usePage();
const sidebarOpen = ref(Boolean(page.props.sidebarOpen));

function sinkronkanSidebarOpen(): void {
    sidebarOpen.value = props.forceSidebarCollapsed ? false : Boolean(page.props.sidebarOpen);
}

watch(
    [() => props.forceSidebarCollapsed, () => page.props.sidebarOpen],
    () => sinkronkanSidebarOpen(),
    { immediate: true },
);
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else v-model:open="sidebarOpen" :default-open="Boolean(page.props.sidebarOpen)">
        <slot />
    </SidebarProvider>
</template>
