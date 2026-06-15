<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalDeleteDialog from '@/components/shared/GlobalDeleteDialog.vue';
import { Toaster } from '@/components/ui/sonner';
import { showFeedback } from '@/composables/useFeedback';
import type { BreadcrumbItem } from '@/types';
import type { FlashToast } from '@/types/ui';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    forceSidebarCollapsed?: boolean;
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    forceSidebarCollapsed: false,
});

const page = usePage<{
    flash?: {
        toast?: FlashToast;
    };
    feedbackMode?: unknown;
}>();

watch(
    () => page.props.flash?.toast,
    (toast) => {
        if (!toast) {
            return;
        }

        showFeedback(toast, page.props.feedbackMode);
    },
    {
        immediate: true,
    },
);
</script>

<template>
    <AppShell variant="sidebar" :force-sidebar-collapsed="forceSidebarCollapsed">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader />
            <section class="flex h-full flex-1 flex-col gap-5 overflow-x-auto p-4">
                <Breadcrumbs
                    v-if="breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                    class="min-w-0"
                />
                <slot />
            </section>
        </AppContent>
        <Toaster rich-colors position="top-center" />
        <GlobalDeleteDialog />
    </AppShell>
</template>
