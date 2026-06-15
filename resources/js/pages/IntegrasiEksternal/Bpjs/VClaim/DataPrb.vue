<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { prbData } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import TabelDataPrb from '@/components/integrasi-eksternal/bpjs/vclaim/TabelDataPrb.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';

type PrbValue = string | number | PrbRecord | null | undefined;

interface PrbRecord {
    [key: string]: PrbValue;
}

type PrbRow = PrbRecord;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: PrbRow[];
    };
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Data PRB', href: prbData() },
        ],
    },
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const tanggalAwal = useTanggalCalendar(startDate);
const tanggalAkhir = useTanggalCalendar(endDate);
const loading = ref(false);

watch(
    () => props.filters,
    (filters) => {
        startDate.value = filters.start_date;
        endDate.value = filters.end_date;
    },
);

function applyFilters(): void {
    router.get(
        prbData.url(),
        {
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            only: ['result', 'filters'],
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                loading.value = true;
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    const hariIni = tanggalHariIni();

    startDate.value = hariIni;
    endDate.value = hariIni;
    applyFilters();
}
</script>

<template>
    <Head title="Data PRB" />

    <div class="contents">
        <PageHeader
            title="Data PRB"
            description="Daftar Program Rujuk Balik BPJS berdasarkan rentang tanggal."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="bpjsIndex()">
                        <ArrowLeft class="size-4" />
                        Kembali
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3">
            <Card>
                <CardContent class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                    <label class="grid gap-2 text-sm font-medium">
                        Tanggal Awal
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
                    <label class="grid gap-2 text-sm font-medium">
                        Tanggal Akhir
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
                    <TombolAksiFilter
                        :processing="loading"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <TabelDataPrb :result="result" :loading="loading" />
        </div>
    </div>
</template>
