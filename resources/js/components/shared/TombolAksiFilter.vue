<script setup lang="ts">
import { FunnelX } from '@lucide/vue';
import { SlidersHorizontal } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

withDefaults(
    defineProps<{
        processing?: boolean;
        disabled?: boolean;
        submit?: boolean;
    }>(),
    {
        processing: false,
        disabled: false,
        submit: false,
    },
);

const emit = defineEmits<{
    terapkan: [];
    bersihkan: [];
}>();
</script>

<template>
    <div class="flex items-center gap-2">
        <Button
            :type="submit ? 'submit' : 'button'"
            variant="secondary"
            :disabled="processing || disabled"
            @click="!submit && emit('terapkan')"
        >
            <Spinner v-if="processing" />
            <SlidersHorizontal v-else class="size-4" />
            Terapkan
        </Button>

        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost-destructive"
                        size="icon"
                        aria-label="Bersihkan filter"
                        :disabled="processing"
                        @click="emit('bersihkan')"
                    >
                        <FunnelX class="size-4" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>Bersihkan filter</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
