<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import {
    destroySep,
    monitoringVisits,
    pullSep,
    storeControlPlan,
    storeOutboundReferral,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import FormRencanaKontrol from '@/components/integrasi-eksternal/bpjs/FormRencanaKontrol.vue';
import FormRujukanKeluar from '@/components/integrasi-eksternal/bpjs/FormRujukanKeluar.vue';
import DialogTarikSep from '@/components/integrasi-eksternal/bpjs/vclaim/DialogTarikSep.vue';
import TabelMonitoringKunjungan from '@/components/integrasi-eksternal/bpjs/vclaim/TabelMonitoringKunjungan.vue';
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

type VisitRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: VisitRow[];
    };
    filters: {
        date: string;
        service_type: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Monitoring Data Kunjungan', href: monitoringVisits() },
        ],
    },
});

const date = ref(props.filters.date);
const tanggalSep = useTanggalCalendar(date);
const serviceType = ref(props.filters.service_type);
const loading = ref(false);
const selectedRow = ref<VisitRow | null>(null);
const pullDialogOpen = ref(false);
const controlDialogOpen = ref(false);
const rujukDialogOpen = ref(false);
const { openDeleteDialog: openGlobalDeleteDialog } = useDeleteDialog();

const pullForm = useForm({
    no_sep: '',
    no_rawat: '',
});

watch(
    () => props.filters,
    (filters) => {
        date.value = filters.date;
        serviceType.value = filters.service_type;
    },
);

function applyFilters(): void {
    router.get(
        monitoringVisits.url(),
        {
            date: date.value,
            service_type: serviceType.value,
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
    serviceType.value = '2';
    applyFilters();
}

function noopAction(event: Event): void {
    event.preventDefault();
}

function rowValue(row: VisitRow | null, key: string): string {
    return String(row?.[key] ?? '');
}

function openPullDialog(row: VisitRow): void {
    selectedRow.value = row;
    pullForm.clearErrors();
    pullForm.no_sep = rowValue(row, 'noSep');
    pullForm.no_rawat = '';
    pullDialogOpen.value = true;
}

function openControlDialog(row: VisitRow): void {
    selectedRow.value = row;
    controlDialogOpen.value = true;
}

function openRujukDialog(row: VisitRow): void {
    selectedRow.value = row;
    rujukDialogOpen.value = true;
}

function openDeleteDialog(row: VisitRow): void {
    const noSep = rowValue(row, 'noSep');

    openGlobalDeleteDialog({
        level: 'warning',
        title: 'Hapus SEP?',
        description: `SEP "${noSep || '-'}" akan dihapus dari BPJS dan database lokal jika BPJS mengembalikan status berhasil.`,
        actionLabel: 'Hapus SEP',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroySep.url(noSep), {
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

function submitPullSep(): void {
    pullForm.post(pullSep.url(), {
        preserveScroll: true,
        onSuccess: () => {
            pullDialogOpen.value = false;
            pullForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Monitoring Data Kunjungan" />

    <div class="contents">
        <PageHeader
            title="Monitoring Data Kunjungan"
            description="Monitoring kunjungan peserta dari endpoint VClaim berdasarkan tanggal SEP dan jenis pelayanan."
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
                        Tanggal SEP
                        <Popover v-model:open="tanggalSep.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalSep.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalSep.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                    </label>
                    <label class="grid gap-2 text-sm font-medium">
                        Jenis Pelayanan
                        <AppSelect
                            v-model="serviceType"
                            :options="[
                                { label: 'Rawat Inap', value: '1' },
                                { label: 'Rawat Jalan', value: '2' },
                            ]"
                        />
                    </label>
                    <TombolAksiFilter
                        :processing="loading"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <TabelMonitoringKunjungan
                :result="result"
                :loading="loading"
                @edit-sep="noopAction"
                @cetak-sep="noopAction"
                @kontrol="openControlDialog"
                @rujuk="openRujukDialog"
                @tarik-sep="openPullDialog"
                @hapus-sep="openDeleteDialog"
            />
        </div>

        <DialogTarikSep
            v-model:open="pullDialogOpen"
            :form="pullForm"
            @submit="submitPullSep"
        />

        <FormRencanaKontrol
            v-model:open="controlDialogOpen"
            mode="create"
            method="post"
            :action-url="storeControlPlan.url()"
            :reference-number="rowValue(selectedRow, 'noSep')"
        />

        <FormRujukanKeluar
            v-model:open="rujukDialogOpen"
            mode="create"
            method="post"
            :action-url="storeOutboundReferral.url()"
            :reference-number="rowValue(selectedRow, 'noSep')"
        />
    </div>
</template>
