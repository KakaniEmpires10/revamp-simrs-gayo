<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowRightLeft, Check, ChevronsUpDown, Save } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { reference, storeInternalReferral } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { AppSelect } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { RegisteredPatientRow, RegistrationOption } from '@/types';

const props = defineProps<{
    patient: RegisteredPatientRow | null;
    clinics: RegistrationOption[];
    igdClinicCode: string;
    reloadProps?: string[];
    resetProps?: string[];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    kd_poli: '',
    kd_dokter: '',
    processing: false,
    errors: {} as Record<string, string>,
});

const doctorOpen = ref(false);
const doctorOptions = ref<RegistrationOption[]>([]);
const doctorLoading = ref(false);
const doctorError = ref('');
const filterDoctorBySchedule = ref(true);
const sedangMengisiForm = ref(false);

const clinicOptions = computed(() => props.clinics.filter((clinic) => clinic.value !== props.igdClinicCode));
const shouldFilterDoctorBySchedule = computed(() => filterDoctorBySchedule.value);
const selectedDoctorLabel = computed(() => {
    return doctorOptions.value.find((doctor) => doctor.value === form.kd_dokter)?.label || 'Pilih dokter tujuan';
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        sedangMengisiForm.value = true;
        form.kd_poli = '';
        form.kd_dokter = '';
        form.errors = {};
        filterDoctorBySchedule.value = true;
        doctorOptions.value = [];
        doctorError.value = '';
        sedangMengisiForm.value = false;
    },
    { immediate: true },
);

watch(
    () => [form.kd_poli, shouldFilterDoctorBySchedule.value],
    () => {
        if (!open.value || !props.patient || sedangMengisiForm.value) {
            return;
        }

        form.kd_dokter = '';
        doctorOptions.value = [];

        if (shouldFilterDoctorBySchedule.value && !form.kd_poli) {
            return;
        }

        void loadDoctors();
    },
);

async function loadDoctors(): Promise<void> {
    if (!props.patient || (shouldFilterDoctorBySchedule.value && !form.kd_poli)) {
        return;
    }

    doctorLoading.value = true;
    doctorError.value = '';

    const query: Record<string, string> = {
        type: 'doctor',
        registration_date: props.patient.tgl_registrasi,
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
        doctorOptions.value = payload.data ?? [];
    } catch {
        doctorError.value = shouldFilterDoctorBySchedule.value
            ? 'Gagal memuat jadwal dokter tujuan.'
            : 'Gagal memuat data dokter tujuan.';
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

    router.post(storeInternalReferral.url(props.patient.no_rawat), {
        kd_poli: form.kd_poli,
        kd_dokter: form.kd_dokter,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(props.reloadProps ?? ['registeredPatients', 'filters', 'view']),
        reset: props.resetProps ?? ['registeredPatients'],
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
                <DialogTitle>Rujuk Internal</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-sky-500/8 p-4 shadow-sm">
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
                                    Tanggal Registrasi
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ patient.tgl_registrasi }}
                                </p>
                            </div>

                            <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Usia Saat Daftar
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 rounded-lg border bg-muted/20 p-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Poli Asal</Label>
                        <div class="rounded-md border border-border bg-background px-3 py-2.5 text-sm font-medium">
                            {{ patient.nm_poli }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label>Dokter Asal</Label>
                        <div class="rounded-md border border-border bg-background px-3 py-2.5 text-sm font-medium">
                            {{ patient.nm_dokter }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Poliklinik Tujuan</Label>
                        <AppSelect
                            v-model="form.kd_poli"
                            :options="clinicOptions"
                            placeholder="Pilih poliklinik tujuan"
                        />
                        <span v-if="form.errors.kd_poli" class="text-destructive text-xs">{{ form.errors.kd_poli }}</span>
                    </label>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Dokter Tujuan</Label>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
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
                                        {{ doctorLoading ? 'Memuat dokter tujuan...' : selectedDoctorLabel }}
                                    </span>
                                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Cari dokter tujuan..." />
                                    <CommandList>
                                        <CommandEmpty>
                                            {{ doctorError || (shouldFilterDoctorBySchedule ? 'Tidak ada jadwal dokter untuk poli tujuan ini.' : 'Tidak ada dokter aktif yang cocok.') }}
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

                <div class="rounded-lg border border-dashed border-border/80 bg-muted/10 p-3 text-sm text-muted-foreground">
                    <div class="flex items-start gap-2">
                        <ArrowRightLeft class="mt-0.5 size-4 shrink-0" />
                        <p>Rujukan internal menambahkan tujuan poli dan dokter lain untuk no rawat yang sama. Poli dan dokter asal tidak berubah dari pendaftaran awal.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.kd_poli || !form.kd_dokter"
                    >
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Simpan Rujukan Internal
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
