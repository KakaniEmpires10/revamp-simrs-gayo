<script setup lang="ts">
import { ClipboardList } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <Card class="border-border/70 shadow-sm">
        <CardContent class="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
            <div class="flex size-12 items-center justify-center rounded-md border bg-muted/40 text-muted-foreground">
                <ClipboardList class="size-6" />
            </div>
            <div class="max-w-xl space-y-1">
                <h2 class="text-base font-semibold">
                    {{ title }}
                </h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
