<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { sepApprovals } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import TabelPersetujuanSep from '@/components/integrasi-eksternal/bpjs/vclaim/TabelPersetujuanSep.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import { Input } from '@/components/ui/input';
import { parseDate } from '@internationalized/date';
import { tanggalHariIni } from '@/lib/pasien';

type ApprovalRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ApprovalRow[];
    };
    filters: {
        month: string;
        year: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Data Persetujuan SEP', href: sepApprovals() },
        ],
    },
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);
const loading = ref(false);

const monthOptions = [
    { label: 'Januari', value: '1' },
    { label: 'Februari', value: '2' },
    { label: 'Maret', value: '3' },
    { label: 'April', value: '4' },
    { label: 'Mei', value: '5' },
    { label: 'Juni', value: '6' },
    { label: 'Juli', value: '7' },
    { label: 'Agustus', value: '8' },
    { label: 'September', value: '9' },
    { label: 'Oktober', value: '10' },
    { label: 'November', value: '11' },
    { label: 'Desember', value: '12' },
];

watch(
    () => props.filters,
    (filters) => {
        month.value = filters.month;
        year.value = filters.year;
    },
);

function applyFilters(): void {
    router.get(
        sepApprovals.url(),
        {
            month: month.value,
            year: year.value,
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
    const hariIni = parseDate(tanggalHariIni());

    month.value = String(hariIni.month);
    year.value = String(hariIni.year);
    applyFilters();
}

</script>

<template>
    <Head title="Data Persetujuan SEP" />

    <div class="contents">
        <PageHeader
            title="Data Persetujuan SEP"
            description="Daftar persetujuan SEP dari VClaim berdasarkan periode bulan."
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
                        Bulan
                        <AppSelect v-model="month" :options="monthOptions" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium">
                        Tahun
                        <Input v-model="year" maxlength="4" inputmode="numeric" />
                    </label>
                    <TombolAksiFilter
                        :processing="loading"
                        :disabled="year.length !== 4"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <TabelPersetujuanSep :result="result" :loading="loading" />
        </div>
    </div>
</template>
