<script setup lang="ts">
import { Search } from '@lucide/vue';
import { reactive, watch } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Card, CardContent } from '@/components/ui/card';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';

type FilterDataPasien = {
    search: string;
    jk: string;
};

const props = defineProps<{
    filters: FilterDataPasien;
    processing: boolean;
}>();

const emit = defineEmits<{
    terapkan: [filters: FilterDataPasien];
    bersihkan: [filters: FilterDataPasien];
}>();

const genderOptions = [
    { value: '', label: 'Semua gender' },
    { value: 'L', label: 'Laki-laki' },
    { value: 'P', label: 'Perempuan' },
];

const form = reactive({
    search: props.filters.search,
    jk: props.filters.jk,
});

watch(
    () => props.filters,
    (filters) => {
        form.search = filters.search;
        form.jk = filters.jk;
    },
    { deep: true },
);

function currentFilters(): FilterDataPasien {
    return {
        search: form.search,
        jk: form.jk,
    };
}

function terapkanFilter(): void {
    emit('terapkan', currentFilters());
}

function bersihkanFilter(): void {
    form.search = '';
    form.jk = '';

    emit('bersihkan', currentFilters());
}
</script>

<template>
    <Card class="border-border/70 shadow-sm">
        <CardContent>
            <form class="grid gap-4" @submit.prevent="terapkanFilter">
                <div class="grid min-w-0 gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,220px)_auto]">
                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-medium">Keyword</span>
                        <div class="relative">
                            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="form.search"
                                class="pl-9"
                                placeholder="No rawat, no RM, NIK, peserta, nama"
                            />
                        </div>
                    </label>
                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-medium">Gender</span>
                        <AppSelect
                            v-model="form.jk"
                            :options="genderOptions"
                            placeholder="Semua gender"
                        />
                    </label>
                    <div class="flex min-w-0 items-end">
                        <TombolAksiFilter
                            :processing="processing"
                            submit
                            @bersihkan="bersihkanFilter"
                        />
                    </div>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
