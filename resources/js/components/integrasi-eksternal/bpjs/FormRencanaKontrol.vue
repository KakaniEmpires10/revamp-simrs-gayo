<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, CalendarIcon, Check, ChevronsUpDown, ClipboardList, RefreshCw, Save } from '@lucide/vue';
import { computed, ref, toRef, watch } from 'vue';
import { parseDate } from '@internationalized/date';
import {
    controlPlanDoctors,
    controlPlanDetail,
    controlPlanSpecialists,
    controlPlanBySepDetail,
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
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { cn } from '@/lib/utils';

type Mode = 'create' | 'update';
type Method = 'post' | 'patch';

type ReferenceOption = {
    value: string;
    label: string;
    description?: string;
    meta?: Record<string, string | number | null | undefined>;
};

type PatientContext = {
    noSep: string;
    tglSep: string;
    jenisPelayanan: string;
    poli: string;
    diagnosa: string;
    noKartu: string;
    nama: string;
    tglLahir: string;
    kelamin: string;
    kelasRawat: string;
};

type SepDetail = {
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

type ControlPlanDetail = Record<string, unknown> & {
    noSuratKontrol?: string;
    tglRencanaKontrol?: string;
    tglTerbit?: string;
    jnsKontrol?: string;
    poliTujuan?: string;
    namaPoliTujuan?: string;
    kodeDokter?: string;
    namaDokter?: string;
    kodeDokterPembuat?: string;
    namaDokterPembuat?: string;
    sep?: SepDetail | null;
};

const props = withDefaults(defineProps<{
    mode: Mode;
    actionUrl: string;
    method: Method;
    referenceNumber: string;
    title?: string;
    description?: string;
}>(), {
    title: 'Form Surat Kontrol / SKDP',
    description: 'Lengkapi rencana kontrol pasien. Data akan dikirim ke BPJS VClaim dan disimpan ke sistem rumah sakit setelah berhasil.',
});

const open = defineModel<boolean>('open', { default: false });

const specialistOpen = ref(false);
const doctorOpen = ref(false);

const specialistLoading = ref(false);
const doctorLoading = ref(false);

const specialistOptions = ref<ReferenceOption[]>([]);
const doctorOptions = ref<ReferenceOption[]>([]);

const controlDetail = ref<ControlPlanDetail | null>(null);
const sepDetail = ref<SepDetail | null>(null);

const specialistError = ref('');
const doctorError = ref('');
const detailError = ref('');
const detailErrorCode = ref('');
const detailLoading = ref(false);

const initialControlDate = ref('');
const initializing = ref(false);

/**
 * Khusus mode update:
 *
 * false:
 * - Dialog baru dibuka.
 * - Tanggal belum pernah diubah user.
 * - Poli dan dokter tetap dikunci agar data existing tidak berubah tanpa sengaja.
 *
 * true:
 * - User sudah pernah mengubah tanggal.
 * - Poli dan dokter masuk alur pemilihan ulang seperti create.
 * - Walaupun tanggal dikembalikan ke initialControlDate, field tetap boleh dipilih ulang.
 */
const scheduleTouched = ref(false);

/**
 * Untuk mencegah response referensi lama menimpa response terbaru
 * ketika user mengganti tanggal/poli dengan cepat.
 */
let specialistRequestId = 0;
let doctorRequestId = 0;

const form = useForm({
    no_sep: '',
    kode_dokter: '',
    nama_dokter: '',
    poli_kontrol: '',
    nama_poli: '',
    tanggal_kontrol: '',
});

const tanggalKontrol = useTanggalCalendar(toRef(form, 'tanggal_kontrol'));

/**
 * Key inisialisasi dialog.
 *
 * Ini membuat dialog initialize ulang ketika:
 * - open berubah dari false ke true
 * - mode berubah
 * - referenceNumber berubah
 *
 * Jadi modal tidak bergantung pada data pasien lama ketika parent mengubah
 * selected pasien dan open dialog pada tick yang sama.
 */
const dialogKey = computed(() => `${props.mode}:${props.referenceNumber}`);

const selectedSpecialist = computed(() => {
    return specialistOptions.value.find((option) => option.value === form.poli_kontrol);
});

const selectedDoctor = computed(() => {
    return doctorOptions.value.find((option) => option.value === form.kode_dokter);
});

const activeContext = computed<PatientContext | null>(() => {
    if (props.mode === 'update') {
        return mapSepToPatientContext(controlDetail.value?.sep);
    }

    return mapSepToPatientContext(sepDetail.value);
});

const isOutpatientVisit = computed(() => {
    const service = activeContext.value?.jenisPelayanan?.toLowerCase() ?? '';

    return service === '2' || service.includes('jalan');
});

const specialistEmptyText = computed(() => {
    if (specialistLoading.value) {
        return 'Mengambil daftar poli dari BPJS sesuai tanggal kontrol...';
    }

    if (specialistError.value) {
        return specialistError.value;
    }

    return isOutpatientVisit.value
        ? 'Tidak ada poli rujukan balik/kontrol yang tersedia untuk tanggal ini.'
        : 'Tidak ada poli kontrol yang tersedia untuk tanggal ini.';
});

const doctorEmptyText = computed(() => {
    if (doctorLoading.value) {
        return 'Mengambil jadwal dokter dari BPJS sesuai poli dan tanggal kontrol...';
    }

    if (doctorError.value) {
        return doctorError.value;
    }

    return form.poli_kontrol
        ? 'Tidak ada jadwal dokter untuk poli dan tanggal kontrol yang dipilih.'
        : 'Pilih poli tujuan kontrol terlebih dahulu.';
});

const submitLabel = computed(() => props.mode === 'create' ? 'Buat SKDP' : 'Simpan Perubahan');
const submitIcon = computed(() => props.mode === 'create' ? ClipboardList : Save);

/**
 * Di mode create, poli/dokter langsung bisa dipilih.
 *
 * Di mode update, poli/dokter hanya bisa dipilih setelah user mengubah tanggal.
 * Setelah pernah diubah, tetap terbuka walaupun tanggal dikembalikan ke tanggal awal.
 */
const canChangeSchedule = computed(() => props.mode === 'create' || scheduleTouched.value);

const issuedDate = computed(() => stringValue(controlDetail.value?.tglTerbit));
const detailFailure = computed(() => {
    if (props.mode !== 'update' || detailLoading.value || !detailError.value) {
        return null;
    }

    return {
        code: detailErrorCode.value || 'BPJS_ERROR',
        message: detailError.value,
    };
});

const scheduleNote = computed(() => {
    if (props.mode === 'update' && !scheduleTouched.value) {
        return 'Poli dan dokter masih memakai data SKDP yang tersimpan. Ubah tanggal rencana kontrol jika petugas perlu memilih ulang poli dan dokter dari jadwal BPJS.';
    }

    if (props.mode === 'update') {
        return 'Tanggal rencana kontrol sudah diubah. Silakan pilih ulang poli dan dokter sesuai jadwal BPJS pada tanggal tersebut.';
    }

    return 'Pilih tanggal rencana kontrol, lalu sistem akan memuat poli dan dokter yang tersedia dari jadwal BPJS.';
});

function stringValue(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}

function mapSepToPatientContext(sep?: SepDetail | null): PatientContext | null {
    if (!sep) {
        return null;
    }

    return {
        noSep: stringValue(sep.noSep),
        tglSep: stringValue(sep.tglSep),
        jenisPelayanan: stringValue(sep.jnsPelayanan),
        poli: stringValue(sep.poli),
        diagnosa: stringValue(sep.diagnosa),
        noKartu: stringValue(sep.peserta?.noKartu),
        nama: stringValue(sep.peserta?.nama),
        tglLahir: stringValue(sep.peserta?.tglLahir),
        kelamin: stringValue(sep.peserta?.kelamin),
        kelasRawat: stringValue(sep.peserta?.hakKelas),
    };
}

function datePlusDays(dateValue: string | undefined, days: number): string {
    if (!dateValue) {
        return '';
    }

    try {
        return parseDate(dateValue).add({ days }).toString();
    } catch {
        return '';
    }
}

function queryUrl(url: string, query: Record<string, string>): string {
    return `${url}?${new URLSearchParams(query).toString()}`;
}

function resetFormValues(): void {
    form.no_sep = '';
    form.kode_dokter = '';
    form.nama_dokter = '';
    form.poli_kontrol = '';
    form.nama_poli = '';
    form.tanggal_kontrol = '';
}

function resetReferences(): void {
    specialistOptions.value = [];
    doctorOptions.value = [];
    specialistError.value = '';
    doctorError.value = '';
}

function resetDialogState(): void {
    specialistOpen.value = false;
    doctorOpen.value = false;
}

async function fetchReference(url: string): Promise<{
    metadata?: {
        code?: string;
        message?: string;
    };
    rows?: Record<string, string | number | null | undefined>[];
}> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Gagal mengambil referensi BPJS.');
    }

    return await response.json();
}

