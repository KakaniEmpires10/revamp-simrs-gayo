<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, Check, ChevronsUpDown, Save } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import { reference, update } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
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
import { AppSelect, AppTimeInput } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { RegisteredPatientRow, RegistrationOption, RegistrationType } from '@/types';

const props = defineProps<{
    patient: RegisteredPatientRow | null;
    registrationType: RegistrationType;
    doctors: RegistrationOption[];
    clinics: RegistrationOption[];
    payments: RegistrationOption[];
    igdClinicCode: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    jenis_pendaftaran: 'rawat_jalan' as RegistrationType,
    tgl_registrasi: '',
    jam_reg: '',
    kd_dokter: '',
    kd_poli: '',
    kd_pj: '',
    p_jawab: '',
    almt_pj: '',
    hubunganpj: '',
    perujuk: '',
    kategori_rujuk: '-',
    processing: false,
    errors: {} as Record<string, string>,
});

const tanggalRegistrasi = useTanggalCalendar(toRef(form, 'tgl_registrasi'));

const doctorOpen = ref(false);
const doctorOptions = ref<RegistrationOption[]>([]);
const doctorLoading = ref(false);
const doctorError = ref('');
const filterDoctorBySchedule = ref(true);
const sedangMengisiForm = ref(false);

const isIgd = computed(() => form.jenis_pendaftaran === 'igd' || form.kd_poli === props.igdClinicCode);
const title = computed(() => isIgd.value ? 'Edit Pendaftaran IGD' : 'Edit Pendaftaran Rawat Jalan');
const shouldFilterDoctorBySchedule = computed(() => !isIgd.value && filterDoctorBySchedule.value);
const clinicOptions = computed(() => isIgd.value
    ? props.clinics.filter((clinic) => clinic.value === props.igdClinicCode)
    : props.clinics.filter((clinic) => clinic.value !== props.igdClinicCode),
);

const selectedDoctorLabel = computed(() => {
    return doctorOptions.value.find((doctor) => doctor.value === form.kd_dokter)?.label
        || props.patient?.nm_dokter
        || 'Pilih dokter';
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        isiFormDariPendaftaran(props.patient);
    },
    { immediate: true },
);

watch(
    () => [form.kd_poli, form.tgl_registrasi, shouldFilterDoctorBySchedule.value],
    () => {
        if (!open.value || sedangMengisiForm.value) {
            return;
        }

        form.kd_dokter = '';
        doctorOptions.value = [];

        if (!form.tgl_registrasi || (shouldFilterDoctorBySchedule.value && !form.kd_poli)) {
            return;
        }

        void loadDoctors();
    },
);

function isiFormDariPendaftaran(patient: RegisteredPatientRow): void {
    sedangMengisiForm.value = true;

    form.jenis_pendaftaran = patient.kd_poli === props.igdClinicCode ? 'igd' : props.registrationType;
    form.tgl_registrasi = patient.tgl_registrasi;
    form.jam_reg = patient.jam_reg.slice(0, 5);
    form.kd_dokter = patient.kd_dokter;
    form.kd_poli = patient.kd_poli;
    form.kd_pj = patient.kd_pj;
    form.p_jawab = patient.p_jawab || patient.nm_pasien;
    form.almt_pj = patient.almt_pj || '-';
    form.hubunganpj = patient.hubunganpj || 'DIRI SENDIRI';
    form.perujuk = patient.perujuk && patient.perujuk !== '-' ? patient.perujuk : '';
    form.kategori_rujuk = patient.kategori_rujuk || '-';
    form.errors = {};
    filterDoctorBySchedule.value = patient.kd_poli !== props.igdClinicCode;

    void loadDoctors().finally(() => {
        sedangMengisiForm.value = false;
    });
}

