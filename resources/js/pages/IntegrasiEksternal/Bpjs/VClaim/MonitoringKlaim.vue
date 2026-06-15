<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { claimMonitoring } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import TabelMonitoringKlaim from '@/components/integrasi-eksternal/bpjs/vclaim/TabelMonitoringKlaim.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';

type ClaimValue = string | number | ClaimRecord | null | undefined;

interface ClaimRecord {
    [key: string]: ClaimValue;
}

type ClaimRow = ClaimRecord;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ClaimRow[];
    };
    filters: {
        date: string;
        service_type: string;
        status: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Monitoring Klaim', href: claimMonitoring() },
        ],
    },
});

const date = ref(props.filters.date);
const tanggalKlaim = useTanggalCalendar(date);
const serviceType = ref(props.filters.service_type);
const status = ref(props.filters.status);
const loading = ref(false);

const serviceTypeOptions = [
    { label: 'Rawat Inap', value: '1' },
    { label: 'Rawat Jalan', value: '2' },
];

const statusOptions = [
    { label: 'Proses Verifikasi', value: '1' },
    { label: 'Pending Verifikasi', value: '2' },
    { label: 'Klaim', value: '3' },
];

watch(
    () => props.filters,
    (filters) => {
        date.value = filters.date;
        serviceType.value = filters.service_type;
        status.value = filters.status;
    },
);

function applyFilters(): void {
    router.get(
        claimMonitoring.url(),
        {
            date: date.value,
            service_type: serviceType.value,
            status: status.value,
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
    date.value = tanggalHariIni();
    serviceType.value = '1';
    status.value = '1';
    applyFilters();
}

</script>

<template>
    <Head title="Monitoring Klaim" />

    <div class="contents">
        <PageHeader
            title="Monitoring Klaim"
            description="Monitoring klaim BPJS berdasarkan tanggal, jenis pelayanan, dan status."
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
                <CardContent class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                    <label class="grid gap-2 text-sm font-medium">
                        Tanggal
                        <Popover v-model:open="tanggalKlaim.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalKlaim.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalKlaim.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                    </label>
                    <label class="grid gap-2 text-sm font-medium">
                        Jenis Pelayanan
                        <AppSelect v-model="serviceType" :options="serviceTypeOptions" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium">
                        Status
                        <AppSelect v-model="status" :options="statusOptions" />
                    </label>
                    <TombolAksiFilter
                        :processing="loading"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <TabelMonitoringKlaim :result="result" :loading="loading" />
        </div>
    </div>
</template>
