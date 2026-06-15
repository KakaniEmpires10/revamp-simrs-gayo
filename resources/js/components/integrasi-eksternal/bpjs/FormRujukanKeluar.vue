<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { getLocalTimeZone, today } from '@internationalized/date';
import {
    CalendarIcon,
    ClipboardList,
    Save,
} from '@lucide/vue';
import { computed, ref, toRef, watch } from 'vue';
import {
    controlPlanBySepDetail,
    diagnosisReferences,
    outboundReferralDetail,
    providerReferences,
    specialistReferences,
} from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppAsyncCombobox from '@/components/ui/form/AppAsyncCombobox.vue';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import type { AppAsyncComboboxOption } from '@/components/ui/form/types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import { Spinner } from '@/components/ui/spinner';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';

type Mode = 'create' | 'update';
type Method = 'post' | 'patch';
type FaskesType = '1' | '2';

type SelectOption = {
    value: string;
    label: string;
};

type ReferenceOption = AppAsyncComboboxOption & {
    meta?: Record<string, unknown>;
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
    provUmum?: {
        kdProvider?: string;
        nmProvider?: string;
    };
    provPerujuk?: {
        kdProviderPerujuk?: string;
        nmProviderPerujuk?: string;
        asalRujukan?: string;
        noRujukan?: string;
        tglRujukan?: string;
    };
};

type OutboundReferralDetail = {
    noRujukan?: string;
    noSep?: string;
    noKartu?: string;
    nama?: string;
    kelasRawat?: string;
    kelamin?: string;
    tglLahir?: string;
    tglSep?: string;
    tglRujukan?: string;
    tglRencanaKunjungan?: string;
    ppkDirujuk?: string;
    namaPpkDirujuk?: string;
    jnsPelayanan?: string;
    catatan?: string;
    diagRujukan?: string;
    namaDiagRujukan?: string;
    tipeRujukan?: string;
    namaTipeRujukan?: string;
    poliRujukan?: string;
    namaPoliRujukan?: string;
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
    providerPerujuk: string;
    noRujukanAsal: string;
    tglRujukanAsal: string;
};

type ReferenceFetchResult = {
    metadata?: {
        code?: string;
        message?: string;
    };
    rows?: Record<string, string | number | null | undefined>[];
};

type OutboundReferralFetchResult = {
    metadata?: {
        code?: string;
        message?: string;
    };
    rujukan?: OutboundReferralDetail;
    response?: {
        rujukan?: OutboundReferralDetail;
    };
};

const props = withDefaults(defineProps<{
    mode: Mode;
    actionUrl: string;
    method: Method;
    referenceNumber: string;
    title?: string;
    description?: string;
}>(), {
    title: 'Form Rujukan',
    description: 'Lengkapi data rujukan. Data akan dikirim ke BPJS VClaim dan disinkronkan ke database lokal jika berhasil.',
});

const open = defineModel<boolean>('open', { default: false });

const minimumSearchLength = 3;
const searchDebounceMs = 350;

const detailLoading = ref(false);
const detailError = ref('');
const initializing = ref(false);

const sepDetail = ref<SepDetail | null>(null);

const faskesType = ref<FaskesType>('2');

const diagnosisOpen = ref(false);
const faskesOpen = ref(false);
const poliOpen = ref(false);

const isRanap = computed(() => form.jenis_pelayanan === '1');

const diagnosisKeyword = ref('');
const faskesKeyword = ref('');
const poliKeyword = ref('');

const diagnosisLoading = ref(false);
const faskesLoading = ref(false);
const poliLoading = ref(false);

const diagnosisError = ref('');
const faskesError = ref('');
const poliError = ref('');

const diagnosisOptions = ref<ReferenceOption[]>([]);
const faskesOptions = ref<ReferenceOption[]>([]);
const poliOptions = ref<ReferenceOption[]>([]);

let diagnosisRequestId = 0;
let faskesRequestId = 0;
let poliRequestId = 0;

const serviceOptions: SelectOption[] = [
    { value: '1', label: 'Rawat Inap' },
    { value: '2', label: 'Rawat Jalan' },
];

const referralTypeOptions: SelectOption[] = [
    { value: '0', label: 'Penuh' },
    { value: '1', label: 'Partial' },
    { value: '2', label: 'Balik PRB' },
];

