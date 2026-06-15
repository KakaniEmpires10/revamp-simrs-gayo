<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { fingerprints } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import TabelDataFingerprint from '@/components/integrasi-eksternal/bpjs/vclaim/TabelDataFingerprint.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';

type FingerprintPatient = {
    no_rkm_medis?: string | null;
    nm_pasien?: string | null;
    jk?: string | null;
};

type FingerprintRow = Record<string, string | number | FingerprintPatient | null | undefined> & {
    pasien?: FingerprintPatient | null;
};

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: FingerprintRow[];
    };
    filters: {
        date: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Data Finger Print', href: fingerprints() },
        ],
    },
});

const date = ref(props.filters.date);
const tanggalPelayanan = useTanggalCalendar(date);
const loading = ref(false);

watch(
    () => props.filters,
    (filters) => {
        date.value = filters.date;
    },
);

function applyFilters(): void {
    router.get(
        fingerprints.url(),
        { date: date.value },
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
    applyFilters();
}
</script>

<template>
    <Head title="Data Finger Print" />

    <div class="contents">
        <PageHeader
            title="Data Finger Print"
            description="Data validasi finger print peserta BPJS berdasarkan tanggal pelayanan."
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
                <CardContent class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <label class="grid gap-2 text-sm font-medium">
                        Tanggal Pelayanan
                        <Popover v-model:open="tanggalPelayanan.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalPelayanan.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalPelayanan.value" layout="month-and-year" />
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

            <TabelDataFingerprint :result="result" :loading="loading" />
        </div>
    </div>
</template>
