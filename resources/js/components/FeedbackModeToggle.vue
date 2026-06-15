<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell, MessageSquareWarning } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/feedback';
import type { FeedbackMode } from '@/types/ui';

const page = usePage();
const feedbackMode = computed(() => page.props.feedbackMode as FeedbackMode);

const options = [
    {
        value: 'alert',
        Icon: MessageSquareWarning,
        label: 'Alert',
    },
    {
        value: 'toast',
        Icon: Bell,
        label: 'Toast',
    },
] as const;

function updateFeedbackMode(value: FeedbackMode): void {
    if (feedbackMode.value === value) {
        return;
    }

    router.patch(
        update(),
        { feedback_mode: value },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <Button
            v-for="{ value, Icon, label } in options"
            :key="value"
            type="button"
            variant="ghost"
            size="sm"
            :class="[
                'h-8 rounded-md px-3',
                feedbackMode === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
            @click="updateFeedbackMode(value)"
        >
            <component :is="Icon" class="size-4" />
            <span>{{ label }}</span>
        </Button>
    </div>
</template>