function rememberExistingOptions(): void {
    if (props.mode !== 'update') {
        return;
    }

    if (
        form.poli_kontrol
        && form.nama_poli
        && !specialistOptions.value.some((option) => option.value === form.poli_kontrol)
    ) {
        specialistOptions.value.unshift({
            value: form.poli_kontrol,
            label: form.nama_poli,
            description: 'Data SKDP tersimpan',
        });
    }

    if (
        form.kode_dokter
        && form.nama_dokter
        && !doctorOptions.value.some((option) => option.value === form.kode_dokter)
    ) {
        doctorOptions.value.unshift({
            value: form.kode_dokter,
            label: form.nama_dokter,
            description: 'Data SKDP tersimpan',
        });
    }
}

async function loadSpecialists(): Promise<void> {
    if (!form.no_sep || !form.tanggal_kontrol) {
        return;
    }

    const requestId = ++specialistRequestId;

    specialistLoading.value = true;
    specialistError.value = '';
    doctorError.value = '';

    try {
        const result = await fetchReference(queryUrl(controlPlanSpecialists.url(), {
            no_sep: form.no_sep,
            tanggal_kontrol: form.tanggal_kontrol,
        }));

        if (requestId !== specialistRequestId) {
            return;
        }

        if (result.metadata?.code !== '200') {
            specialistError.value = `Gagal mengambil poli dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;
            specialistOptions.value = [];
            doctorOptions.value = [];

            rememberExistingOptions();

            return;
        }

        specialistOptions.value = (result.rows ?? [])
            .map((row) => ({
                value: String(row.kodePoli ?? ''),
                label: String(row.namaPoli ?? row.kodePoli ?? ''),
                description: `Kapasitas ${row.kapasitas ?? '-'}`,
                meta: row,
            }))
            .filter((option) => option.value !== '');

        rememberExistingOptions();
    } catch (error) {
        if (requestId !== specialistRequestId) {
            return;
        }

        specialistOptions.value = [];
        doctorOptions.value = [];
        specialistError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS: ${error.message}`
            : 'Gagal terhubung ke BPJS saat mengambil referensi poli.';

        rememberExistingOptions();
    } finally {
        if (requestId === specialistRequestId) {
            specialistLoading.value = false;
        }
    }
}

async function loadDoctors(): Promise<void> {
    if (!canChangeSchedule.value) {
        return;
    }

    if (!form.poli_kontrol || !form.tanggal_kontrol) {
        return;
    }

    const requestId = ++doctorRequestId;

    doctorLoading.value = true;
    doctorError.value = '';

    try {
        const result = await fetchReference(queryUrl(controlPlanDoctors.url(), {
            poli_kontrol: form.poli_kontrol,
            tanggal_kontrol: form.tanggal_kontrol,
        }));

        if (requestId !== doctorRequestId) {
            return;
        }

        if (result.metadata?.code !== '200') {
            doctorError.value = `Gagal mengambil dokter dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;
            doctorOptions.value = [];

            rememberExistingOptions();

            return;
        }

        doctorOptions.value = (result.rows ?? [])
            .map((row) => ({
                value: String(row.kodeDokter ?? ''),
                label: String(row.namaDokter ?? row.kodeDokter ?? ''),
                description: `Jadwal ${row.jadwalPraktek ?? '-'} | Kapasitas ${row.kapasitas ?? '-'}`,
                meta: row,
            }))
            .filter((option) => option.value !== '');

        rememberExistingOptions();
    } catch (error) {
        if (requestId !== doctorRequestId) {
            return;
        }

        doctorOptions.value = [];
        doctorError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS: ${error.message}`
            : 'Gagal terhubung ke BPJS saat mengambil jadwal dokter.';

        rememberExistingOptions();
    } finally {
        if (requestId === doctorRequestId) {
            doctorLoading.value = false;
        }
    }
}

async function loadSepDetail(): Promise<SepDetail | null> {
    if (!props.referenceNumber) {
        detailError.value = 'Nomor SEP tidak tersedia.';
        detailErrorCode.value = 'NO_SEP';

        return null;
    }

    try {
        const result = await fetchReference(
            controlPlanBySepDetail.url(props.referenceNumber),
        ) as {
            metadata?: {
                code?: string;
                message?: string;
            };
            sep?: SepDetail;
            response?: SepDetail;
        };

        if (result.metadata?.code !== '200') {
            detailErrorCode.value = String(result.metadata?.code ?? 'BPJS_ERROR');
            detailError.value = result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.';

            return null;
        }

        return result.sep ?? result.response ?? null;
    } catch (error) {
        detailErrorCode.value = 'CONNECTION_ERROR';
        detailError.value = error instanceof Error
            ? error.message
            : 'Gagal terhubung ke BPJS saat mengambil detail SEP.';

        return null;
    }
}

async function loadControlPlanDetail(): Promise<ControlPlanDetail | null> {
    if (!props.referenceNumber) {
        detailError.value = 'Nomor SKDP tidak tersedia.';
        detailErrorCode.value = 'NO_SKDP';

        return null;
    }

    try {
        const result = await fetchReference(
            controlPlanDetail.url(props.referenceNumber),
        ) as {
            metadata?: {
                code?: string;
                message?: string;
            };
            kontrol?: ControlPlanDetail;
            response?: ControlPlanDetail;
        };

        if (result.metadata?.code !== '200') {
            detailErrorCode.value = String(result.metadata?.code ?? 'BPJS_ERROR');
            detailError.value = result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.';

            return null;
        }

        return result.kontrol ?? result.response ?? null;
    } catch (error) {
        detailErrorCode.value = 'CONNECTION_ERROR';
        detailError.value = error instanceof Error
            ? error.message
            : 'Gagal terhubung ke BPJS saat mengambil detail SKDP.';

        return null;
    }
}

async function initializeForm(): Promise<void> {
    form.clearErrors();

    initializing.value = true;
    scheduleTouched.value = false;

    detailLoading.value = true;
    detailError.value = '';
    detailErrorCode.value = '';

    resetDialogState();
    resetReferences();
    resetFormValues();

    controlDetail.value = null;
    sepDetail.value = null;
    initialControlDate.value = '';

    try {
        if (props.mode === 'update') {
            const detail = await loadControlPlanDetail();

            controlDetail.value = detail;

            const sep = detail?.sep ?? null;

            form.no_sep = stringValue(sep?.noSep);
            form.kode_dokter = stringValue(detail?.kodeDokter) || stringValue(detail?.kodeDokterPembuat);
            form.nama_dokter = stringValue(detail?.namaDokter) || stringValue(detail?.namaDokterPembuat);
            form.poli_kontrol = stringValue(detail?.poliTujuan);
            form.nama_poli = stringValue(detail?.namaPoliTujuan);
            form.tanggal_kontrol = stringValue(detail?.tglRencanaKontrol);

            initialControlDate.value = form.tanggal_kontrol;

            rememberExistingOptions();

            return;
        }

        const sep = await loadSepDetail();

        sepDetail.value = sep;

        form.no_sep = stringValue(sep?.noSep);
        form.kode_dokter = '';
        form.nama_dokter = '';
        form.poli_kontrol = '';
        form.nama_poli = '';
        form.tanggal_kontrol = datePlusDays(sep?.tglSep, 7) || stringValue(sep?.tglSep);

        initialControlDate.value = form.tanggal_kontrol;

        if (form.no_sep && form.tanggal_kontrol) {
            await loadSpecialists();
        }
    } finally {
        initializing.value = false;
        detailLoading.value = false;
    }
}

function selectSpecialist(option: ReferenceOption): void {
    if (!canChangeSchedule.value) {
        return;
    }

    form.poli_kontrol = option.value;
    form.nama_poli = option.label;
    form.kode_dokter = '';
    form.nama_dokter = '';

    doctorOptions.value = [];
    doctorError.value = '';
    doctorRequestId++;
    specialistOpen.value = false;

    void loadDoctors();
}

function selectDoctor(option: ReferenceOption): void {
    if (!canChangeSchedule.value) {
        return;
    }

    form.kode_dokter = option.value;
    form.nama_dokter = option.label;
    doctorOpen.value = false;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            form.clearErrors();

            resetReferences();
            resetFormValues();
            resetDialogState();

            controlDetail.value = null;
            sepDetail.value = null;
            scheduleTouched.value = false;
            initialControlDate.value = '';
        },
    };

    if (props.method === 'patch') {
        form.patch(props.actionUrl, options);

        return;
    }

    form.post(props.actionUrl, options);
}