async function loadDoctors(): Promise<void> {
    if (!form.tgl_registrasi || (shouldFilterDoctorBySchedule.value && !form.kd_poli)) {
        return;
    }

    doctorLoading.value = true;
    doctorError.value = '';

    const query: Record<string, string> = {
        type: 'doctor',
        registration_date: form.tgl_registrasi,
    };

    if (shouldFilterDoctorBySchedule.value) {
        query.clinic_code = form.kd_poli;
    }

    try {
        const response = await fetch(reference.url({ query }), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat data dokter.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        const options = payload.data ?? [];

        if (form.kd_dokter && !options.some((doctor) => doctor.value === form.kd_dokter)) {
            options.unshift({
                value: form.kd_dokter,
                label: props.patient?.nm_dokter || form.kd_dokter,
                description: 'Dokter tersimpan pada pendaftaran ini',
            });
        }

        doctorOptions.value = options;
    } catch {
        doctorError.value = shouldFilterDoctorBySchedule.value
            ? 'Gagal memuat jadwal dokter.'
            : 'Gagal memuat data dokter.';
        doctorOptions.value = [];
    } finally {
        doctorLoading.value = false;
    }
}

function selectDoctor(option: RegistrationOption): void {
    form.kd_dokter = option.value;
    doctorOpen.value = false;
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.patch(update.url(props.patient.no_rawat), {
        jenis_pendaftaran: form.jenis_pendaftaran,
        tgl_registrasi: form.tgl_registrasi,
        jam_reg: form.jam_reg,
        kd_dokter: form.kd_dokter,
        kd_poli: isIgd.value ? props.igdClinicCode : form.kd_poli,
        kd_pj: form.kd_pj,
        p_jawab: form.p_jawab,
        almt_pj: form.almt_pj,
        hubunganpj: form.hubunganpj,
        perujuk: form.perujuk,
        kategori_rujuk: form.kategori_rujuk,
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
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-emerald-500/8 p-4 shadow-sm">
                    <svg class="pointer-events-none absolute -right-16 -bottom-16 h-44 w-80 rotate-[-14deg] text-primary/10"
                        viewBox="0 0 420 180" fill="none" aria-hidden="true">
                        <path d="M0 126C46 92 92 92 138 126C184 160 230 160 276 126C322 92 368 92 420 126V180H0V126Z"
                            fill="currentColor" />
                        <path d="M0 78C46 44 92 44 138 78C184 112 230 112 276 78C322 44 368 44 420 78"
                            stroke="currentColor" stroke-width="14" stroke-linecap="round" />
                    </svg>

                    <div class="relative grid gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase text-muted-foreground">
                                Ringkasan Pendaftaran
                            </p>

                            <p class="truncate text-lg font-semibold leading-tight">
                                {{ patient.nm_pasien }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="soft-info" size="sm" class="font-mono">
                                    No RM: {{ patient.no_rkm_medis }}
                                </Badge>
                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                    No Rawat: {{ patient.no_rawat }}
                                </Badge>
                                <Badge variant="soft-indigo" size="sm">
                                    {{ patient.png_jawab }}
                                </Badge>
                                <Badge variant="muted" size="sm">
                                    {{ labelJenisKelamin(patient.jk) }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    No Reg
                                </p>
                                <p class="mt-1 font-mono font-semibold">
                                    {{ patient.no_reg }}
                                </p>
                            </div>

                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    Usia Saat Daftar
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ hitungUmur(patient.tgl_lahir, form.tgl_registrasi || patient.tgl_registrasi) }}
                                </p>
                            </div>

                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Status
                                </p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <Badge variant="soft-warning" size="sm">
                                        Periksa: {{ patient.stts }}
                                    </Badge>
                                    <Badge variant="soft-info" size="sm">
                                        {{ patient.status_bayar }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Tanggal</Label>
                        <Popover v-model:open="tanggalRegistrasi.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalRegistrasi.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalRegistrasi.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                        <span v-if="form.errors.tgl_registrasi" class="text-destructive text-xs">{{ form.errors.tgl_registrasi }}</span>
                    </label>
                    <label class="grid gap-2">
                        <Label>Jam</Label>
                        <AppTimeInput v-model="form.jam_reg" />
                        <span v-if="form.errors.jam_reg" class="text-destructive text-xs">{{ form.errors.jam_reg }}</span>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Poliklinik</Label>
                        <AppSelect
                            v-model="form.kd_poli"
                            :options="clinicOptions"
                            :disabled="isIgd"
                            :placeholder="isIgd ? 'Instalasi Gawat Darurat' : 'Pilih poliklinik'"
                        />
                        <span v-if="form.errors.kd_poli" class="text-destructive text-xs">{{ form.errors.kd_poli }}</span>
                        <span v-if="isIgd" class="text-muted-foreground text-xs">
                            Pendaftaran IGD memakai Instalasi Gawat Darurat dan tidak perlu memilih poli lain.
                        </span>
                    </label>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Dokter</Label>
                            <div v-if="!isIgd" class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span>Filter per-jadwal poli</span>
                                <Switch
                                    :model-value="filterDoctorBySchedule"
                                    @update:model-value="filterDoctorBySchedule = Boolean($event)"
                                />
                            </div>
                        </div>
                        <Popover v-model:open="doctorOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    role="combobox"
                                    :aria-expanded="doctorOpen"
                                    :disabled="(shouldFilterDoctorBySchedule && !form.kd_poli) || doctorLoading"
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
                                    <CommandInput placeholder="Cari dokter..." />
                                    <CommandList>
                                        <CommandEmpty>
                                            {{ doctorError || (shouldFilterDoctorBySchedule ? 'Tidak ada jadwal dokter untuk poli dan tanggal ini.' : 'Tidak ada dokter aktif yang cocok.') }}
                                        </CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="doctor in doctorOptions"
                                                :key="doctor.value"
                                                :value="`${doctor.value} ${doctor.label} ${doctor.description || ''}`"
                                                @select="selectDoctor(doctor)"
                                            >
                                                <Check
                                                    :class="cn(
                                                        'size-4',
                                                        doctor.value === form.kd_dokter ? 'opacity-100' : 'opacity-0',
                                                    )"
                                                />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">
                                                        {{ doctor.label }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted-foreground">
                                                        {{ doctor.description || doctor.value }}
                                                    </p>
                                                </div>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <span v-if="form.errors.kd_dokter" class="text-destructive text-xs">{{ form.errors.kd_dokter }}</span>
                    </div>
                </div>

                <div class="grid gap-4 rounded-lg border bg-muted/20 p-3 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Jenis Bayar</Label>
                        <AppSelect v-model="form.kd_pj" :options="payments" placeholder="Pilih jenis bayar" />
                        <span v-if="form.errors.kd_pj" class="text-destructive text-xs">{{ form.errors.kd_pj }}</span>
                    </label>
                    <label class="grid gap-2">
                        <Label>Penanggung Jawab</Label>
                        <Input v-model="form.p_jawab" />
                        <span v-if="form.errors.p_jawab" class="text-destructive text-xs">{{ form.errors.p_jawab }}</span>
                    </label>
                    <label class="grid gap-2">
                        <Label>Hubungan</Label>
                        <Input v-model="form.hubunganpj" />
                        <span v-if="form.errors.hubunganpj" class="text-destructive text-xs">{{ form.errors.hubunganpj }}</span>
                    </label>
                    <label class="grid gap-2">
                        <Label>Alamat Penanggung Jawab</Label>
                        <Input v-model="form.almt_pj" />
                        <span v-if="form.errors.almt_pj" class="text-destructive text-xs">{{ form.errors.almt_pj }}</span>
                    </label>
                </div>

                <div class="grid gap-4 rounded-lg border bg-muted/20 p-3 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Perujuk</Label>
                        <Input v-model="form.perujuk" placeholder="Nama faskes atau perujuk" />
                        <span v-if="form.errors.perujuk" class="text-destructive text-xs">{{ form.errors.perujuk }}</span>
                    </label>
                    <label class="grid gap-2">
                        <Label>Kategori Rujukan</Label>
                        <AppSelect
                            v-model="form.kategori_rujuk"
                            :options="[
                                { value: '-', label: '-' },
                                { value: 'Bedah', label: 'Bedah' },
                                { value: 'Non-Bedah', label: 'Non-Bedah' },
                                { value: 'Kebidanan', label: 'Kebidanan' },
                                { value: 'Anak', label: 'Anak' },
                            ]"
                            placeholder="Pilih kategori"
                        />
                        <span v-if="form.errors.kategori_rujuk" class="text-destructive text-xs">{{ form.errors.kategori_rujuk }}</span>
                    </label>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.kd_dokter || (!isIgd && !form.kd_poli)"
                    >
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
