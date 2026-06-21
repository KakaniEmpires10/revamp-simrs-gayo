<script setup lang="ts">
import type { Page } from '@inertiajs/core';
import { router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CalendarIcon, Check, ChevronsUpDown, RefreshCw, Save } from '@lucide/vue';
import { computed, ref, toRef, watch } from 'vue';
import {
    inpatientPlanDetail,
    inpatientPlanDoctors,
    inpatientPlanSpecialists,
    updateInpatientPlan,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { cn } from '@/lib/utils';

type RencanaRawatInapRow = Record<string, string | number | null | undefined>;

type ReferensiOption = {
    value: string;
    label: string;
    description?: string;
    meta?: Record<string, string | number | null | undefined>;
};

type DetailSep = {
    noSep?: string;
    tglSep?: string;
    jnsPelayanan?: string;
    poli?: string;
    diagnosa?: string;
    peserta?: {
        noKartu?: string;
        nama?: string;
        tglLahir?: string;
        kelamin?: string;
        hakKelas?: string;
    };
};

type DetailSpri = Record<string, unknown> & {
    noSuratKontrol?: string;
    tglRencanaKontrol?: string;
    tglTerbit?: string;
    jnsKontrol?: string;
    namaJnsKontrol?: string;
    poliTujuan?: string;
    namaPoliTujuan?: string;
    kodeDokter?: string;
    namaDokter?: string;
    kodeDokterPembuat?: string;
    namaDokterPembuat?: string;
    sep?: DetailSep | null;
};

type DetailPayload = {
    metadata?: {
        code?: string;
        message?: string;
    };
    metaData?: {
        code?: string;
        message?: string;
    };
    response?: DetailSpri;
    kontrol?: DetailSpri;
};

const props = defineProps<{
    selectedRow: RencanaRawatInapRow | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const detail = ref<DetailSpri | null>(null);
const detailLoading = ref(false);
const detailFailure = ref<{ code: string; message: string } | null>(null);
const initializing = ref(false);

const clinicOpen = ref(false);
const clinicLoading = ref(false);
const clinicError = ref('');
const clinicOptions = ref<ReferensiOption[]>([]);

const doctorOpen = ref(false);
const doctorLoading = ref(false);
const doctorError = ref('');
const doctorOptions = ref<ReferensiOption[]>([]);

let clinicRequestId = 0;
let doctorRequestId = 0;

const form = useForm({
    tanggal_kontrol: '',
    poli_kontrol: '',
    nama_poli: '',
    kode_dokter: '',
    nama_dokter: '',
});

const tanggalKontrol = useTanggalCalendar(toRef(form, 'tanggal_kontrol'));

const noSurat = computed(() => value(props.selectedRow, 'noSPRI', 'noSuratKontrol', 'noSurat'));
const noKartu = computed(() => stringValue(detail.value?.sep?.peserta?.noKartu) || value(props.selectedRow, 'noKartu'));
const namaPasien = computed(() => stringValue(detail.value?.sep?.peserta?.nama) || value(props.selectedRow, 'nama'));
const tanggalLahir = computed(() => stringValue(detail.value?.sep?.peserta?.tglLahir) || value(props.selectedRow, 'tglLahir'));
const jenisKelamin = computed(() => stringValue(detail.value?.sep?.peserta?.kelamin) || value(props.selectedRow, 'kelamin'));
const kelasRawat = computed(() => stringValue(detail.value?.sep?.peserta?.hakKelas) || value(props.selectedRow, 'hakKelas'));
const noSep = computed(() => stringValue(detail.value?.sep?.noSep) || value(props.selectedRow, 'noSep', 'noSEP', 'noSepAsalKontrol'));
const diagnosaSep = computed(() => stringValue(detail.value?.sep?.diagnosa) || value(props.selectedRow, 'diagnosa'));
const poliAsal = computed(() => stringValue(detail.value?.sep?.poli) || value(props.selectedRow, 'poli'));
const tglSep = computed(() => stringValue(detail.value?.sep?.tglSep) || value(props.selectedRow, 'tglSep'));
const tglTerbit = computed(() => stringValue(detail.value?.tglTerbit) || value(props.selectedRow, 'tglTerbit'));

const selectedClinic = computed(() => clinicOptions.value.find((option) => option.value === form.poli_kontrol));
const selectedDoctor = computed(() => doctorOptions.value.find((option) => option.value === form.kode_dokter));
const bisaSubmit = computed(() => Boolean(noSurat.value && form.tanggal_kontrol && form.poli_kontrol && form.kode_dokter));

const clinicEmptyText = computed(() => {
    if (clinicLoading.value) {
        return 'Mengambil spesialis/subspesialis dari BPJS...';
    }

    return clinicError.value || 'Tidak ada spesialis/subspesialis yang tersedia untuk tanggal ini.';
});

const doctorEmptyText = computed(() => {
    if (doctorLoading.value) {
        return 'Mengambil jadwal dokter dari BPJS...';
    }

    if (doctorError.value) {
        return doctorError.value;
    }

    return form.poli_kontrol
        ? 'Tidak ada dokter yang tersedia untuk poli dan tanggal ini.'
        : 'Pilih spesialis/subspesialis terlebih dahulu.';
});

function stringValue(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}

function value(row: RencanaRawatInapRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '';
}

function queryUrl(url: string, query: Record<string, string>): string {
    return `${url}?${new URLSearchParams(query).toString()}`;
}

async function fetchJson(url: string): Promise<DetailPayload & { rows?: Record<string, string | number | null | undefined>[] }> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Server tidak dapat mengambil data BPJS.');
    }

    return await response.json();
}

