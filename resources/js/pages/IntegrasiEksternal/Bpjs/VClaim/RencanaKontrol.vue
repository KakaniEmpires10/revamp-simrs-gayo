<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import {
    controlPlans,
    destroyControlPlan,
    updateControlPlan,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import FormRencanaKontrol from '@/components/integrasi-eksternal/bpjs/FormRencanaKontrol.vue';
import TabelRencanaKontrol from '@/components/integrasi-eksternal/bpjs/vclaim/TabelRencanaKontrol.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';

type ControlPlanRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ControlPlanRow[];
    };
    filters: {
        start_date: string;
        end_date: string;
        filter: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Rencana Kontrol / SKDP', href: controlPlans() },
        ],
    },
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const tanggalAwal = useTanggalCalendar(startDate);
const tanggalAkhir = useTanggalCalendar(endDate);
const filter = ref(props.filters.filter);
const loading = ref(false);
const selectedRow = ref<ControlPlanRow | null>(null);
const editDialogOpen = ref(false);
const { openDeleteDialog: openGlobalDeleteDialog } = useDeleteDialog();

const filterOptions = [
    { label: 'Tanggal Entry', value: '1' },
    { label: 'Tanggal Rencana Kontrol', value: '2' },
];

watch(
    () => props.filters,
    (filters) => {
        startDate.value = filters.start_date;
        endDate.value = filters.end_date;
        filter.value = filters.filter;
    },
);

function applyFilters(): void {
    router.get(
        controlPlans.url(),
        {
            start_date: startDate.value,
            end_date: endDate.value,
            filter: filter.value,
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
    filter.value = '1';
    applyFilters();
}

function noopAction(event: Event): void {
    event.preventDefault();
}

function value(row: ControlPlanRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function letterNumber(row: ControlPlanRow | null): string {
    return value(row, 'noSuratKontrol', 'noSurat');
}

function openEditDialog(row: ControlPlanRow): void {
    selectedRow.value = row;
    editDialogOpen.value = true;
}

function openDeleteDialog(row: ControlPlanRow): void {
    const noSurat = letterNumber(row);

    openGlobalDeleteDialog({
        level: 'warning',
        title: 'Hapus SKDP?',
        description: `SKDP ${noSurat} akan dihapus dari BPJS dan database lokal jika BPJS mengembalikan status berhasil.`,
        actionLabel: 'Hapus SKDP',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroyControlPlan.url(noSurat), {
                    preserveScroll: true,
                    onError: () => {
                        hasError = true;
                        reject();
                    },
                    onFinish: () => {
                        if (!hasError) {
                            resolve();
                        }
                    },
                });
            }),
    });
}
</script>

<template>
    <Head title="Rencana Kontrol / SKDP" />

    <div class="contents">
        <PageHeader
            title="Rencana Kontrol / SKDP"
            description="Daftar surat kontrol rawat jalan dari VClaim."
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
                <CardContent class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
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
                    <label class="grid gap-2 text-sm font-medium">
                        Filter Tanggal
                        <AppSelect v-model="filter" :options="filterOptions" />
                    </label>
                    <TombolAksiFilter
                        :processing="loading"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <TabelRencanaKontrol
                :result="result"
                :loading="loading"
                @edit="openEditDialog"
                @hapus="openDeleteDialog"
                @cetak="noopAction"
            />
        </div>

        <FormRencanaKontrol
            v-model:open="editDialogOpen"
            mode="update"
            method="patch"
            title="Edit SKDP"
            description="Perubahan akan dikirim ke VClaim lalu disinkronkan ke tabel surat kontrol lokal."
            :action-url="updateControlPlan.url(letterNumber(selectedRow))"
            :reference-number="letterNumber(selectedRow)"
        />
    </div>
</template>