const faskesTypeOptions: SelectOption[] = [
    { value: '1', label: 'Faskes 1' },
    { value: '2', label: 'Faskes 2 / RS' },
];

const form = useForm({
    no_sep: '',
    tanggal_rujukan: '',
    tanggal_rencana_kunjungan: '',
    ppk_dirujuk: '',
    nama_ppk_dirujuk: '',
    jenis_pelayanan: '2',
    catatan: '',
    diagnosa_rujukan: '',
    nama_diagnosa_rujukan: '',
    tipe_rujukan: '0',
    poli_rujukan: '',
    nama_poli_rujukan: '',
});

const tanggalRujukan = useTanggalCalendar(toRef(form, 'tanggal_rujukan'));
const tanggalRencanaKunjungan = useTanggalCalendar(toRef(form, 'tanggal_rencana_kunjungan'));

const dialogKey = computed(() => `${props.mode}:${props.referenceNumber}`);

const activeContext = computed<PatientContext | null>(() => {
    return mapSepToPatientContext(sepDetail.value);
});

const selectedDiagnosis = computed(() => {
    return diagnosisOptions.value.find((option) => option.value === form.diagnosa_rujukan);
});

const selectedFaskes = computed(() => {
    return faskesOptions.value.find((option) => option.value === form.ppk_dirujuk);
});

const selectedPoli = computed(() => {
    return poliOptions.value.find((option) => option.value === form.poli_rujukan);
});

const submitLabel = computed(() => props.mode === 'create' ? 'Buat Rujukan' : 'Simpan Perubahan');
const submitIcon = computed(() => props.mode === 'create' ? ClipboardList : Save);
const detailLoadingMessage = computed(() => props.mode === 'create'
    ? 'Mengambil detail SEP dari BPJS...'
    : 'Mengambil detail rujukan dari BPJS...');

const isPrbReferral = computed(() => form.tipe_rujukan === '2');

const canSubmit = computed(() => {
    if (form.processing || detailLoading.value) {
        return false;
    }

    if (
        !form.tanggal_rujukan
        || !form.tanggal_rencana_kunjungan
        || !form.ppk_dirujuk
        || !form.jenis_pelayanan
        || !form.diagnosa_rujukan
        || !form.tipe_rujukan
    ) {
        return false;
    }

    if (props.mode === 'create' && !form.no_sep) {
        return false;
    }

    if (!isPrbReferral.value && !isRanap.value && !form.poli_rujukan) {
        return false;
    }

    return true;
});

const faskesPlaceholder = computed(() => {
    return faskesType.value === '1'
        ? 'Pilih faskes tingkat 1'
        : 'Pilih faskes tingkat 2 / RS';
});

const diagnosisDisplayText = computed(() => {
    if (selectedDiagnosis.value?.description) {
        return selectedDiagnosis.value.description;
    }

    if (form.diagnosa_rujukan) {
        return `${form.diagnosa_rujukan} - ${form.nama_diagnosa_rujukan || '-'}`;
    }

    return 'Pilih diagnosa rujukan';
});

const faskesDisplayText = computed(() => {
    return (selectedFaskes.value?.label ?? form.nama_ppk_dirujuk) || faskesPlaceholder.value;
});

const poliDisplayText = computed(() => {
    if (isRanap.value && isPrbReferral.value) {
        return 'Tidak diperlukan untuk rawat inap dan rujukan balik PRB';
    }

    if (isRanap.value) {
        return 'Tidak diperlukan untuk rawat inap';
    }

    if (isPrbReferral.value) {
        return 'Tidak diperlukan untuk rujukan balik PRB';
    }

    return (selectedPoli.value?.label ?? form.nama_poli_rujukan) || 'Pilih poli rujukan';
});

function stringValue(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}

function normalizeKeyword(value: string): string {
    return value.trim();
}

function isTooShortKeyword(value: string): boolean {
    return normalizeKeyword(value).length < minimumSearchLength;
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
        providerPerujuk: [
            stringValue(sep.provPerujuk?.kdProviderPerujuk),
            stringValue(sep.provPerujuk?.nmProviderPerujuk),
        ].filter(Boolean).join(' - '),
        noRujukanAsal: stringValue(sep.provPerujuk?.noRujukan),
        tglRujukanAsal: stringValue(sep.provPerujuk?.tglRujukan),
    };
}

