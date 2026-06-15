<script setup lang="ts">
import { Plus } from '@lucide/vue';

defineEmits<{
    create: [];
}>();
</script>

<template>
    <button
        type="button"
        class="flex min-h-56 flex-col items-center justify-center gap-3 rounded-lg border border-dashed bg-card p-5 text-center text-muted-foreground transition-colors hover:border-primary/50 hover:bg-primary/5 hover:text-primary"
        @click="$emit('create')"
    >
        <span
            class="flex size-12 items-center justify-center rounded-md bg-primary/10 text-primary"
        >
            <Plus class="size-6" />
        </span>
        <span class="text-base font-semibold text-foreground">
            Tambah Level Akses
        </span>
        <span class="max-w-56 text-sm">
            Buat grup akses baru untuk mengatur permission pengguna SIMRS.
        </span>
    </button>
</template>
