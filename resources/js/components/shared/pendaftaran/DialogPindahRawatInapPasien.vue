<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { BedDouble, CalendarIcon, Check, ChevronsUpDown, Info, Save, Search } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import { reference, storeInpatientTransfer } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
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
import { Calendar } from '@/components/ui/calendar';
import { AppTimeInput } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverAnchor,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { RegisteredPatientRow, RegistrationOption } from '@/types';
import { tanggalHariIni, waktuSekarang } from '@/lib/pasien';

type RoomOption = RegistrationOption & {
    kelas?: string;
    tarif?: number;
    status?: string;
};

const props = defineProps<{
    patient: RegisteredPatientRow | null;
    reloadProps?: string[];
    resetProps?: string[];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    kd_kamar: '',
    kd_dokter: '',
    tgl_masuk: '',
    jam_masuk: '',
    diagnosa_awal: '',
    kd_penyakit: '',
    mode_diagnosa: 'manual' as 'manual' | 'referensi',
    processing: false,
    errors: {} as Record<string, string>,
});

const tanggalMasuk = useTanggalCalendar(toRef(form, 'tgl_masuk'));

const roomOpen = ref(false);
const roomOptions = ref<RoomOption[]>([]);
const roomLoading = ref(false);
const roomError = ref('');

const doctorOpen = ref(false);
const doctorOptions = ref<RegistrationOption[]>([]);
const doctorLoading = ref(false);
const doctorError = ref('');

// Ã¢â€â‚¬Ã¢â€â‚¬ Diagnosa Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
const diagnosisOpen = ref(false);
const diagnosisOptions = ref<RegistrationOption[]>([]);
const diagnosisLoading = ref(false);
const diagnosisError = ref('');
const diagnosisInputRef = ref<InstanceType<typeof Input> | null>(null);

/**
 * Buka suggestions HANYA kalau:
 * - sedang loading (beri feedback langsung ke user), ATAU
 * - sudah ada pilihan yang bisa ditampilkan
 * Ini mencegah dropdown kosong yang muncul saat pertama kali focus.
 */
const showDiagnosisSuggestions = computed(
    () => diagnosisLoading.value || diagnosisOptions.value.length > 0,
);

watch(showDiagnosisSuggestions, (val) => {
    // Sinkronkan state popover dengan ketersediaan suggestions.
    // Jika tidak ada suggestions, tutup Ã¢â‚¬â€ tapi jangan paksa buka.
    if (!val) {
        diagnosisOpen.value = false;
    }
});
// Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

const selectedRoomLabel = computed(() => {
    return roomOptions.value.find((room) => room.value === form.kd_kamar)?.label || 'Pilih kamar rawat inap';
});

const selectedDoctorLabel = computed(() => {
    return doctorOptions.value.find((doctor) => doctor.value === form.kd_dokter)?.label || 'Pilih DPJP rawat inap';
});

const selectedRoom = computed(() => roomOptions.value.find((room) => room.value === form.kd_kamar) ?? null);
const hakKelasLabel = computed(() => {
    return props.patient?.klsrawat ? `Kelas ${props.patient.klsrawat}` : 'Tidak ada SEP';
});
const hakKelasVariant = computed(() => props.patient?.klsrawat ? 'soft-info' : 'soft-warning');
const kelasBerbedaDariHak = computed(() => {
    if (!props.patient?.klsrawat || !selectedRoom.value?.kelas) {
        return false;
    }

    return selectedRoom.value.kelas !== `Kelas ${props.patient.klsrawat}`;
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        isiFormAwal(props.patient);
        void loadRooms('');
        void loadDoctors('');
    },
    { immediate: true },
);

function isiFormAwal(patient: RegisteredPatientRow): void {
    form.kd_kamar = '';
    form.kd_dokter = patient.kd_dokter;
    form.tgl_masuk = tanggalHariIni();
    form.jam_masuk = waktuSekarang();
    form.kd_penyakit = patient.diagawal || '';
    form.diagnosa_awal = patient.nmdiagnosaawal || '';
    form.mode_diagnosa = patient.diagawal ? 'referensi' : 'manual';
    form.errors = {};
    // Reset suggestions agar tidak ada sisa dari sesi sebelumnya
    diagnosisOptions.value = [];
    diagnosisError.value = '';
    diagnosisOpen.value = false;
}

