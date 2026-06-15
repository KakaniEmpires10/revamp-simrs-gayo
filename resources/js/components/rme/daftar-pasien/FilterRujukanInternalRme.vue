<script setup lang="ts">
import { CalendarIcon, Search } from '@lucide/vue';
import { computed, reactive, toRef, watch } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';
import type { FilterKunjunganRme, RmeOption } from '@/types';

const props = withDefaults(
    defineProps<{
        filters: FilterKunjunganRme;
        processing?: boolean;
        clinics?: RmeOption[];
        searchPlaceholder?: string;
    }>(),
    {
        processing: false,
        clinics: () => [],
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
    kd_poli_asal: props.filters.kd_poli_asal ?? null,
});

const tanggalAwal = useTanggalCalendar(toRef(form, 'tgl_awal'));
const tanggalAkhir = useTanggalCalendar(toRef(form, 'tgl_akhir'));
const selectedOriginClinic = computed<string>({
    get: () => form.kd_poli_asal ?? '',
    set: (value) => {
        form.kd_poli_asal = value || null;
    },
});
const selectedDestinationClinic = computed<string>({
    get: () => form.kd_poli ?? '',
    set: (value) => {
        form.kd_poli = value || null;
    },
});

watch(
    () => props.filters,
    (filters) => {
        form.tgl_awal = filters.tgl_awal;
        form.tgl_akhir = filters.tgl_akhir;
        form.search = filters.search;
        form.kd_poli = filters.kd_poli ?? null;
        form.kd_poli_asal = filters.kd_poli_asal ?? null;
    },
);

function filterSaatIni(): FilterKunjunganRme {
    return {
        tgl_awal: form.tgl_awal,
        tgl_akhir: form.tgl_akhir,
        search: form.search,
        kd_poli_asal: form.kd_poli_asal || null,
        kd_poli: form.kd_poli || null,
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
    form.kd_poli_asal = null;
    form.kd_poli = null;

    emit('bersihkan', filterSaatIni());
}
</script>

<template>
    <form
        class="grid w-full max-w-full min-w-0 gap-4 rounded-lg border bg-card p-4 shadow-sm"
        @submit.prevent="terapkanFilter"
    >
        <div class="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.35fr)_auto] xl:items-end">
            <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:contents">
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
            </div>

            <label class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Poli Asal</span>
                <AppSelect
                    v-model="selectedOriginClinic"
                    :options="clinics"
                    placeholder="Semua poli asal"
                />
            </label>

            <label class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Poli Tujuan</span>
                <AppSelect
                    v-model="selectedDestinationClinic"
                    :options="clinics"
                    placeholder="Semua poli tujuan"
                />
            </label>

            <label class="grid min-w-0 gap-2">
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
    </form>
</template>
