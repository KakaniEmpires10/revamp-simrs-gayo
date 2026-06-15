<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import {
    destroyInpatientPlan,
    inpatientPlans,
    updateInpatientPlan,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import DialogEditRencanaRawatInap from '@/components/integrasi-eksternal/bpjs/vclaim/DialogEditRencanaRawatInap.vue';
import TabelRencanaRawatInap from '@/components/integrasi-eksternal/bpjs/vclaim/TabelRencanaRawatInap.vue';
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

type InpatientPlanRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: InpatientPlanRow[];
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
            { title: 'Rencana Rawat Inap / SPRI', href: inpatientPlans() },
        ],
    },
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const tanggalAwal = useTanggalCalendar(startDate);
const tanggalAkhir = useTanggalCalendar(endDate);
const filter = ref(props.filters.filter);
const loading = ref(false);
const selectedRow = ref<InpatientPlanRow | null>(null);
const editDialogOpen = ref(false);
const { openDeleteDialog: openGlobalDeleteDialog } = useDeleteDialog();

const editForm = useForm({
    kode_dokter: '',
    poli_kontrol: '',
    tanggal_kontrol: '',
});

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
        inpatientPlans.url(),
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

function value(row: InpatientPlanRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function letterNumber(row: InpatientPlanRow | null): string {
    return value(row, 'noSPRI', 'noSuratKontrol', 'noSurat');
}

function openEditDialog(row: InpatientPlanRow): void {
    selectedRow.value = row;
    editForm.clearErrors();
    editForm.kode_dokter = value(row, 'kodeDokter', 'kdDokter', 'kodeDokterKontrol') === '-' ? '' : value(row, 'kodeDokter', 'kdDokter', 'kodeDokterKontrol');
    editForm.poli_kontrol = value(row, 'kodePoliTujuan', 'kdPoliTujuan', 'kodePoli') === '-' ? '' : value(row, 'kodePoliTujuan', 'kdPoliTujuan', 'kodePoli');
    editForm.tanggal_kontrol = value(row, 'tglRencanaKontrol', 'tglKontrol') === '-' ? endDate.value : value(row, 'tglRencanaKontrol', 'tglKontrol');
    editDialogOpen.value = true;
}

function openDeleteDialog(row: InpatientPlanRow): void {
    const noSurat = letterNumber(row);

    openGlobalDeleteDialog({
        level: 'warning',
        title: 'Hapus SPRI?',
        description: `SPRI ${noSurat} akan dihapus dari BPJS dan database lokal jika BPJS mengembalikan status berhasil.`,
        actionLabel: 'Hapus SPRI',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroyInpatientPlan.url(noSurat), {
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

function submitEdit(): void {
    const noSurat = letterNumber(selectedRow.value);

    if (noSurat === '-') {
        return;
    }

    editForm.patch(updateInpatientPlan.url(noSurat), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
            selectedRow.value = null;
        },
    });
}
</script>

<template>
    <Head title="Rencana Rawat Inap / SPRI" />

    <div class="contents">
        <PageHeader
            title="Rencana Rawat Inap / SPRI"
            description="Daftar surat rencana rawat inap dari VClaim."
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

            <TabelRencanaRawatInap
                :result="result"
                :loading="loading"
                @edit="openEditDialog"
                @hapus="openDeleteDialog"
                @cetak="noopAction"
            />
        </div>

        <DialogEditRencanaRawatInap
            v-model:open="editDialogOpen"
            :form="editForm"
            :selected-row="selectedRow"
            @submit="submitEdit"
        />
    </div>
</template>
