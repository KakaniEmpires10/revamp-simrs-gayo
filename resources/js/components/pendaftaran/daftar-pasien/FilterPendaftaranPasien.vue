<script setup lang="ts">
import { CalendarIcon, Search } from '@lucide/vue';
import { reactive, toRef, watch } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';
import type { RegistrationOption, RegistrationTableFilters, RegistrationType } from '@/types';

const props = defineProps<{
    filters: RegistrationTableFilters;
    clinics: RegistrationOption[];
    registrationType: RegistrationType;
    processing: boolean;
}>();

const emit = defineEmits<{
    terapkan: [filters: RegistrationTableFilters];
    bersihkan: [filters: RegistrationTableFilters];
}>();

const form = reactive({
    tgl_awal: props.filters.tgl_awal,
    tgl_akhir: props.filters.tgl_akhir,
    kd_poli: props.filters.kd_poli ?? '',
    search: props.filters.search,
});

const tanggalAwal = useTanggalCalendar(toRef(form, 'tgl_awal'));
const tanggalAkhir = useTanggalCalendar(toRef(form, 'tgl_akhir'));

watch(
    () => props.filters,
    (filters) => {
        form.tgl_awal = filters.tgl_awal;
        form.tgl_akhir = filters.tgl_akhir;
        form.kd_poli = filters.kd_poli ?? '';
        form.search = filters.search;
    },
);

function filterSaatIni(): RegistrationTableFilters {
    return {
        view: 'table',
        jenis_pendaftaran: props.registrationType,
        tgl_awal: form.tgl_awal,
        tgl_akhir: form.tgl_akhir,
        kd_poli: props.registrationType === 'igd' ? null : (form.kd_poli || null),
        search: form.search,
    };
}

function terapkanFilter(): void {
    emit('terapkan', filterSaatIni());
}

function bersihkanFilter(): void {
    const hariIni = tanggalHariIni();

    form.tgl_awal = hariIni;
    form.tgl_akhir = hariIni;
    form.kd_poli = '';
    form.search = '';

    emit('bersihkan', filterSaatIni());
}
</script>

<template>
    <form
        class="border-border bg-background/95 grid w-full max-w-full min-w-0 gap-4 overflow-x-hidden rounded-lg border p-4 shadow-sm"
        @submit.prevent="terapkanFilter"
    >
        <div class="grid min-w-0 gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.4fr)_auto]">
            <label class="grid min-w-0 gap-2">
                <span class="text-sm font-medium">Tanggal Awal</span>
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
                <span class="text-sm font-medium">Tanggal Akhir</span>
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
            <label class="grid min-w-0 gap-2">
                <span class="text-sm font-medium">Poliklinik</span>
                <AppSelect
                    v-model="form.kd_poli"
                    :options="clinics"
                    :disabled="registrationType === 'igd'"
                    :placeholder="registrationType === 'igd' ? 'Instalasi Gawat Darurat' : 'Semua poliklinik'"
                />
            </label>
            <label class="grid min-w-0 gap-2">
                <span class="text-sm font-medium">Cari</span>
                <div class="relative">
                    <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        v-model="form.search"
                        class="pl-9"
                        placeholder="No rawat, no RM, NIK, peserta, nama"
                    />
                </div>
            </label>
            <div class="flex min-w-0 items-end gap-2">
                <TombolAksiFilter
                    :processing="processing"
                    submit
                    @bersihkan="bersihkanFilter"
                />
            </div>
        </div>

        <p v-if="registrationType === 'igd'" class="text-muted-foreground text-sm">
            Mode IGD selalu memakai poliklinik Instalasi Gawat Darurat, sehingga filter poli tidak diperlukan.
        </p>
    </form>
</template>
