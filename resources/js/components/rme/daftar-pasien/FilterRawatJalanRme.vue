<script setup lang="ts">
import { CalendarIcon, Search } from '@lucide/vue';
import { computed, reactive, toRef, watch } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';
import type { FilterKunjunganRme, RmeOption } from '@/types';

const props = withDefaults(
    defineProps<{
        filters: FilterKunjunganRme;
        processing?: boolean;
        options?: RmeOption[];
        optionKey?: 'kd_poli' | 'kd_bangsal' | 'status';
        optionLabel?: string;
        optionPlaceholder?: string;
        statusOptions?: RmeOption[];
        paymentOptions?: RmeOption[];
        searchPlaceholder?: string;
    }>(),
    {
        processing: false,
        options: () => [],
        optionKey: undefined,
        optionLabel: 'Filter',
        optionPlaceholder: 'Semua data',
        statusOptions: () => [],
        paymentOptions: () => [],
        searchPlaceholder: 'Cari no rawat, no RM, nama pasien, dokter...',
    },
);

const emit = defineEmits<{
    terapkan: [filters: FilterKunjunganRme];
    bersihkan: [filters: FilterKunjunganRme];
}>();

const form = reactive<FilterKunjunganRme>({
    tgl_awal: props.filters.tgl_awal,
    tgl_akhir: props.filters.tgl_akhir,
    search: props.filters.search,
    kd_poli: props.filters.kd_poli ?? null,
    kd_bangsal: props.filters.kd_bangsal ?? null,
    kd_pj: props.filters.kd_pj ?? null,
    status: props.filters.status ?? null,
});

const tanggalAwal = useTanggalCalendar(toRef(form, 'tgl_awal'));
const tanggalAkhir = useTanggalCalendar(toRef(form, 'tgl_akhir'));
const selectedOptionValue = computed<string>({
    get: () => {
        if (!props.optionKey) {
            return '';
        }

        return form[props.optionKey] ?? '';
    },
    set: (value) => {
        if (!props.optionKey) {
            return;
        }

        form[props.optionKey] = value || null;
    },
});
const selectedStatusValue = computed<string>({
    get: () => form.status ?? '',
    set: (value) => {
        form.status = value || null;
    },
});
const selectedPaymentValue = computed<string>({
    get: () => form.kd_pj ?? '',
    set: (value) => {
        form.kd_pj = value || null;
    },
});

watch(
    () => props.filters,
    (filters) => {
        form.tgl_awal = filters.tgl_awal;
        form.tgl_akhir = filters.tgl_akhir;
        form.search = filters.search;
        form.kd_poli = filters.kd_poli ?? null;
        form.kd_bangsal = filters.kd_bangsal ?? null;
        form.kd_pj = filters.kd_pj ?? null;
        form.status = filters.status ?? null;
    },
);

function filterSaatIni(): FilterKunjunganRme {
    return {
        tgl_awal: form.tgl_awal,
        tgl_akhir: form.tgl_akhir,
        search: form.search,
        kd_poli: props.optionKey === 'kd_poli' ? (form.kd_poli || null) : undefined,
        kd_bangsal: props.optionKey === 'kd_bangsal' ? (form.kd_bangsal || null) : undefined,
        kd_pj: form.kd_pj || null,
        status: form.status || null,
    };
}

function terapkanFilter(): void {
    emit('terapkan', filterSaatIni());
}

function bersihkanFilter(): void {
    const hariIni = tanggalHariIni();

    form.tgl_awal = hariIni;
    form.tgl_akhir = hariIni;
    form.search = '';
    form.kd_poli = null;
    form.kd_bangsal = null;
    form.kd_pj = null;
    form.status = null;

    emit('bersihkan', filterSaatIni());
}
</script>

<template>
    <form
        class="grid w-full max-w-full min-w-0 gap-4 rounded-lg border bg-card p-4 shadow-sm"
        @submit.prevent="terapkanFilter"
    >
        <div class="grid min-w-0 gap-4">
            <div class="grid min-w-0 gap-3 md:max-w-2xl md:grid-cols-2">
                <label class="grid min-w-0 gap-2">
                    <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Tanggal Awal</span>
                    <Popover v-model:open="tanggalAwal.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalAwal.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalAwal.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                </label>

                <label class="grid min-w-0 gap-2">
                    <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Tanggal Akhir</span>
                    <Popover v-model:open="tanggalAkhir.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalAkhir.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalAkhir.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                </label>

                <Separator class="md:col-span-2" />
            </div>

            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1.4fr)_auto]">

            <label v-if="optionKey" class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">{{ optionLabel }}</span>
                <AppSelect
                    v-model="selectedOptionValue"
                    :options="options"
                    :placeholder="optionPlaceholder"
                />
            </label>

            <label v-if="statusOptions.length" class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Status</span>
                <AppSelect
                    v-model="selectedStatusValue"
                    :options="statusOptions"
                    placeholder="Semua status"
                />
            </label>

            <label v-if="paymentOptions.length" class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Penjamin</span>
                <AppSelect
                    v-model="selectedPaymentValue"
                    :options="paymentOptions"
                    placeholder="Semua penjamin"
                />
            </label>

            <label class="grid min-w-0 gap-2" :class="!optionKey && 'xl:col-span-2'">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Pencarian</span>
                <div class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="form.search"
                        class="pl-9"
                        :placeholder="searchPlaceholder"
                    />
                </div>
            </label>

            <div class="flex items-end md:col-span-2 xl:col-span-1">
                <TombolAksiFilter
                    :processing="processing"
                    submit
                    @bersihkan="bersihkanFilter"
                />
            </div>
            </div>
        </div>
    </form>
</template>
