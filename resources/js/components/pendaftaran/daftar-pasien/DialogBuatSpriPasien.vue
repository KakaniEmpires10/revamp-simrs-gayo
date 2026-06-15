<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { CalendarClock, CalendarIcon, Check, ChevronsUpDown, Save, Search } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import { reference } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { storeRegistrationInpatientPlan } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import {
    PopoverAnchor,
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { labelJenisKelamin } from '@/lib/pasien';
import { tanggalHariIni } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { RegisteredPatientRow, RegistrationOption } from '@/types';

const props = defineProps<{
    patient: RegisteredPatientRow | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    tanggal_kontrol: '',
    poli_kontrol: '',
    nama_poli: '',
    kode_dokter: '',
    nama_dokter: '',
    no_sep: '',
    diagnosa_awal: '',
    kd_penyakit: '',
    mode_diagnosa: 'manual' as 'manual' | 'referensi',
    processing: false,
    errors: {} as Record<string, string>,
});

const tanggalKontrol = useTanggalCalendar(toRef(form, 'tanggal_kontrol'));

const sepOpen = ref(false);

const clinicOpen = ref(false);
const clinicLoading = ref(false);
const clinicError = ref('');
const clinicOptions = ref<RegistrationOption[]>([]);

const doctorOpen = ref(false);
const doctorLoading = ref(false);
const doctorError = ref('');
const doctorOptions = ref<RegistrationOption[]>([]);

const diagnosisOpen = ref(false);
const diagnosisLoading = ref(false);
const diagnosisError = ref('');
const diagnosisOptions = ref<RegistrationOption[]>([]);
const diagnosisInputRef = ref<InstanceType<typeof Input> | null>(null);

const sepOptions = computed<RegistrationOption[]>(() => {
    if (!props.patient?.no_sep) {
        return [];
    }

    return [
        {
            value: props.patient.no_sep,
            label: props.patient.no_sep,
            description: 'SEP terkait pendaftaran ini',
        },
    ];
});

const selectedSepLabel = computed(() => {
    return sepOptions.value.find((sep) => sep.value === form.no_sep)?.label || 'Pilih SEP terkait (opsional)';
});

const selectedClinicLabel = computed(() => {
    return clinicOptions.value.find((clinic) => clinic.value === form.poli_kontrol)?.label || 'Pilih spesialis/subspesialis';
});

const selectedDoctorLabel = computed(() => {
    return doctorOptions.value.find((doctor) => doctor.value === form.kode_dokter)?.label || 'Pilih dokter DPJP BPJS';
});

const bisaMemuatPoli = computed(() => Boolean(props.patient?.no_peserta && form.tanggal_kontrol));
const showDiagnosisSuggestions = computed(() => diagnosisLoading.value || diagnosisOptions.value.length > 0);
const bisaSubmit = computed(() => Boolean(
    props.patient
    && props.patient.no_peserta
    && form.tanggal_kontrol
    && form.poli_kontrol
    && form.kode_dokter
    && form.diagnosa_awal.trim(),
));

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.tanggal_kontrol = tanggalDefaultSpri(props.patient);
        form.poli_kontrol = '';
        form.nama_poli = '';
        form.kode_dokter = '';
        form.nama_dokter = '';
        form.no_sep = props.patient.no_sep || '';
        form.diagnosa_awal = props.patient.diagnosa_ranap_awal || props.patient.nmdiagnosaawal || '';
        form.kd_penyakit = props.patient.diagawal || '';
        form.mode_diagnosa = props.patient.diagawal ? 'referensi' : 'manual';
        form.errors = {};
        clinicOptions.value = [];
        doctorOptions.value = [];
        diagnosisOptions.value = [];
        clinicError.value = '';
        doctorError.value = '';
        diagnosisError.value = '';
        diagnosisOpen.value = false;
    },
    { immediate: true },
);

watch(
    () => form.tanggal_kontrol,
    () => {
        form.poli_kontrol = '';
        form.nama_poli = '';
        form.kode_dokter = '';
        form.nama_dokter = '';
        doctorOptions.value = [];

        if (!bisaMemuatPoli.value) {
            clinicOptions.value = [];

            return;
        }

        void loadClinics();
    },
);

watch(
    () => form.poli_kontrol,
    () => {
        form.kode_dokter = '';
        form.nama_dokter = '';
        doctorOptions.value = [];

        if (!form.poli_kontrol || !form.tanggal_kontrol) {
            return;
        }

        void loadDoctors();
    },
);

const cariDiagnosa = useDebounceFn((query: string) => {
    void loadDiagnoses(query);
}, 300);

watch(showDiagnosisSuggestions, (value) => {
    if (!value) {
        diagnosisOpen.value = false;
    }
});

function tanggalDefaultSpri(patient: RegisteredPatientRow): string {
    if (patient.is_ranap && patient.tgl_masuk_ranap) {
        return patient.tgl_masuk_ranap;
    }

    if (patient.tgl_sep) {
        return patient.tgl_sep;
    }

    return tanggalHariIni();
}