function payloadMetadata(payload: DetailPayload): { code: string; message: string } {
    return {
        code: stringValue(payload.metadata?.code ?? payload.metaData?.code ?? 'UNKNOWN'),
        message: stringValue(payload.metadata?.message ?? payload.metaData?.message ?? 'BPJS tidak mengembalikan pesan.'),
    };
}

function resetReferences(): void {
    clinicOptions.value = [];
    doctorOptions.value = [];
    clinicError.value = '';
    doctorError.value = '';
    clinicOpen.value = false;
    doctorOpen.value = false;
}

function rememberExistingOptions(): void {
    if (form.poli_kontrol && !clinicOptions.value.some((option) => option.value === form.poli_kontrol)) {
        clinicOptions.value.unshift({
            value: form.poli_kontrol,
            label: form.nama_poli || form.poli_kontrol,
            description: 'Data SPRI tersimpan',
        });
    }

    if (form.kode_dokter && !doctorOptions.value.some((option) => option.value === form.kode_dokter)) {
        doctorOptions.value.unshift({
            value: form.kode_dokter,
            label: form.nama_dokter || form.kode_dokter,
            description: 'Data SPRI tersimpan',
        });
    }
}

function mapClinicRows(rows: Record<string, string | number | null | undefined>[]): ReferensiOption[] {
    return rows
        .map((row) => ({
            value: stringValue(row.kodePoli ?? row.kdPoli ?? row.poliTujuan ?? row.kode),
            label: stringValue(row.namaPoli ?? row.nmPoli ?? row.namaPoliTujuan ?? row.nama ?? row.kodePoli),
            description: row.kapasitas !== undefined ? `Kapasitas ${row.kapasitas ?? '-'}` : stringValue(row.keterangan ?? row.description),
            meta: row,
        }))
        .filter((option) => option.value !== '');
}

function mapDoctorRows(rows: Record<string, string | number | null | undefined>[]): ReferensiOption[] {
    return rows
        .map((row) => ({
            value: stringValue(row.kodeDokter ?? row.kdDokter ?? row.kode),
            label: stringValue(row.namaDokter ?? row.nmDokter ?? row.nama ?? row.kodeDokter),
            description: [
                row.jadwalPraktek ? `Jadwal ${row.jadwalPraktek}` : '',
                row.kapasitas !== undefined ? `Kapasitas ${row.kapasitas ?? '-'}` : '',
            ].filter(Boolean).join(' | '),
            meta: row,
        }))
        .filter((option) => option.value !== '');
}

