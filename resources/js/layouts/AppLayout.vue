<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [], forceSidebarCollapsed = false } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    forceSidebarCollapsed?: boolean;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :force-sidebar-collapsed="forceSidebarCollapsed">
        <slot />
    </AppLayout>
</template>