async function loadClinics(): Promise<void> {
    if (!props.patient?.no_peserta) {
        return;
    }

    clinicLoading.value = true;
    clinicError.value = '';

    try {
        const response = await fetch(reference.url({
            query: {
                type: 'spri_clinic',
                no_kartu: props.patient.no_peserta,
                tanggal_kontrol: form.tanggal_kontrol,
            },
        }), { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error('Gagal memuat poli SPRI.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        clinicOptions.value = payload.data ?? [];
    } catch {
        clinicError.value = 'Gagal memuat spesialis/subspesialis dari VClaim.';
        clinicOptions.value = [];
    } finally {
        clinicLoading.value = false;
    }
}

async function loadDoctors(): Promise<void> {
    doctorLoading.value = true;
    doctorError.value = '';

    try {
        const response = await fetch(reference.url({
            query: {
                type: 'spri_doctor',
                poli_kontrol: form.poli_kontrol,
                tanggal_kontrol: form.tanggal_kontrol,
            },
        }), { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error('Gagal memuat dokter SPRI.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        doctorOptions.value = payload.data ?? [];
    } catch {
        doctorError.value = 'Gagal memuat jadwal dokter BPJS dari VClaim.';
        doctorOptions.value = [];
    } finally {
        doctorLoading.value = false;
    }
}

async function loadDiagnoses(query: string): Promise<void> {
    if (query.trim().length < 2) {
        diagnosisOptions.value = [];
        diagnosisError.value = '';
        diagnosisOpen.value = false;

        return;
    }

    diagnosisLoading.value = true;
    diagnosisError.value = '';
    diagnosisOpen.value = true;

    try {
        const response = await fetch(reference.url({ query: { type: 'diagnosis', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat referensi diagnosa.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        diagnosisOptions.value = payload.data ?? [];
        diagnosisOpen.value = diagnosisOptions.value.length > 0;
    } catch {
        diagnosisError.value = 'Gagal memuat referensi diagnosa.';
        diagnosisOptions.value = [];
        diagnosisOpen.value = false;
    } finally {
        diagnosisLoading.value = false;
    }
}

function selectSep(sep: RegistrationOption): void {
    form.no_sep = sep.value;
    sepOpen.value = false;
}

function selectClinic(clinic: RegistrationOption): void {
    form.poli_kontrol = clinic.value;
    form.nama_poli = clinic.label;
    clinicOpen.value = false;
}

function selectDoctor(doctor: RegistrationOption): void {
    form.kode_dokter = doctor.value;
    form.nama_dokter = doctor.label;
    doctorOpen.value = false;
}

function ubahDiagnosa(value: string | number): void {
    form.diagnosa_awal = String(value);
    form.kd_penyakit = '';
    form.mode_diagnosa = 'manual';
    cariDiagnosa(String(value));
}

function pilihDiagnosa(diagnosis: RegistrationOption): void {
    form.kd_penyakit = diagnosis.value;
    form.diagnosa_awal = diagnosis.label;
    form.mode_diagnosa = 'referensi';
    diagnosisOpen.value = false;
}

function onDiagnosisInteractOutside(event: Event): void {
    const inputEl = (diagnosisInputRef.value as any)?.$el ?? diagnosisInputRef.value;

    if (inputEl && (event.target as Node | null) && inputEl.contains(event.target as Node)) {
        event.preventDefault();

        return;
    }

    diagnosisOpen.value = false;
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.post(storeRegistrationInpatientPlan.url(props.patient.no_rawat), {
        kode_dokter: form.kode_dokter,
        nama_dokter: form.nama_dokter,
        poli_kontrol: form.poli_kontrol,
        nama_poli: form.nama_poli,
        tanggal_kontrol: form.tanggal_kontrol,
        diagnosa_awal: form.diagnosa_awal,
        kd_penyakit: form.kd_penyakit || null,
        mode_diagnosa: form.mode_diagnosa,
        no_sep: form.no_sep || null,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['registeredPatients', 'filters', 'view']),
        reset: ['registeredPatients'],
        onSuccess: (page) => {
            if (!isFeedbackSuccess(page)) {
                return;
            }

            open.value = false;
        },
        onError: (errors) => {
            form.errors = errors;
        },
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[95vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Buat SPRI</DialogTitle>
                <DialogDescription v-if="patient">
                    Surat Perintah Rawat Inap untuk {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-blue-500/8 p-4 shadow-sm">
                    <CalendarClock class="absolute -right-8 -bottom-8 size-36 rotate-[-10deg] text-primary/10" />
                    <div class="relative grid gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase text-muted-foreground">
                                Data Pasien
                            </p>
                            <p class="truncate text-lg font-semibold">
                                {{ patient.nm_pasien }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="soft-info" size="sm" class="font-mono">
                                    No Kartu: {{ patient.no_peserta || '-' }}
                                </Badge>
                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                    SEP: {{ patient.no_sep || '-' }}
                                </Badge>
                                <Badge variant="muted" size="sm">
                                    {{ labelJenisKelamin(patient.jk) }}
                                </Badge>
                            </div>
                        </div>

                        <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 text-sm shadow-sm">
                            <p class="text-xs text-muted-foreground">
                                Diagnosa awal yang dipakai
                            </p>
                            <p class="mt-1 font-medium">
                                {{ patient.diagnosa_ranap_awal || patient.nmdiagnosaawal || patient.diagawal || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="!patient.no_peserta" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    SPRI membutuhkan nomor peserta BPJS. SEP bersifat opsional dan hanya disimpan sebagai metadata lokal.
                </div>

                <div v-if="sepOptions.length" class="grid gap-2">
                    <Label>SEP Terkait</Label>
                    <Popover v-model:open="sepOpen">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                role="combobox"
                                :aria-expanded="sepOpen"
                                class="h-10 w-full justify-between overflow-hidden bg-background px-3"
                            >
                                <span class="truncate text-left">
                                    {{ selectedSepLabel }}
                                </span>
                                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                            <Command>
                                <CommandList>
                                    <CommandEmpty>Tidak ada SEP terkait.</CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem
                                            v-for="sep in sepOptions"
                                            :key="sep.value"
                                            :value="`${sep.value} ${sep.label}`"
                                            @select="selectSep(sep)"
                                        >
                                            <Check :class="cn('size-4', sep.value === form.no_sep ? 'opacity-100' : 'opacity-0')" />
                                            <div class="min-w-0">
                                                <p class="truncate font-medium">
                                                    {{ sep.label }}
                                                </p>
                                                <p class="truncate text-xs text-muted-foreground">
                                                    {{ sep.description }}
                                                </p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                </div>

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
                                    :disabled="!bisaMemuatPoli || clinicLoading"
                                    class="h-10 w-full justify-between overflow-hidden bg-background px-3"
                                >
                                    <span class="truncate text-left">
                                        {{ clinicLoading ? 'Memuat poli SPRI...' : selectedClinicLabel }}
                                    </span>
                                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandList>
                                        <CommandEmpty>{{ clinicError || 'Tidak ada spesialis yang tersedia untuk tanggal ini.' }}</CommandEmpty>
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
                                        {{ doctorLoading ? 'Memuat dokter...' : selectedDoctorLabel }}
                                    </span>
                                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandList>
                                        <CommandEmpty>{{ doctorError || 'Tidak ada dokter BPJS yang tersedia untuk poli dan tanggal ini.' }}</CommandEmpty>
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

                <div class="grid gap-2">
                    <Label>Diagnosa SPRI</Label>
                    <Popover v-model:open="diagnosisOpen">
                        <PopoverAnchor as-child>
                            <div class="relative">
                                <Search class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    ref="diagnosisInputRef"
                                    :model-value="form.diagnosa_awal"
                                    class="pl-9"
                                    placeholder="Ketik diagnosa bebas atau cari ICD, contoh: A00 atau cholera"
                                    autocomplete="off"
                                    @focus="diagnosisOpen = true"
                                    @update:model-value="ubahDiagnosa"
                                    @keydown.escape="diagnosisOpen = false"
                                    @keydown.tab="diagnosisOpen = false"
                                />
                            </div>
                        </PopoverAnchor>
                        <PopoverContent
                            class="w-[--reka-popover-trigger-width] p-0"
                            align="start"
                            :disable-outside-pointer-events="false"
                            @interact-outside="onDiagnosisInteractOutside"
                            @open-auto-focus.prevent
                        >
                            <Command :should-filter="false">
                                <CommandList>
                                    <CommandEmpty>
                                        {{ diagnosisLoading ? 'Memuat referensi diagnosa...' : (diagnosisError || 'Tidak ada hasil. Lanjutkan sebagai input manual.') }}
                                    </CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem
                                            v-for="diagnosis in diagnosisOptions"
                                            :key="diagnosis.value"
                                            :value="`${diagnosis.value} ${diagnosis.label}`"
                                            @select="pilihDiagnosa(diagnosis)"
                                        >
                                            <Check :class="cn('size-4 shrink-0', diagnosis.value === form.kd_penyakit ? 'opacity-100' : 'opacity-0')" />
                                            <div class="min-w-0">
                                                <p class="truncate font-medium">
                                                    {{ diagnosis.label }}
                                                </p>
                                                <p class="truncate text-xs text-muted-foreground">
                                                    {{ diagnosis.description }}
                                                </p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        <Badge :variant="form.mode_diagnosa === 'referensi' ? 'soft-success' : 'muted'" size="sm">
                            {{ form.mode_diagnosa === 'referensi' ? 'Referensi ICD' : 'Manual' }}
                        </Badge>
                        <span>Dianjurkan mengisi diagnosa sesuai ICD 10, tapi bisa menginput nama penyakit secara manual.</span>
                    </div>
                    <InputError :message="form.errors.diagnosa_awal" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                    <Button type="submit" :disabled="form.processing || !bisaSubmit">
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Simpan SPRI
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
