<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import {
    destroyOutboundReferral,
    outboundReferrals,
    updateOutboundReferral,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import FormRujukanKeluar from '@/components/integrasi-eksternal/bpjs/FormRujukanKeluar.vue';
import TabelDataRujukan from '@/components/integrasi-eksternal/bpjs/vclaim/TabelDataRujukan.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';

type ReferralRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ReferralRow[];
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
            { title: 'Data Rujukan', href: outboundReferrals() },
        ],
    },
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const tanggalAwal = useTanggalCalendar(startDate);
const tanggalAkhir = useTanggalCalendar(endDate);
const loading = ref(false);
const selectedRow = ref<ReferralRow | null>(null);
const editDialogOpen = ref(false);
const { openDeleteDialog: openGlobalDeleteDialog } = useDeleteDialog();

watch(
    () => props.filters,
    (filters) => {
        startDate.value = filters.start_date;
        endDate.value = filters.end_date;
    },
);

function applyFilters(): void {
    router.get(
        outboundReferrals.url(),
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

function noopAction(event: Event): void {
    event.preventDefault();
}

function value(row: ReferralRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function referralNumber(row: ReferralRow | null): string {
    return value(row, 'noRujukan', 'noKunjungan');
}

function openEditDialog(row: ReferralRow): void {
    selectedRow.value = row;
    editDialogOpen.value = true;
}

function openDeleteDialog(row: ReferralRow): void {
    const noRujukan = referralNumber(row);

    openGlobalDeleteDialog({
        level: 'warning',
        title: 'Hapus Rujukan?',
        description: `Rujukan ${noRujukan} akan dihapus dari BPJS dan database lokal jika BPJS mengembalikan status berhasil.`,
        actionLabel: 'Hapus Rujukan',
        action: () =>
            new Promise<void>((resolve, reject) => {
                let hasError = false;

                router.delete(destroyOutboundReferral.url(noRujukan), {
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
    <Head title="Data Rujukan" />

    <div class="contents">
        <PageHeader
            title="Data Rujukan"
            description="Daftar rujukan keluar BPJS dari VClaim berdasarkan rentang tanggal."
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

            <TabelDataRujukan
                :result="result"
                :loading="loading"
                @edit="openEditDialog"
                @hapus="openDeleteDialog"
                @cetak="noopAction"
            />
        </div>

        <FormRujukanKeluar
            v-if="selectedRow"
            v-model:open="editDialogOpen"
            mode="update"
            method="patch"
            :reference-number="referralNumber(selectedRow)"
            :action-url="updateOutboundReferral.url(referralNumber(selectedRow))"
            title="Edit Rujukan"
            description="Perubahan akan dikirim ke VClaim dan disinkronkan ke database lokal jika BPJS mengembalikan status berhasil."
        />
    </div>
</template>
