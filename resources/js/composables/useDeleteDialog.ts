import type { Component } from 'vue';
import { markRaw, reactive } from 'vue';
import { Trash2 } from '@lucide/vue';

export type DeleteDialogLevel = 'danger' | 'warning' | 'info';

export type DeleteDialogOptions = {
    title?: string;
    description?: string;
    actionLabel?: string;
    cancelLabel?: string;
    level?: DeleteDialogLevel;
    icon?: Component;
    actionIcon?: Component;
    action: () => Promise<void> | void;
};

type DeleteDialogState = Required<
    Pick<
        DeleteDialogOptions,
        'title' | 'description' | 'actionLabel' | 'cancelLabel' | 'level'
    >
> & {
    open: boolean;
    processing: boolean;
    icon: Component;
    actionIcon: Component;
    action: () => Promise<void> | void;
};

const noop = (): void => {};

const state = reactive<DeleteDialogState>({
    open: false,
    processing: false,
    title: 'Hapus data?',
    description: 'Data yang dihapus tidak dapat dikembalikan.',
    actionLabel: 'Hapus',
    cancelLabel: 'Batal',
    level: 'danger',
    icon: markRaw(Trash2),
    actionIcon: markRaw(Trash2),
    action: noop,
});

export function useDeleteDialog() {
    function openDeleteDialog(options: DeleteDialogOptions): void {
        state.open = true;
        state.processing = false;
        state.title = options.title ?? 'Hapus data?';
        state.description =
            options.description ?? 'Data yang dihapus tidak dapat dikembalikan.';
        state.actionLabel = options.actionLabel ?? 'Hapus';
        state.cancelLabel = options.cancelLabel ?? 'Batal';
        state.level = options.level ?? 'danger';
        state.icon = markRaw(options.icon ?? Trash2);
        state.actionIcon = markRaw(options.actionIcon ?? Trash2);
        state.action = options.action;
    }

    return {
        openDeleteDialog,
        state,
    };
}
