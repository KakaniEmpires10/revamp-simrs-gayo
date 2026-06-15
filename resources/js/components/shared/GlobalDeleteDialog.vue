<script setup lang="ts">
import { computed } from 'vue';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useDeleteDialog } from '@/composables/useDeleteDialog';

const { state } = useDeleteDialog();

const levelClasses = computed(() => {
    const classes = {
        danger: {
            icon: 'border-destructive/20 bg-destructive/10 text-destructive',
            action: 'destructive' as const,
        },
        warning: {
            icon: 'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400',
            action: 'destructive' as const,
        },
        info: {
            icon: 'border-primary/20 bg-primary/10 text-primary',
            action: 'destructive' as const,
        },
    };

    return classes[state.level];
});

function updateOpen(open: boolean): void {
    if (state.processing) {
        return;
    }

    state.open = open;
}

async function runAction(): Promise<void> {
    if (state.processing) {
        return;
    }

    state.processing = true;

    try {
        await state.action();
        state.open = false;
    } finally {
        state.processing = false;
    }
}
</script>

<template>
    <AlertDialog :open="state.open" @update:open="updateOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <div class="flex flex-col items-center gap-4">
                    <div
                        :class="[
                            'flex size-11 shrink-0 items-center justify-center rounded-full border',
                            levelClasses.icon,
                        ]"
                    >
                        <component :is="state.icon" class="size-5" />
                    </div>

                    <div class="min-w-0 space-y-2 text-center">
                        <AlertDialogTitle>
                            {{ state.title }}
                        </AlertDialogTitle>
                        <AlertDialogDescription class="text-center">
                            {{ state.description }}
                        </AlertDialogDescription>
                    </div>
                </div>
            </AlertDialogHeader>

            <AlertDialogFooter class="justify-end sm:justify-center">
                <AlertDialogCancel :disabled="state.processing">
                    {{ state.cancelLabel }}
                </AlertDialogCancel>
                <Button
                    type="button"
                    :variant="levelClasses.action"
                    :disabled="state.processing"
                    @click="runAction"
                >
                    <Spinner v-if="state.processing" />
                    <component v-else :is="state.actionIcon" class="size-4" />
                    {{ state.processing ? 'Memproses...' : state.actionLabel }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
