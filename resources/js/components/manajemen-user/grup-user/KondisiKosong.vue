<script setup lang="ts">
import { ShieldX } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    hasSearch: boolean;
}>();

defineEmits<{
    clear: [];
    create: [];
}>();
</script>

<template>
    <section
        class="flex min-h-72 flex-col items-center justify-center rounded-lg border bg-card p-10 text-center"
    >
        <div
            class="mb-4 flex size-14 items-center justify-center rounded-lg bg-muted text-muted-foreground"
        >
            <ShieldX class="size-7" />
        </div>
        <h2 class="text-lg font-semibold">
            {{ hasSearch ? 'Level akses tidak ditemukan' : 'Belum ada level akses' }}
        </h2>
        <p class="mt-2 max-w-md text-sm text-muted-foreground">
            {{
                hasSearch
                    ? 'Tidak ada data yang cocok dengan pencarian. Coba gunakan kata kunci lain.'
                    : 'Tambahkan level akses pertama untuk mulai mengelompokkan permission pengguna SIMRS.'
            }}
        </p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            <Button v-if="hasSearch" variant="secondary" @click="$emit('clear')">
                Bersihkan Pencarian
            </Button>
            <Button @click="$emit('create')">Tambah Level Akses</Button>
        </div>
    </section>
</template>
