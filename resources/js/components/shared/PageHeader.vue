<script setup lang="ts">
import FeedbackFlashAlert from '@/components/FeedbackFlashAlert.vue';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ title }}
                </h1>
                <p v-if="description" class="max-w-3xl text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.actions" class="flex w-full items-center gap-2 sm:w-auto sm:shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <FeedbackFlashAlert />
    </div>
</template>