function resetFormValues(): void {
    form.no_sep = '';
    form.tanggal_rujukan = '';
    form.tanggal_rencana_kunjungan = '';
    form.ppk_dirujuk = '';
    form.nama_ppk_dirujuk = '';
    form.jenis_pelayanan = '2';
    form.catatan = '';
    form.diagnosa_rujukan = '';
    form.nama_diagnosa_rujukan = '';
    form.tipe_rujukan = '0';
    form.poli_rujukan = '';
    form.nama_poli_rujukan = '';
}

function resetReferenceKeywords(): void {
    diagnosisKeyword.value = '';
    faskesKeyword.value = '';
    poliKeyword.value = '';
}

function resetReferences(): void {
    diagnosisOptions.value = [];
    faskesOptions.value = [];
    poliOptions.value = [];

    diagnosisError.value = '';
    faskesError.value = '';
    poliError.value = '';
}

function resetReferenceLoading(): void {
    diagnosisLoading.value = false;
    faskesLoading.value = false;
    poliLoading.value = false;
}

function resetPopover(): void {
    diagnosisOpen.value = false;
    faskesOpen.value = false;
    poliOpen.value = false;
}

function cancelReferenceRequests(): void {
    diagnosisRequestId++;
    faskesRequestId++;
    poliRequestId++;
}

function rememberSelectedOptions(): void {
    if (
        form.diagnosa_rujukan
        && form.nama_diagnosa_rujukan
        && !diagnosisOptions.value.some((option) => option.value === form.diagnosa_rujukan)
    ) {
        diagnosisOptions.value.unshift({
            value: form.diagnosa_rujukan,
            label: form.nama_diagnosa_rujukan,
            description: `${form.diagnosa_rujukan} - ${form.nama_diagnosa_rujukan}`,
        });
    }

    if (
        form.ppk_dirujuk
        && form.nama_ppk_dirujuk
        && !faskesOptions.value.some((option) => option.value === form.ppk_dirujuk)
    ) {
        faskesOptions.value.unshift({
            value: form.ppk_dirujuk,
            label: form.nama_ppk_dirujuk,
            description: faskesType.value === '1' ? 'Faskes 1 terpilih' : 'Faskes 2 terpilih',
        });
    }

    if (
        form.poli_rujukan
        && form.nama_poli_rujukan
        && !poliOptions.value.some((option) => option.value === form.poli_rujukan)
    ) {
        poliOptions.value.unshift({
            value: form.poli_rujukan,
            label: form.nama_poli_rujukan,
            description: `${form.poli_rujukan} - ${form.nama_poli_rujukan}`,
        });
    }
}

