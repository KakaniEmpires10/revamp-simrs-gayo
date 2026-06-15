<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, Check, ChevronsUpDown, Save } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import { reference, store } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
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
import { feedbackToastFromPage } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni, waktuSekarang } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { RegistrationOption, RegistrationPatient, RegistrationType } from '@/types';

const props = defineProps<{
    patient: RegistrationPatient | null;
    registrationType: RegistrationType;
    doctors: RegistrationOption[];
    clinics: RegistrationOption[];
    payments: RegistrationOption[];
    igdClinicCode: string;
    defaultPaymentCode: string;
}>();

const emit = defineEmits<{
    registered: [];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    tgl_registrasi: tanggalHariIni(),
    jam_reg: waktuSekarang(),
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
const showResponsibleForm = ref(false);
const showReferralForm = ref(false);

const title = computed(() => props.registrationType === 'igd' ? 'Pendaftaran IGD' : 'Pendaftaran Rawat Jalan');
const shouldFilterDoctorBySchedule = computed(() => props.registrationType === 'rawat_jalan' && filterDoctorBySchedule.value);
const clinicOptions = computed(() => props.registrationType === 'igd'
    ? props.clinics.filter((clinic) => clinic.value === props.igdClinicCode)
    : props.clinics.filter((clinic) => clinic.value !== props.igdClinicCode),
);

watch(
    () => [open.value, props.patient, props.registrationType],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.tgl_registrasi = tanggalHariIni();
        form.jam_reg = waktuSekarang();
        form.kd_dokter = '';
        form.kd_poli = props.registrationType === 'igd' ? props.igdClinicCode : '';
        form.kd_pj = props.patient.kd_pj || props.defaultPaymentCode;
        form.p_jawab = props.patient.namakeluarga || props.patient.nm_pasien;
        form.almt_pj = props.patient.alamatpj || props.patient.alamat || '-';
        form.hubunganpj = props.patient.keluarga || 'DIRI SENDIRI';
        form.perujuk = '';
        form.kategori_rujuk = '-';
        form.errors = {};
        filterDoctorBySchedule.value = props.registrationType === 'rawat_jalan';
        showResponsibleForm.value = false;
        showReferralForm.value = false;
    },
    { immediate: true },
);

watch(
    () => [form.kd_poli, form.tgl_registrasi, open.value, props.registrationType, shouldFilterDoctorBySchedule.value],
    () => {
        form.kd_dokter = '';
        doctorOptions.value = [];

        if (!open.value || !form.tgl_registrasi || (shouldFilterDoctorBySchedule.value && !form.kd_poli)) {
            return;
        }

        void loadDoctors();
    },
);

async function loadDoctors(): Promise<void> {
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
        const response = await fetch(reference.url({
            query,
        }), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat data dokter.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        doctorOptions.value = payload.data ?? [];
    } catch {
        doctorError.value = shouldFilterDoctorBySchedule.value
            ? 'Gagal memuat jadwal dokter.'
            : 'Gagal memuat data dokter.';
        doctorOptions.value = [];
    } finally {
        doctorLoading.value = false;
    }
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    const payload: Record<string, string> = {
        jenis_pendaftaran: props.registrationType,
        no_rkm_medis: props.patient.no_rkm_medis,
        tgl_registrasi: form.tgl_registrasi,
        jam_reg: form.jam_reg,
        kd_dokter: form.kd_dokter,
        kd_poli: props.registrationType === 'igd' ? props.igdClinicCode : form.kd_poli,
    };

    if (showResponsibleForm.value) {
        payload.kd_pj = form.kd_pj;
        payload.p_jawab = form.p_jawab;
        payload.almt_pj = form.almt_pj;
        payload.hubunganpj = form.hubunganpj;
    }

    if (showReferralForm.value) {
        payload.perujuk = form.perujuk;
        payload.kategori_rujuk = form.kategori_rujuk;
    }

    router.post(
        store.url(),
        payload,
        {
            preserveScroll: true,
            onSuccess: (page) => {
                if (feedbackToastFromPage(page)?.type !== 'success') {
                    return;
                }

                open.value = false;
                emit('registered');
            },
            onError: (errors) => {
                form.errors = errors;
            },
            onFinish: () => {
                form.processing = false;
            },
        },
    );
}

function selectDoctor(option: RegistrationOption): void {
    form.kd_dokter = option.value;
    doctorOpen.value = false;
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[95vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.nm_pasien }} - {{ patient.no_rkm_medis }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="patient" class="grid gap-5">
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
                                Ringkasan Pasien
                            </p>

                            <p class="truncate text-lg font-semibold leading-tight">
                                {{ patient.nm_pasien }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="soft-info" size="sm" class="font-mono">
                                    No RM: {{ patient.no_rkm_medis }}
                                </Badge>
                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                    NIK: {{ patient.no_ktp || '-' }}
                                </Badge>
                                <Badge variant="soft-warning" size="sm" class="font-mono">
                                    Peserta: {{ patient.no_peserta || '-' }}
                                </Badge>
                                <Badge variant="muted" size="sm">
                                    {{ patient.jk === 'P' ? 'Perempuan' : patient.jk === 'L' ? 'Laki-laki' : patient.jk || '-' }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    Tanggal Lahir
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ patient.tgl_lahir || '-' }}
                                </p>
                            </div>

                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    No Telepon
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ patient.no_tlp || '-' }}
                                </p>
                            </div>

                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Alamat
                                </p>
                                <p class="mt-1 line-clamp-2 font-medium">
                                    {{ patient.alamat || '-' }}
                                </p>
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
                        <AppSelect v-model="form.kd_poli" :options="clinicOptions"
                            :disabled="registrationType === 'igd'" :placeholder="registrationType === 'igd' ? 'Instalasi Gawat Darurat' : 'Pilih poliklinik'" />
                        <span v-if="form.errors.kd_poli" class="text-destructive text-xs">{{ form.errors.kd_poli
                            }}</span>
                    </label>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Dokter</Label>
                            <div v-if="registrationType !== 'igd'" class="flex items-center gap-2 text-xs text-muted-foreground">
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
                                        {{ doctorOptions.find((doctor) => doctor.value === form.kd_dokter)?.label || (doctorLoading ? 'Memuat dokter...' : 'Pilih dokter') }}
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

                <div class="grid gap-3 rounded-lg border bg-muted/20 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Ubah penanggung jawab</p>
                            <p class="text-xs text-muted-foreground">
                                Jika tidak dibuka, sistem memakai data PJ dari master pasien.
                            </p>
                        </div>
                        <Switch
                            :model-value="showResponsibleForm"
                            @update:model-value="showResponsibleForm = Boolean($event)"
                        />
                    </div>

                    <div v-if="showResponsibleForm" class="grid gap-4 sm:grid-cols-2">
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
                </div>

                <div class="grid gap-3 rounded-lg border bg-muted/20 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Isi data rujukan</p>
                            <p class="text-xs text-muted-foreground">
                                Jika tidak dibuka, data rujukan disimpan dengan nilai default.
                            </p>
                        </div>
                        <Switch
                            :model-value="showReferralForm"
                            @update:model-value="showReferralForm = Boolean($event)"
                        />
                    </div>

                    <div v-if="showReferralForm" class="grid gap-4 sm:grid-cols-2">
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
                </div>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="open = false">Batal</Button>
                <Button
                    :disabled="form.processing || !form.kd_dokter || (registrationType === 'rawat_jalan' && !form.kd_poli)"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Daftarkan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