async function loadDetail(): Promise<void> {
    if (!noSurat.value) {
        detailFailure.value = {
            code: 'NO_SPRI',
            message: 'Nomor SPRI tidak tersedia pada baris yang dipilih.',
        };

        return;
    }

    detailLoading.value = true;
    detailFailure.value = null;
    detail.value = null;
    initializing.value = true;
    resetReferences();
    form.clearErrors();

    try {
        const payload = await fetchJson(inpatientPlanDetail.url(noSurat.value));
        const metadata = payloadMetadata(payload);
        const response = payload.kontrol ?? payload.response ?? null;

        if (metadata.code !== '200' || !response) {
            detailFailure.value = metadata;

            return;
        }

        detail.value = response;
        form.tanggal_kontrol = stringValue(response.tglRencanaKontrol);
        form.poli_kontrol = stringValue(response.poliTujuan);
        form.nama_poli = stringValue(response.namaPoliTujuan);
        form.kode_dokter = stringValue(response.kodeDokter) || stringValue(response.kodeDokterPembuat);
        form.nama_dokter = stringValue(response.namaDokter) || stringValue(response.namaDokterPembuat);

        rememberExistingOptions();
    } catch (error) {
        detailFailure.value = {
            code: 'CONNECTION_ERROR',
            message: error instanceof Error ? error.message : 'Gagal terhubung ke layanan BPJS.',
        };
    } finally {
        initializing.value = false;
        detailLoading.value = false;
    }
}

async function loadClinics(): Promise<void> {
    if (!noKartu.value || !form.tanggal_kontrol) {
        rememberExistingOptions();

        return;
    }

    const requestId = ++clinicRequestId;

    clinicLoading.value = true;
    clinicError.value = '';

    try {
        const payload = await fetchJson(queryUrl(inpatientPlanSpecialists.url(), {
            no_kartu: noKartu.value,
            tanggal_kontrol: form.tanggal_kontrol,
        }));
        const metadata = payloadMetadata(payload);

        if (requestId !== clinicRequestId) {
            return;
        }

        if (metadata.code !== '200') {
            clinicOptions.value = [];
            clinicError.value = `BPJS: ${metadata.message}`;
            rememberExistingOptions();

            return;
        }

        clinicOptions.value = mapClinicRows(payload.rows ?? []);
        rememberExistingOptions();
    } catch (error) {
        if (requestId !== clinicRequestId) {
            return;
        }

        clinicOptions.value = [];
        clinicError.value = error instanceof Error ? error.message : 'Gagal mengambil referensi poli BPJS.';
        rememberExistingOptions();
    } finally {
        if (requestId === clinicRequestId) {
            clinicLoading.value = false;
        }
    }
}

async function loadDoctors(): Promise<void> {
    if (!form.poli_kontrol || !form.tanggal_kontrol) {
        rememberExistingOptions();

        return;
    }

    const requestId = ++doctorRequestId;

    doctorLoading.value = true;
    doctorError.value = '';

    try {
        const payload = await fetchJson(queryUrl(inpatientPlanDoctors.url(), {
            poli_kontrol: form.poli_kontrol,
            tanggal_kontrol: form.tanggal_kontrol,
        }));
        const metadata = payloadMetadata(payload);

        if (requestId !== doctorRequestId) {
            return;
        }

        if (metadata.code !== '200') {
            doctorOptions.value = [];
            doctorError.value = `BPJS: ${metadata.message}`;
            rememberExistingOptions();

            return;
        }

        doctorOptions.value = mapDoctorRows(payload.rows ?? []);
        rememberExistingOptions();
    } catch (error) {
        if (requestId !== doctorRequestId) {
            return;
        }

        doctorOptions.value = [];
        doctorError.value = error instanceof Error ? error.message : 'Gagal mengambil jadwal dokter BPJS.';
        rememberExistingOptions();
    } finally {
        if (requestId === doctorRequestId) {
            doctorLoading.value = false;
        }
    }
}

function selectClinic(option: ReferensiOption): void {
    form.poli_kontrol = option.value;
    form.nama_poli = option.label;
    form.kode_dokter = '';
    form.nama_dokter = '';
    doctorOptions.value = [];
    doctorRequestId++;
    clinicOpen.value = false;

    void loadDoctors();
}

function selectDoctor(option: ReferensiOption): void {
    form.kode_dokter = option.value;
    form.nama_dokter = option.label;
    doctorOpen.value = false;
}

function submit(): void {
    if (!noSurat.value) {
        return;
    }

    router.patch(updateInpatientPlan.url(noSurat.value), {
        tanggal_kontrol: form.tanggal_kontrol,
        poli_kontrol: form.poli_kontrol,
        kode_dokter: form.kode_dokter,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['result', 'filters']),
        reset: ['result'],
        onStart: () => {
            form.processing = true;
            form.clearErrors();
        },
        onSuccess: (page: Page) => {
            if (isFeedbackSuccess(page)) {
                open.value = false;
            }
        },
        onError: (errors: Record<string, string>) => {
            form.errors = errors;
        },
        onFinish: () => {
            form.processing = false;
        },
    });
}