/**
 * Jangan hanya watch(open).
 * Parent biasanya mengubah selected pasien / mode / referenceNumber lalu membuka dialog
 * pada tick yang sama. Kalau hanya watch(open), modal bisa initialize memakai props lama.
 */
watch(
    () => [open.value, dialogKey.value] as const,
    async ([isOpen]) => {
        if (!isOpen) {
            resetDialogState();

            return;
        }

        await initializeForm();
    },
    {
        flush: 'post',
    },
);

/**
 * Watcher ini hanya boleh menganggap tanggal berubah sebagai aksi user
 * setelah fase initialize selesai.
 *
 * Guard `newDate === initialControlDate.value` penting untuk update mode,
 * karena beberapa date input bisa emit ulang value awal setelah mounted.
 */
watch(
    () => form.tanggal_kontrol,
    (newDate, oldDate) => {
        if (!open.value || initializing.value) {
            return;
        }

        if (newDate === oldDate) {
            return;
        }

        if (
            props.mode === 'update'
            && !scheduleTouched.value
            && newDate === initialControlDate.value
        ) {
            return;
        }

        if (props.mode === 'update') {
            scheduleTouched.value = true;
        }

        form.poli_kontrol = '';
        form.nama_poli = '';
        form.kode_dokter = '';
        form.nama_dokter = '';

        doctorRequestId++;

        resetReferences();

        if (form.tanggal_kontrol) {
            void loadSpecialists();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div v-if="detailFailure" class="grid gap-5">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-warning/15 text-warning">
                    <AlertTriangle class="size-7" />
                </div>

                <div class="space-y-2 text-center">
                    <h3 class="text-lg font-semibold">
                        Data SKDP tidak dapat dimuat
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
                    <Button type="button" variant="outline" @click="initializeForm">
                        <RefreshCw class="size-4" />
                        Coba Lagi
                    </Button>
                </DialogFooter>
            </div>

            <template v-else>
            <div
                class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-emerald-500/8 p-4">
                <svg class="pointer-events-none absolute -right-16 -bottom-24 h-52 w-96 rotate-[-14deg] text-primary/10"
                    viewBox="0 0 420 180" fill="none" aria-hidden="true">
                    <path d="M0 126C46 92 92 92 138 126C184 160 230 160 276 126C322 92 368 92 420 126V180H0V126Z"
                        fill="currentColor" />
                    <path d="M0 78C46 44 92 44 138 78C184 112 230 112 276 78C322 44 368 44 420 78" stroke="currentColor"
                        stroke-width="14" stroke-linecap="round" />
                </svg>

                <svg class="pointer-events-none absolute -top-14 -left-6 h-36 w-64 rotate-[-18deg] text-emerald-500/10"
                    viewBox="0 0 420 180" fill="none" aria-hidden="true">
                    <path d="M0 96C50 132 90 132 140 96C190 60 230 60 280 96C330 132 370 132 420 96"
                        stroke="currentColor" stroke-width="18" stroke-linecap="round" />
                </svg>

                <div class="relative space-y-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            Peserta
                        </p>

                        <p class="truncate text-lg font-semibold leading-tight">
                            <Skeleton v-if="detailLoading && !activeContext?.nama" class="h-6 w-56" />
                            <template v-else>
                                {{ activeContext?.nama || '-' }}
                            </template>
                        </p>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <Badge variant="soft-info" size="sm" class="font-mono">
                                <Skeleton v-if="detailLoading && !activeContext?.noKartu" class="h-4 w-28" />
                                <template v-else>
                                    {{ activeContext?.noKartu || '-' }}
                                </template>
                            </Badge>

                            <Badge variant="soft-primary" size="sm" class="font-mono">
                                <Skeleton v-if="detailLoading && !form.no_sep" class="h-4 w-32" />
                                <template v-else>
                                    {{ form.no_sep || '-' }}
                                </template>
                            </Badge>

                            <Badge variant="soft-warning" size="sm">
                                <Skeleton v-if="detailLoading && !activeContext?.kelasRawat" class="h-4 w-16" />
                                <template v-else>
                                    Kelas {{ activeContext?.kelasRawat || '-' }}
                                </template>
                            </Badge>
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                            <p class="text-xs text-muted-foreground">
                                Tanggal SEP
                            </p>

                            <p class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.tglSep" class="h-4 w-18" />
                                <template v-else>
                                    {{ activeContext?.tglSep || '-' }}
                                </template>
                            </p>
                        </div>

                        <div v-if="mode === 'update'"
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                            <p class="text-xs text-muted-foreground">
                                Tanggal terbit
                            </p>

                            <div class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !issuedDate" class="h-5 w-24" />
                                <template v-else>
                                    {{ issuedDate || '-' }}
                                </template>
                            </div>
                        </div>

                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur sm:col-span-2"
                            :class="mode === 'update' ? 'lg:col-span-2' : 'lg:col-span-3'">
                            <p class="text-xs text-muted-foreground">
                                Poli asal
                            </p>

                            <div class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.poli" class="h-5 w-full max-w-sm" />
                                <template v-else>
                                    {{ activeContext?.poli || '-' }}
                                </template>
                            </div>
                        </div>

                        <div
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur sm:col-span-2 lg:col-span-4">
                            <p class="text-xs text-muted-foreground">
                                Diagnosa
                            </p>

                            <div class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.diagnosa"
                                    class="h-5 w-full max-w-2xl" />
                                <template v-else>
                                    {{ activeContext?.diagnosa || '-' }}
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <p v-if="detailLoading || detailError" role="status"
                    class="rounded-md border px-3 py-2 text-xs leading-relaxed sm:col-span-2"
                    :class="detailError ? 'border-destructive/20 bg-destructive/10 text-destructive' : 'border-info/20 bg-info/10 text-info'">
                    <Spinner v-if="detailLoading" class="mr-2 inline-flex size-3" />
                    {{ detailError || (mode === 'create' ? 'Mengambil detail SEP dari BPJS...' : 'Mengambil detail SKDP dari BPJS...') }}
                </p>

                <p role="alert"
                    class="rounded-md border border-info/20 bg-info/10 px-3 py-2 text-xs leading-relaxed text-info sm:col-span-2">
                    {{ scheduleNote }}
                </p>

                <label class="grid gap-2">
                    <Label>Tanggal Rencana Kontrol</Label>
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

                <label class="grid gap-2">
                    <Label>Poli Tujuan Kontrol</Label>

                    <Popover v-model:open="specialistOpen">
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="h-10 w-full justify-between rounded-lg bg-background px-3"
                                :disabled="detailLoading || !canChangeSchedule">
                                <span class="truncate text-left">
                                    {{ selectedSpecialist?.label ?? (specialistLoading ? 'Memuat poli BPJS...' : 'Pilih poli tujuan kontrol') }}
                                </span>

                                <Spinner v-if="specialistLoading" class="size-4 shrink-0" />
                                <ChevronsUpDown v-else class="size-4 shrink-0 text-muted-foreground" />
                            </Button>
                        </PopoverTrigger>

                        <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Cari poli tujuan kontrol..." />

                                <CommandList>
                                    <CommandEmpty v-if="specialistOptions.length > 0 && !specialistError">
                                        Tidak ada poli yang sesuai dengan pencarian.
                                    </CommandEmpty>

                                    <div v-if="specialistOptions.length === 0" class="px-3 py-5 text-center text-sm">
                                        <Spinner v-if="specialistLoading" class="mx-auto mb-2" />
                                        <p class="font-medium" :class="specialistError ? 'text-destructive' : 'text-foreground'">
                                            {{ specialistLoading ? 'Memuat referensi poli' : (specialistError ? 'Referensi poli gagal dimuat' : 'Poli tidak tersedia') }}
                                        </p>
                                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                            {{ specialistEmptyText }}
                                        </p>
                                    </div>

                                    <CommandGroup>
                                        <CommandItem v-for="option in specialistOptions" :key="option.value"
                                            :value="`${option.value} ${option.label}`"
                                            @select="selectSpecialist(option)">
                                            <Check :class="cn(
                                                'size-4',
                                                option.value === form.poli_kontrol ? 'opacity-100' : 'opacity-0',
                                            )" />

                                            <div class="min-w-0">
                                                <p class="truncate font-medium">
                                                    {{ option.label }}
                                                </p>
                                                <p class="truncate text-xs text-muted-foreground">
                                                    {{ option.value }} - {{ option.description }}
                                                </p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <InputError :message="form.errors.poli_kontrol" />
                    <InputError :message="form.errors.nama_poli" />
                </label>

                <label class="grid gap-2 sm:col-span-2">
                    <Label>Dokter Kontrol</Label>

                    <Popover v-model:open="doctorOpen">
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="h-10 w-full justify-between rounded-lg bg-background px-3"
                                :disabled="detailLoading || !form.poli_kontrol || !canChangeSchedule">
                                <span class="truncate text-left">
                                    {{ selectedDoctor?.label ?? (doctorLoading ? 'Memuat dokter BPJS...' : 'Pilih dokter kontrol') }}
                                </span>

                                <Spinner v-if="doctorLoading" class="size-4 shrink-0" />
                                <ChevronsUpDown v-else class="size-4 shrink-0 text-muted-foreground" />
                            </Button>
                        </PopoverTrigger>

                        <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Cari dokter kontrol..." />

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
                                        <CommandItem v-for="option in doctorOptions" :key="option.value"
                                            :value="`${option.value} ${option.label}`" @select="selectDoctor(option)">
                                            <Check :class="cn(
                                                'size-4',
                                                option.value === form.kode_dokter ? 'opacity-100' : 'opacity-0',
                                            )" />

                                            <div class="min-w-0">
                                                <p class="truncate font-medium">
                                                    {{ option.label }}
                                                </p>
                                                <p class="truncate text-xs text-muted-foreground">
                                                    {{ option.value }} - {{ option.description }}
                                                </p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <InputError :message="form.errors.kode_dokter" />
                    <InputError :message="form.errors.nama_dokter" />
                </label>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="open = false">
                    Batal
                </Button>

                <Button :disabled="form.processing
                    || detailLoading
                    || specialistLoading
                    || doctorLoading
                    || !form.no_sep
                    || !form.kode_dokter
                    || !form.nama_dokter
                    || !form.poli_kontrol
                    || !form.nama_poli
                    || !form.tanggal_kontrol
                    " @click="submit">
                    <Spinner v-if="form.processing" />
                    <component :is="submitIcon" v-else class="size-4" />
                    {{ submitLabel }}
                </Button>
            </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
