<script setup lang="ts">
import { CircleCheck, Info, OctagonX, TriangleAlert, X } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertIconWrap, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useFeedbackAlertState } from '@/composables/useFeedback';

const state = useFeedbackAlertState();

const title = computed(() => {
    const titles: Record<string, string> = {
        success: 'Berhasil',
        info: 'Informasi',
        warning: 'Perhatian',
        error: 'Gagal',
    };

    return titles[state.type];
});

const icon = computed(() => {
    const icons: Record<string, any> = {
        success: CircleCheck,
        info: Info,
        warning: TriangleAlert,
        error: OctagonX,
    };

    return icons[state.type];
});

const variant = computed(() => {
    const variants = {
        success: 'soft-success',
        info: 'soft-info',
        warning: 'soft-warning',
        error: 'soft-destructive',
    } as const;

    return variants[state.type as keyof typeof variants];
});

function dismiss(): void {
    state.open = false;
}
</script>

<template>
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
        <Alert v-if="state.open" :variant="variant" class="pr-12">
            <AlertIconWrap>
                <component :is="icon" />
            </AlertIconWrap>
            <div class="flex flex-col gap-0.5 pt-0.5">
                <AlertTitle>{{ title }}</AlertTitle>
                <AlertDescription>{{ state.message }}</AlertDescription>
            </div>
            <Button type="button" variant="ghost" size="icon-sm"
                class="absolute top-2.5 right-2.5 opacity-50 hover:opacity-90" aria-label="Tutup pesan"
                @click="dismiss">
                <X class="size-4" />
            </Button>
        </Alert>
    </Transition>
</template>