watch(
    () => [open.value, noSurat.value] as const,
    ([isOpen]) => {
        if (!isOpen) {
            resetReferences();
            detail.value = null;
            detailFailure.value = null;
            form.reset();
            form.clearErrors();

            return;
        }

        void loadDetail();
    },
    { flush: 'post' },
);

watch(
    () => form.tanggal_kontrol,
    (newDate, oldDate) => {
        if (!open.value || initializing.value || detailFailure.value || newDate === oldDate) {
            return;
        }

        form.poli_kontrol = '';
        form.nama_poli = '';
        form.kode_dokter = '';
        form.nama_dokter = '';
        resetReferences();

        void loadClinics();
    },
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit SPRI</DialogTitle>
                <DialogDescription>
                    Data edit diambil ulang dari BPJS berdasarkan nomor SPRI sebelum form ditampilkan.
                </DialogDescription>
            </DialogHeader>

            <div v-if="detailLoading" class="grid gap-4">
                <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-blue-500/8 p-4">
                    <div class="space-y-3">
                        <Skeleton class="h-5 w-32" />
                        <Skeleton class="h-7 w-56" />
                        <div class="flex flex-wrap gap-2">
                            <Skeleton class="h-6 w-28" />
                            <Skeleton class="h-6 w-36" />
                            <Skeleton class="h-6 w-20" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 rounded-md border border-info/20 bg-info/10 px-4 py-6 text-sm text-info">
                    <Spinner />
                    Mengambil detail SPRI dari BPJS...
                </div>
            </div>

            <div v-else-if="detailFailure" class="grid gap-5">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-warning/15 text-warning">
                    <AlertTriangle class="size-7" />
                </div>

                <div class="space-y-2 text-center">
                    <h3 class="text-lg font-semibold">
                        Data SPRI tidak dapat dimuat
                    </h3>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ detailFailure.message }}
                    </p>
                    <Badge variant="soft-warning" size="sm" class="font-mono">
                        Kode BPJS: {{ detailFailure.code }}
                    </Badge>
                </div>

                <DialogFooter class="justify-center sm:justify-center">
                    <Button type="button" variant="secondary" @click="open = false">
                        Tutup
                    </Button>
                    <Button type="button" variant="outline" @click="loadDetail">
                        <RefreshCw class="size-4" />
                        Coba Lagi
                    </Button>
                </DialogFooter>
            </div>

            <form v-else class="grid gap-5" @submit.prevent="submit">
                <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-blue-500/8 p-4 shadow-sm">
                    <div class="relative grid gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase text-muted-foreground">
                                Data Pasien
                            </p>
                            <p class="truncate text-lg font-semibold">
                                {{ namaPasien || '-' }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="soft-info" size="sm" class="font-mono">
                                    No Kartu: {{ noKartu || '-' }}
                                </Badge>
                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                    SPRI: {{ noSurat || '-' }}
                                </Badge>
                                <Badge variant="muted" size="sm">
                                    {{ jenisKelamin || '-' }}
                                </Badge>
                                <Badge variant="soft-warning" size="sm">
                                    Kelas {{ kelasRawat || '-' }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                                <p class="text-xs text-muted-foreground">
                                    SEP Asal
                                </p>
                                <p class="mt-1 truncate font-mono font-medium">
                                    {{ noSep || '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                                <p class="text-xs text-muted-foreground">
                                    Tanggal SEP / Terbit
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ tglSep || '-' }} / {{ tglTerbit || '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                                <p class="text-xs text-muted-foreground">
                                    Tanggal Lahir
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ tanggalLahir || '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                                <p class="text-xs text-muted-foreground">
                                    Poli SEP
                                </p>
                                <p class="mt-1 truncate font-medium">
                                    {{ poliAsal || '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Diagnosa SEP
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ diagnosaSep || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p
                    v-if="!noSep"
                    class="rounded-md border border-info/20 bg-info/10 px-3 py-2 text-xs leading-relaxed text-info"
                >
                    SPRI jenis kontrol 1 bisa tidak memiliki SEP asal. Perubahan tetap dikirim memakai nomor SPRI, dokter, poli, dan tanggal rencana.
                </p>

                <label class="grid gap-2">
                    <Label>Tanggal Rencana Rawat Inap</Label>
                    <Popover v-model:open="tanggalKontrol.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalKontrol.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalKontrol.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.tanggal_kontrol" />
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Spesialis/Subspesialis</Label>
                        <Popover v-model:open="clinicOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    role="combobox"
                                    :aria-expanded="clinicOpen"
                                    :disabled="clinicLoading"
                                    class="h-10 w-full justify-between overflow-hidden bg-background px-3"
                                >
                                    <span class="truncate text-left">
                                        {{ selectedClinic?.label ?? (clinicLoading ? 'Memuat poli SPRI...' : 'Pilih spesialis/subspesialis') }}
                                    </span>
                                    <Spinner v-if="clinicLoading" class="size-4 shrink-0" />
                                    <ChevronsUpDown v-else class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Cari spesialis/subspesialis..." />
                                    <CommandList>
                                        <CommandEmpty v-if="clinicOptions.length > 0 && !clinicError">
                                            Tidak ada spesialis/subspesialis yang sesuai dengan pencarian.
                                        </CommandEmpty>

                                        <div v-if="clinicOptions.length === 0" class="px-3 py-5 text-center text-sm">
                                            <Spinner v-if="clinicLoading" class="mx-auto mb-2" />
                                            <p class="font-medium" :class="clinicError ? 'text-destructive' : 'text-foreground'">
                                                {{ clinicLoading ? 'Memuat referensi poli' : (clinicError ? 'Referensi poli gagal dimuat' : 'Poli tidak tersedia') }}
                                            </p>
                                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                                {{ clinicEmptyText }}
                                            </p>
                                        </div>

                                        <CommandGroup>
                                            <CommandItem
                                                v-for="clinic in clinicOptions"
                                                :key="clinic.value"
                                                :value="`${clinic.value} ${clinic.label}`"
                                                @select="selectClinic(clinic)"
                                            >
                                                <Check :class="cn('size-4', clinic.value === form.poli_kontrol ? 'opacity-100' : 'opacity-0')" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">
                                                        {{ clinic.label }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted-foreground">
                                                        {{ clinic.value }} {{ clinic.description ? `- ${clinic.description}` : '' }}
                                                    </p>
                                                </div>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <InputError :message="form.errors.poli_kontrol" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Dokter DPJP BPJS</Label>
                        <Popover v-model:open="doctorOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    role="combobox"
                                    :aria-expanded="doctorOpen"
                                    :disabled="!form.poli_kontrol || doctorLoading"
                                    class="h-10 w-full justify-between overflow-hidden bg-background px-3"
                                >
                                    <span class="truncate text-left">
                                        {{ selectedDoctor?.label ?? (doctorLoading ? 'Memuat dokter...' : 'Pilih dokter DPJP BPJS') }}
                                    </span>
                                    <Spinner v-if="doctorLoading" class="size-4 shrink-0" />
                                    <ChevronsUpDown v-else class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Cari dokter..." />
                                    <CommandList>
                                        <CommandEmpty v-if="doctorOptions.length > 0 && !doctorError">
                                            Tidak ada dokter yang sesuai dengan pencarian.
                                        </CommandEmpty>

                                        <div v-if="doctorOptions.length === 0" class="px-3 py-5 text-center text-sm">
                                            <Spinner v-if="doctorLoading" class="mx-auto mb-2" />
                                            <p class="font-medium" :class="doctorError ? 'text-destructive' : 'text-foreground'">
                                                {{ doctorLoading ? 'Memuat jadwal dokter' : (doctorError ? 'Jadwal dokter gagal dimuat' : 'Jadwal dokter tidak tersedia') }}
                                            </p>
                                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                                {{ doctorEmptyText }}
                                            </p>
                                        </div>

                                        <CommandGroup>
                                            <CommandItem
                                                v-for="doctor in doctorOptions"
                                                :key="doctor.value"
                                                :value="`${doctor.value} ${doctor.label}`"
                                                @select="selectDoctor(doctor)"
                                            >
                                                <Check :class="cn('size-4', doctor.value === form.kode_dokter ? 'opacity-100' : 'opacity-0')" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">
                                                        {{ doctor.label }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted-foreground">
                                                        {{ doctor.value }} {{ doctor.description ? `- ${doctor.description}` : '' }}
                                                    </p>
                                                </div>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <InputError :message="form.errors.kode_dokter" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || clinicLoading || doctorLoading || !bisaSubmit">
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