async function loadRooms(query: string): Promise<void> {
    roomLoading.value = true;
    roomError.value = '';

    try {
        const response = await fetch(reference.url({ query: { type: 'room', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat kamar.');
        }

        const payload = await response.json() as { data?: RoomOption[] };
        roomOptions.value = payload.data ?? [];
    } catch {
        roomError.value = 'Gagal memuat kamar rawat inap.';
        roomOptions.value = [];
    } finally {
        roomLoading.value = false;
    }
}

async function loadDoctors(query: string): Promise<void> {
    doctorLoading.value = true;
    doctorError.value = '';

    try {
        const response = await fetch(reference.url({ query: { type: 'doctor', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat dokter.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        const options = payload.data ?? [];

        if (form.kd_dokter && props.patient && !options.some((doctor) => doctor.value === form.kd_dokter)) {
            options.unshift({
                value: form.kd_dokter,
                label: props.patient.nm_dokter,
                description: 'Dokter pendaftaran saat ini',
            });
        }

        doctorOptions.value = options;
    } catch {
        doctorError.value = 'Gagal memuat dokter.';
        doctorOptions.value = [];
    } finally {
        doctorLoading.value = false;
    }
}

async function loadDiagnoses(query: string): Promise<void> {
    if (query.trim().length < 2) {
        // Query terlalu pendek Ã¢â‚¬â€ bersihkan suggestions & tutup dropdown
        diagnosisOptions.value = [];
        diagnosisError.value = '';

        return;
    }

    diagnosisLoading.value = true;
    diagnosisError.value = '';
    // Buka popover lebih awal saat loading agar ada feedback visual instan
    diagnosisOpen.value = true;

    try {
        const response = await fetch(reference.url({ query: { type: 'diagnosis', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat diagnosa.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        diagnosisOptions.value = payload.data ?? [];

        // Buka jika ada hasil; tutup jika kosong (user tetap bisa lanjut manual)
        diagnosisOpen.value = diagnosisOptions.value.length > 0;
    } catch {
        diagnosisError.value = 'Gagal memuat referensi diagnosa.';
        diagnosisOptions.value = [];
        diagnosisOpen.value = false;
    } finally {
        diagnosisLoading.value = false;
    }
}

const cariKamar = useDebounceFn((query: string) => {
    void loadRooms(query);
}, 300);

const cariDokter = useDebounceFn((query: string) => {
    void loadDoctors(query);
}, 300);

const cariDiagnosa = useDebounceFn((query: string) => {
    void loadDiagnoses(query);
}, 300);

function pilihKamar(room: RoomOption): void {
    form.kd_kamar = room.value;
    roomOpen.value = false;
}

function pilihDokter(doctor: RegistrationOption): void {
    form.kd_dokter = doctor.value;
    doctorOpen.value = false;
}

function ubahDiagnosa(value: string | number): void {
    const text = String(value);

    form.diagnosa_awal = text;
    form.kd_penyakit = '';
    form.mode_diagnosa = 'manual';

    // Jangan manipulasi diagnosisOpen di sini Ã¢â‚¬â€ biarkan loadDiagnoses & watcher yang urus
    cariDiagnosa(text);
}

function pilihDiagnosa(diagnosis: RegistrationOption): void {
    form.kd_penyakit = diagnosis.value;
    form.diagnosa_awal = diagnosis.label;
    form.mode_diagnosa = 'referensi';
    diagnosisOpen.value = false;
    // Kembalikan fokus ke input agar user bisa lanjut navigasi dengan keyboard
    diagnosisInputRef.value?.$el?.focus?.();
}

/**
 * Tutup suggestions saat user klik di luar area input+dropdown.
 * `@interact-outside` dari Reka UI/Radix dipanggil saat ada pointer event
 * di luar PopoverContent. Kita perlu pastikan interaksi dengan input
 * (PopoverAnchor) tidak menutup popover.
 */
function onDiagnosisInteractOutside(event: Event): void {
    // Jika target adalah input diagnosa itu sendiri, jangan tutup
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

    router.post(storeInpatientTransfer.url(props.patient.no_rawat), {
        kd_kamar: form.kd_kamar,
        kd_dokter: form.kd_dokter,
        tgl_masuk: form.tgl_masuk,
        jam_masuk: form.jam_masuk,
        diagnosa_awal: form.diagnosa_awal,
        kd_penyakit: form.kd_penyakit || null,
        mode_diagnosa: form.mode_diagnosa,
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
        <DialogContent class="max-h-[95vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Pindah Rawat Inap</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div
                    class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-amber-500/8 p-4 shadow-sm">
                    <BedDouble class="absolute -right-8 -bottom-8 size-36 rotate-[-10deg] text-primary/10" />

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
                                <Badge :variant="hakKelasVariant" size="sm">
                                    Hak Kelas: {{ hakKelasLabel }}
                                </Badge>
                                <Badge variant="muted" size="sm">
                                    {{ labelJenisKelamin(patient.jk) }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div
                                class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    Poli Asal
                                </p>
                                <p class="mt-1 truncate font-medium">
                                    {{ patient.nm_poli }}
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    Dokter Asal
                                </p>
                                <p class="mt-1 truncate font-medium">
                                    {{ patient.nm_dokter }}
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    Usia Saat Daftar
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}
                                </p>
                            </div>

                            <div
                                class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                                <p class="text-xs text-muted-foreground">
                                    SEP
                                </p>
                                <p class="mt-1 truncate font-mono text-xs font-medium">
                                    {{ patient.no_sep || 'Tidak ada SEP' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <Alert :variant="kelasBerbedaDariHak ? 'soft-warning' : 'soft-info'" size="sm">
                    <Info class="size-4" />
                    <div>
                        <AlertTitle>Perhatikan hak kelas pasien</AlertTitle>
                        <AlertDescription>
                            {{ kelasBerbedaDariHak
                                ? `Kamar terpilih ${selectedRoom?.kelas}, sedangkan hak kelas dari SEP adalah
                            ${hakKelasLabel}. Pastikan pasien menyetujui naik/turun kelas bila diperlukan.`
                                : 'Hak kelas ditampilkan dari bridging SEP jika tersedia. Pasien non-BPJS atau tanpa SEP tetap bisa dipindahkan sesuai kebijakan pendaftaran.' }}
                        </AlertDescription>
                    </div>
                </Alert>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label>Tanggal Masuk Ranap</Label>
                        <Popover v-model:open="tanggalMasuk.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalMasuk.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalMasuk.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                        <span v-if="form.errors.tgl_masuk" class="text-destructive text-xs">{{ form.errors.tgl_masuk
                            }}</span>
                    </label>

                    <label class="grid gap-2">
                        <Label>Jam Masuk Ranap</Label>
                        <AppTimeInput v-model="form.jam_masuk" />
                        <span v-if="form.errors.jam_masuk" class="text-destructive text-xs">{{ form.errors.jam_masuk
                            }}</span>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Kamar Rawat Inap</Label>
                        <Popover v-model:open="roomOpen">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" role="combobox" :aria-expanded="roomOpen"
                                    :disabled="roomLoading"
                                    class="h-10 w-full justify-between overflow-hidden bg-background px-3">
                                    <span class="truncate text-left">
                                        {{ roomLoading ? 'Memuat kamar...' : selectedRoomLabel }}
                                    </span>
                                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Cari kamar, bangsal, atau kelas..."
                                        @update:model-value="cariKamar" />
                                    <CommandList>
                                        <CommandEmpty>{{ roomError || 'Tidak ada kamar kosong yang cocok.' }}
                                        </CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem v-for="room in roomOptions" :key="room.value"
                                                :value="`${room.value} ${room.label} ${room.description || ''}`"
                                                @select="pilihKamar(room)">
                                                <Check
                                                    :class="cn('size-4', room.value === form.kd_kamar ? 'opacity-100' : 'opacity-0')" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">
                                                        {{ room.label }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted-foreground">
                                                        {{ room.description }}
                                                    </p>
                                                </div>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <span v-if="form.errors.kd_kamar" class="text-destructive text-xs">{{ form.errors.kd_kamar
                            }}</span>
                    </div>

                    <div class="grid gap-2">
                        <Label>DPJP Rawat Inap</Label>
                        <Popover v-model:open="doctorOpen">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" role="combobox" :aria-expanded="doctorOpen"
                                    :disabled="doctorLoading"
                                    class="h-10 w-full justify-between overflow-hidden bg-background px-3">
                                    <span class="truncate text-left">
                                        {{ doctorLoading ? 'Memuat dokter...' : selectedDoctorLabel }}
                                    </span>
                                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Cari dokter..." @update:model-value="cariDokter" />
                                    <CommandList>
                                        <CommandEmpty>{{ doctorError || 'Tidak ada dokter aktif yang cocok.' }}
                                        </CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem v-for="doctor in doctorOptions" :key="doctor.value"
                                                :value="`${doctor.value} ${doctor.label} ${doctor.description || ''}`"
                                                @select="pilihDokter(doctor)">
                                                <Check
                                                    :class="cn('size-4', doctor.value === form.kd_dokter ? 'opacity-100' : 'opacity-0')" />
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
                        <span v-if="form.errors.kd_dokter" class="text-destructive text-xs">{{ form.errors.kd_dokter
                            }}</span>
                    </div>
                </div>

                <!-- Ã¢â€â‚¬Ã¢â€â‚¬ Diagnosa Masuk Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ -->
                <div class="grid gap-2">
                    <Label>Diagnosa Masuk</Label>

                    <!--
                        Pola: PopoverAnchor wraps input + PopoverContent muncul di bawahnya.
                        Input TIDAK pernah memicu popover secara langsung Ã¢â‚¬â€ popover terbuka
                        hanya ketika ada results (dikontrol via diagnosisOpen di loadDiagnoses).
                        Ini menghilangkan "klik pertama untuk buka popover kosong".
                    -->
                    <Popover v-model:open="diagnosisOpen">
                        <PopoverAnchor class="w-full" as-child>
                            <div class="relative">
                                <Search
                                    class="absolute top-2.5 left-3 size-4 text-muted-foreground pointer-events-none" />
                                <Input ref="diagnosisInputRef" :model-value="form.diagnosa_awal" class="pl-9"
                                    placeholder="Ketik diagnosa bebas atau cari ICD, contoh: A00 atau cholera"
                                    autocomplete="off" @update:model-value="ubahDiagnosa"
                                    @keydown.escape="diagnosisOpen = false" @keydown.tab="diagnosisOpen = false" />
                            </div>
                        </PopoverAnchor>

                        <!--
                            disableOutsidePointerEvents: false  Ã¢â€ â€™ user tetap bisa klik elemen
                            lain tanpa popover "menghalangi".
                            @interact-outside: tutup popover, kecuali interaksi terjadi
                            di dalam input sendiri (PopoverAnchor).
                        -->
                        <PopoverContent class="w-[--reka-popover-trigger-width] p-0" align="start"
                            :disable-outside-pointer-events="false" @interact-outside="onDiagnosisInteractOutside"
                            @open-auto-focus.prevent>
                            <Command :should-filter="false">
                                <CommandList>
                                    <CommandEmpty>
                                        {{ diagnosisLoading
                                            ? 'Mencari referensi diagnosa...'
                                            : (diagnosisError || 'Tidak ada hasil. Lanjutkan sebagai input manual.') }}
                                    </CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem v-for="diagnosis in diagnosisOptions" :key="diagnosis.value"
                                            :value="`${diagnosis.value} ${diagnosis.label}`"
                                            @select="pilihDiagnosa(diagnosis)">
                                            <Check
                                                :class="cn('size-4 shrink-0', diagnosis.value === form.kd_penyakit ? 'opacity-100' : 'opacity-0')" />
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
                        <span>Dianjurkan mengisi diagnosa sesuai ICD 10, tapi bisa menginput nama penyakit secara
                            manual.</span>
                    </div>
                    <span v-if="form.errors.diagnosa_awal" class="text-destructive text-xs">{{ form.errors.diagnosa_awal
                        }}</span>
                </div>
                <!-- Ã¢â€â‚¬Ã¢â€â‚¬ /Diagnosa Masuk Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ -->

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                    <Button type="submit"
                        :disabled="form.processing || !form.kd_kamar || !form.kd_dokter || !form.diagnosa_awal">
                        <Spinner v-if="form.processing" />
                        <Save v-else class="size-4" />
                        Simpan Pindah Ranap
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
