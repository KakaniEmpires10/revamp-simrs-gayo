<script setup lang="ts">
import { Search } from '@lucide/vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';

withDefaults(
    defineProps<{
        loading?: boolean;
    }>(),
    {
        loading: false,
    },
);

defineEmits<{
    apply: [];
    clear: [];
}>();

const search = defineModel<string>('search', { required: true });
const account = defineModel<string>('account', { required: true });
const status = defineModel<string>('status', { required: true });

const accountOptions = [
    { value: 'created', label: 'Sudah Ada Akun' },
    { value: 'empty', label: 'Belum Ada Akun' },
];

const statusOptions = [
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak Aktif' },
];
</script>

<template>
    <form
        class="flex flex-col gap-4 rounded-lg border bg-card p-4 shadow-sm md:flex-row md:items-end"
        @submit.prevent="$emit('apply')"
    >
        <div class="min-w-0 flex-1">
            <div class="group relative">
                <Search
                    class="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground transition-colors group-focus-within:text-primary"
                />
                <Input
                    v-model="search"
                    class="h-9 rounded-lg bg-background pl-11"
                    placeholder="Cari kode, nama, spesialis/jabatan..."
                />
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-[minmax(0,13rem)_minmax(0,13rem)_auto]">
            <label class="flex min-w-44 flex-col gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">
                    Status Akun
                </span>
                <AppSelect
                    v-model="account"
                    :options="accountOptions"
                    placeholder="Semua Data"
                />
            </label>

            <label class="flex min-w-44 flex-col gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">
                    Status Data
                </span>
                <AppSelect
                    v-model="status"
                    :options="statusOptions"
                    placeholder="Semua Status"
                />
            </label>

            <div class="self-end">
                <TombolAksiFilter
                    :processing="loading"
                    submit
                    @bersihkan="$emit('clear')"
                />
            </div>
        </div>
    </form>
</template>