async function fetchReference(url: string): Promise<ReferenceFetchResult> {
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

function queryUrl(url: string, query: Record<string, string>): string {
    return `${url}?${new URLSearchParams(query).toString()}`;
}

async function loadSepDetail(): Promise<SepDetail | null> {
    if (!props.referenceNumber) {
        detailError.value = 'Nomor SEP tidak tersedia.';

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
            detailError.value = `Gagal mengambil detail SEP dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;

            return null;
        }

        return result.sep ?? result.response ?? null;
    } catch (error) {
        detailError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS saat mengambil detail SEP: ${error.message}`
            : 'Gagal terhubung ke BPJS saat mengambil detail SEP.';

        return null;
    }
}

async function loadOutboundReferralDetail(): Promise<OutboundReferralDetail | null> {
    if (!props.referenceNumber) {
        detailError.value = 'Nomor rujukan tidak tersedia.';

        return null;
    }

    try {
        const result = await fetchReference(
            outboundReferralDetail.url(props.referenceNumber),
        ) as OutboundReferralFetchResult;

        if (result.metadata?.code !== '200') {
            detailError.value = `Gagal mengambil detail rujukan dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;

            return null;
        }

        return result.rujukan ?? result.response?.rujukan ?? null;
    } catch (error) {
        detailError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS saat mengambil detail rujukan: ${error.message}`
            : 'Gagal terhubung ke BPJS saat mengambil detail rujukan.';

        return null;
    }
}

async function searchDiagnosis(keyword = ''): Promise<void> {
    const normalizedKeyword = normalizeKeyword(keyword);

    diagnosisError.value = '';

    if (isTooShortKeyword(normalizedKeyword)) {
        diagnosisRequestId++;
        diagnosisOptions.value = [];

        rememberSelectedOptions();

        return;
    }

    const requestId = ++diagnosisRequestId;

    diagnosisLoading.value = true;
    diagnosisError.value = '';

    try {
        const result = await fetchReference(queryUrl(diagnosisReferences.url(), {
            query: normalizedKeyword,
        }));

        if (requestId !== diagnosisRequestId) {
            return;
        }

        if (result.metadata?.code !== '200') {
            diagnosisError.value = `Gagal mengambil referensi diagnosa dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;
            diagnosisOptions.value = [];

            rememberSelectedOptions();

            return;
        }

        diagnosisOptions.value = (result.rows ?? [])
            .map((row) => ({
                value: String(row.kode ?? ''),
                label: String(row.nama ?? row.kode ?? ''),
                description: `${row.kode ?? ''} - ${row.nama ?? ''}`,
                meta: row,
            }))
            .filter((option) => option.value !== '');

        rememberSelectedOptions();
    } catch (error) {
        if (requestId !== diagnosisRequestId) {
            return;
        }

        diagnosisOptions.value = [];
        diagnosisError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS: ${error.message}`
            : 'Gagal mengambil referensi diagnosa.';
    } finally {
        if (requestId === diagnosisRequestId) {
            diagnosisLoading.value = false;
        }
    }
}

async function searchFaskes(keyword = ''): Promise<void> {
    const normalizedKeyword = normalizeKeyword(keyword);

    faskesError.value = '';

    if (isTooShortKeyword(normalizedKeyword)) {
        faskesRequestId++;
        faskesOptions.value = [];

        rememberSelectedOptions();

        return;
    }

    const requestId = ++faskesRequestId;

    faskesLoading.value = true;
    faskesError.value = '';

    try {
        const result = await fetchReference(queryUrl(providerReferences.url(), {
            query: normalizedKeyword,
            type: faskesType.value,
        }));

        if (requestId !== faskesRequestId) {
            return;
        }

        if (result.metadata?.code !== '200') {
            faskesError.value = `Gagal mengambil referensi faskes dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;
            faskesOptions.value = [];

            rememberSelectedOptions();

            return;
        }

        faskesOptions.value = (result.rows ?? [])
            .map((row) => ({
                value: String(row.kode ?? ''),
                label: String(row.nama ?? row.kode ?? ''),
                description: `${row.kode ?? ''} - ${row.nama ?? ''}`,
                meta: row,
            }))
            .filter((option) => option.value !== '');

        rememberSelectedOptions();
    } catch (error) {
        if (requestId !== faskesRequestId) {
            return;
        }

        faskesOptions.value = [];
        faskesError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS: ${error.message}`
            : 'Gagal terhubung ke BPJS saat mengambil referensi faskes.';
    } finally {
        if (requestId === faskesRequestId) {
            faskesLoading.value = false;
        }
    }
}

async function searchPoli(keyword = ''): Promise<void> {
    if (isPrbReferral.value) {
        poliRequestId++;
        poliOptions.value = [];
        poliLoading.value = false;
        poliError.value = '';

        return;
    }

    const normalizedKeyword = normalizeKeyword(keyword);

    poliError.value = '';

    if (isTooShortKeyword(normalizedKeyword)) {
        poliRequestId++;
        poliOptions.value = [];

        rememberSelectedOptions();

        return;
    }

    const requestId = ++poliRequestId;

    poliLoading.value = true;
    poliError.value = '';

    try {
        const result = await fetchReference(queryUrl(specialistReferences.url(), {
            query: normalizedKeyword,
        }));

        if (requestId !== poliRequestId) {
            return;
        }

        if (result.metadata?.code !== '200') {
            poliError.value = `Gagal mengambil referensi poli dari BPJS: ${result.metadata?.message ?? 'BPJS tidak mengembalikan pesan error.'}`;
            poliOptions.value = [];

            rememberSelectedOptions();

            return;
        }

        poliOptions.value = (result.rows ?? [])
            .map((row) => ({
                value: String(row.kode ?? ''),
                label: String(row.nama ?? row.kode ?? ''),
                description: `${row.kode ?? ''} - ${row.nama ?? ''}`,
                meta: row,
            }))
            .filter((option) => option.value !== '');

        rememberSelectedOptions();
    } catch (error) {
        if (requestId !== poliRequestId) {
            return;
        }

        poliOptions.value = [];
        poliError.value = error instanceof Error
            ? `Gagal terhubung ke BPJS: ${error.message}`
            : 'Gagal mengambil referensi poli.';
    } finally {
        if (requestId === poliRequestId) {
            poliLoading.value = false;
        }
    }
}

function selectDiagnosis(option: ReferenceOption): void {
    form.diagnosa_rujukan = option.value;
    form.nama_diagnosa_rujukan = option.label;
    diagnosisKeyword.value = option.description ?? `${option.value} - ${option.label}`;
    diagnosisOpen.value = false;
}

function selectFaskes(option: ReferenceOption): void {
    form.ppk_dirujuk = option.value;
    form.nama_ppk_dirujuk = option.label;
    faskesKeyword.value = option.description ?? `${option.value} - ${option.label}`;
    faskesOpen.value = false;
}

function selectPoli(option: ReferenceOption): void {
    form.poli_rujukan = option.value;
    form.nama_poli_rujukan = option.label;
    poliKeyword.value = option.description ?? `${option.value} - ${option.label}`;
    poliOpen.value = false;
}

async function initializeCreateForm(): Promise<void> {
    const sep = await loadSepDetail();

    sepDetail.value = sep;

    form.no_sep = stringValue(sep?.noSep);
    form.tanggal_rujukan = stringValue(sep?.tglSep);
    form.tanggal_rencana_kunjungan = stringValue(today(getLocalTimeZone()));
    form.jenis_pelayanan = stringValue(sep?.jnsPelayanan).toLowerCase().includes('inap') ? '1' : '2';

    /**
     * Default diagnosa dari SEP, kalau formatnya "Z49.1 - Extracorporeal dialysis"
     * maka pisahkan kode dan nama.
     */
    const diagnosisParts = stringValue(sep?.diagnosa).split(' - ');

    form.diagnosa_rujukan = diagnosisParts[0] ?? '';
    form.nama_diagnosa_rujukan = diagnosisParts.slice(1).join(' - ') || stringValue(sep?.diagnosa);

    diagnosisKeyword.value = form.diagnosa_rujukan
        ? `${form.diagnosa_rujukan} ${form.nama_diagnosa_rujukan}`.trim()
        : '';

    rememberSelectedOptions();

    if (!isTooShortKeyword(diagnosisKeyword.value)) {
        await searchDiagnosis(diagnosisKeyword.value);
    }
}

async function initializeUpdateForm(): Promise<void> {
    const referral = await loadOutboundReferralDetail();

    sepDetail.value = null;

    form.no_sep = stringValue(referral?.noSep);
    form.tanggal_rujukan = stringValue(referral?.tglRujukan);
    form.tanggal_rencana_kunjungan = stringValue(referral?.tglRencanaKunjungan);
    form.ppk_dirujuk = stringValue(referral?.ppkDirujuk);
    form.nama_ppk_dirujuk = stringValue(referral?.namaPpkDirujuk);
    form.jenis_pelayanan = stringValue(referral?.jnsPelayanan) || '2';
    form.catatan = stringValue(referral?.catatan);
    form.diagnosa_rujukan = stringValue(referral?.diagRujukan);
    form.nama_diagnosa_rujukan = stringValue(referral?.namaDiagRujukan);
    form.tipe_rujukan = stringValue(referral?.tipeRujukan) || '0';
    form.poli_rujukan = stringValue(referral?.poliRujukan);
    form.nama_poli_rujukan = stringValue(referral?.namaPoliRujukan);

    diagnosisKeyword.value = form.diagnosa_rujukan
        ? `${form.diagnosa_rujukan} ${form.nama_diagnosa_rujukan}`.trim()
        : '';
    faskesKeyword.value = form.ppk_dirujuk
        ? `${form.ppk_dirujuk} ${form.nama_ppk_dirujuk}`.trim()
        : '';
    poliKeyword.value = form.poli_rujukan
        ? `${form.poli_rujukan} ${form.nama_poli_rujukan}`.trim()
        : '';

    rememberSelectedOptions();

    if (referral) {
        sepDetail.value = {
            noSep: referral.noSep,
            tglSep: referral.tglSep,
            jnsPelayanan: referral.jnsPelayanan,
            poli: [
                stringValue(referral.poliRujukan),
                stringValue(referral.namaPoliRujukan),
            ].filter(Boolean).join(' - '),
            diagnosa: [
                stringValue(referral.diagRujukan),
                stringValue(referral.namaDiagRujukan),
            ].filter(Boolean).join(' - '),
            peserta: {
                noKartu: referral.noKartu,
                nama: referral.nama,
                tglLahir: referral.tglLahir,
                kelamin: referral.kelamin,
                hakKelas: referral.kelasRawat,
            },
            provPerujuk: {
                kdProviderPerujuk: referral.ppkDirujuk,
                nmProviderPerujuk: referral.namaPpkDirujuk,
                noRujukan: referral.noRujukan,
                tglRujukan: referral.tglRujukan,
            },
        };
    }
}

async function initializeForm(): Promise<void> {
    form.clearErrors();

    initializing.value = true;
    detailLoading.value = true;
    detailError.value = '';

    cancelReferenceRequests();
    resetPopover();
    resetReferences();
    resetReferenceKeywords();
    resetReferenceLoading();
    resetFormValues();

    sepDetail.value = null;

    try {
        if (props.mode === 'update') {
            await initializeUpdateForm();

            return;
        }

        await initializeCreateForm();
    } finally {
        initializing.value = false;
        detailLoading.value = false;
    }
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            form.clearErrors();

            cancelReferenceRequests();
            resetPopover();
            resetReferences();
            resetReferenceKeywords();
            resetReferenceLoading();
            resetFormValues();

            sepDetail.value = null;
        },
    };

    if (props.method === 'patch') {
        form.patch(props.actionUrl, options);

        return;
    }

    form.post(props.actionUrl, options);
}

watch(
    () => [open.value, dialogKey.value] as const,
    async ([isOpen]) => {
        if (!isOpen) {
            cancelReferenceRequests();
            resetPopover();
            resetReferenceLoading();

            return;
        }

        await initializeForm();
    },
    {
        flush: 'post',
        immediate: true,
    },
);

watch(faskesType, () => {
    form.ppk_dirujuk = '';
    form.nama_ppk_dirujuk = '';

    faskesKeyword.value = '';
    faskesOptions.value = [];
    faskesError.value = '';
    faskesLoading.value = false;

    faskesRequestId++;
});

watch(
    () => form.tipe_rujukan,
    () => {
        if (isPrbReferral.value) {
            form.poli_rujukan = '';
            form.nama_poli_rujukan = '';

            poliKeyword.value = '';
            poliOptions.value = [];
            poliError.value = '';
            poliLoading.value = false;
            poliOpen.value = false;

            poliRequestId++;

            return;
        }

        if (open.value && !initializing.value && poliOpen.value && !isTooShortKeyword(poliKeyword.value)) {
            void searchPoli(poliKeyword.value);
        }
    },
);

watch(
    () => form.jenis_pelayanan,
    (val) => {
        if (val === '1') {
            form.poli_rujukan = '';
            form.nama_poli_rujukan = '';
            poliKeyword.value = '';
            poliOptions.value = [];
            poliError.value = '';
            poliLoading.value = false;
            poliOpen.value = false;
            poliRequestId++;
        }
    },
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto scrollbar-dialog sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

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

                <div class="relative space-y-3">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            Peserta
                        </p>

                        <p class="truncate text-base font-semibold leading-tight">
                            <Skeleton v-if="detailLoading && !activeContext?.nama" class="h-5 w-52" />
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
                                    SEP: {{ form.no_sep || '-' }}
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

                    <div class="grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm">
                            <p class="text-muted-foreground">
                                Tanggal SEP
                            </p>
                            <p class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.tglSep" class="h-4 w-20" />
                                <template v-else>
                                    {{ activeContext?.tglSep || '-' }}
                                </template>
                            </p>
                        </div>

                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm">
                            <p class="text-muted-foreground">
                                Jenis Layanan
                            </p>
                            <p class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.jenisPelayanan" class="h-4 w-24" />
                                <template v-else>
                                    {{ activeContext?.jenisPelayanan === '1' ? 'Rawat Inap' : activeContext?.jenisPelayanan === '2' ? 'Rawat Jalan' : activeContext?.jenisPelayanan || '-' }}
                                </template>
                            </p>
                        </div>

                        <div
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm sm:col-span-2">
                            <p class="text-muted-foreground">
                                Poli SEP
                            </p>
                            <p class="mt-1 truncate font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.poli" class="h-4 w-full max-w-xs" />
                                <template v-else>
                                    {{ activeContext?.poli || '-' }}
                                </template>
                            </p>
                        </div>

                        <div
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm sm:col-span-2">
                            <p class="text-muted-foreground">
                                Provider Perujuk
                            </p>
                            <p class="mt-1 truncate font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.providerPerujuk"
                                    class="h-4 w-full max-w-xs" />
                                <template v-else>
                                    {{ activeContext?.providerPerujuk || '-' }}
                                </template>
                            </p>
                        </div>

                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm">
                            <p class="text-muted-foreground">
                                No. Rujukan Asal
                            </p>
                            <p class="mt-1 truncate font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.noRujukanAsal" class="h-4 w-28" />
                                <template v-else>
                                    {{ activeContext?.noRujukanAsal || '-' }}
                                </template>
                            </p>
                        </div>

                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm">
                            <p class="text-muted-foreground">
                                Tgl. Rujukan Asal
                            </p>
                            <p class="mt-1 font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.tglRujukanAsal" class="h-4 w-20" />
                                <template v-else>
                                    {{ activeContext?.tglRujukanAsal || '-' }}
                                </template>
                            </p>
                        </div>

                        <div
                            class="rounded-md border border-border/70 bg-background/70 px-3 py-2 shadow-sm sm:col-span-2 lg:col-span-4">
                            <p class="text-muted-foreground">
                                Diagnosa SEP
                            </p>
                            <p class="mt-1 truncate font-medium">
                                <Skeleton v-if="detailLoading && !activeContext?.diagnosa"
                                    class="h-4 w-full max-w-xl" />
                                <template v-else>
                                    {{ activeContext?.diagnosa || '-' }}
                                </template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <p v-if="detailLoading || detailError" role="status"
                    class="rounded-md border px-3 py-2 text-xs leading-relaxed sm:col-span-2"
                    :class="detailError ? 'border-destructive/20 bg-destructive/10 text-destructive' : 'border-info/20 bg-info/10 text-info'">
                    <Spinner v-if="detailLoading" class="mr-2 inline-flex size-3" />
                    {{ detailError || detailLoadingMessage }}
                </p>

                <label class="grid gap-2">
                    <Label>Tanggal Rujukan</Label>
                    <Popover v-model:open="tanggalRujukan.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalRujukan.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalRujukan.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.tanggal_rujukan" />
                </label>

                <label class="grid gap-2">
                    <Label>Tanggal Rencana Kunjungan</Label>
                    <Popover v-model:open="tanggalRencanaKunjungan.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalRencanaKunjungan.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalRencanaKunjungan.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.tanggal_rencana_kunjungan" />
                </label>

                <label class="grid gap-2">
                    <Label>Jenis Pelayanan</Label>
                    <AppSelect v-model="form.jenis_pelayanan" :options="serviceOptions" />
                    <InputError :message="form.errors.jenis_pelayanan" />
                </label>

                <label class="grid gap-2">
                    <Label>Tipe Rujukan</Label>
                    <AppSelect v-model="form.tipe_rujukan" :options="referralTypeOptions" />
                    <InputError :message="form.errors.tipe_rujukan" />
                </label>

                <label class="grid gap-2">
                    <Label>Tujuan Faskes</Label>
                    <AppSelect v-model="faskesType" :options="faskesTypeOptions" />
                </label>

                <label class="grid gap-2">
                    <Label>PPK Dirujuk</Label>

                    <AppAsyncCombobox
                        v-model="form.ppk_dirujuk"
                        v-model:open="faskesOpen"
                        v-model:search="faskesKeyword"
                        :options="faskesOptions"
                        :display-value="faskesDisplayText"
                        :placeholder="faskesPlaceholder"
                        search-placeholder="Cari kode/nama faskes..."
                        :disabled="detailLoading"
                        :loading="faskesLoading"
                        :error="faskesError"
                        :min-search-length="minimumSearchLength"
                        :debounce-ms="searchDebounceMs"
                        loading-title="Memuat faskes"
                        loading-description="Mengambil referensi dari BPJS..."
                        prompt-description="Ketik minimal 3 karakter untuk mencari faskes."
                        error-title="Faskes gagal dimuat"
                        empty-title="Faskes tidak ditemukan"
                        empty-description="Coba gunakan kata kunci lain."
                        @search="searchFaskes"
                        @select="selectFaskes"
                    />

                    <InputError :message="form.errors.ppk_dirujuk" />
                </label>

                <label class="grid gap-2 sm:col-span-2">
                    <Label>Diagnosa Rujukan</Label>

                    <AppAsyncCombobox
                        v-model="form.diagnosa_rujukan"
                        v-model:open="diagnosisOpen"
                        v-model:search="diagnosisKeyword"
                        :options="diagnosisOptions"
                        :display-value="diagnosisDisplayText"
                        placeholder="Pilih diagnosa rujukan"
                        search-placeholder="Cari kode/nama diagnosa..."
                        :disabled="detailLoading"
                        :loading="diagnosisLoading"
                        :error="diagnosisError"
                        :min-search-length="minimumSearchLength"
                        :debounce-ms="searchDebounceMs"
                        loading-title="Memuat diagnosa"
                        loading-description="Mengambil referensi dari BPJS..."
                        prompt-description="Ketik minimal 3 karakter untuk mencari diagnosa."
                        error-title="Diagnosa gagal dimuat"
                        empty-title="Diagnosa tidak ditemukan"
                        empty-description="Coba gunakan kata kunci lain."
                        @search="searchDiagnosis"
                        @select="selectDiagnosis"
                    />

                    <InputError :message="form.errors.diagnosa_rujukan" />
                </label>

                <label class="grid gap-2 sm:col-span-2">
                    <Label>Poli Rujukan</Label>

                    <AppAsyncCombobox
                        v-model="form.poli_rujukan"
                        v-model:open="poliOpen"
                        v-model:search="poliKeyword"
                        :options="poliOptions"
                        :display-value="poliDisplayText"
                        placeholder="Pilih poli rujukan"
                        search-placeholder="Cari kode/nama poli..."
                        :disabled="detailLoading || isPrbReferral || isRanap"
                        :loading="poliLoading"
                        :error="poliError"
                        :min-search-length="minimumSearchLength"
                        :debounce-ms="searchDebounceMs"
                        loading-title="Memuat poli"
                        loading-description="Mengambil referensi dari BPJS..."
                        prompt-description="Ketik minimal 3 karakter untuk mencari poli."
                        error-title="Poli gagal dimuat"
                        empty-title="Poli tidak ditemukan"
                        empty-description="Coba gunakan kata kunci lain."
                        @search="searchPoli"
                        @select="selectPoli"
                    />

                    <p v-if="isRanap" class="text-xs text-muted-foreground">
                        Poli rujukan tidak diperlukan untuk jenis pelayanan rawat inap.
                    </p>

                    <p v-if="isPrbReferral" class="text-xs text-muted-foreground">
                        Untuk tipe rujukan balik PRB, BPJS meminta poli rujukan dikosongkan.
                    </p>

                    <InputError :message="form.errors.poli_rujukan" />
                </label>

                <label class="grid gap-2 sm:col-span-2">
                    <Label>Catatan</Label>
                    <Input v-model="form.catatan" autocomplete="off" placeholder="Masukkan catatan rujukan" />
                    <InputError :message="form.errors.catatan" />
                </label>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="open = false">
                    Batal
                </Button>

                <Button :disabled="!canSubmit" @click="submit">
                    <Spinner v-if="form.processing" />
                    <component :is="submitIcon" v-else class="size-4" />
                    {{ submitLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
