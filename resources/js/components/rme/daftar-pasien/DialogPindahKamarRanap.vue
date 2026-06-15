<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bed, CalendarIcon, DoorOpen, Save, Search } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import { pindahKamarRanap } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import InputError from '@/components/InputError.vue';
import AutocompleteDiagnosaRanap from '@/components/rme/daftar-pasien/AutocompleteDiagnosaRanap.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni, waktuSekarang } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { PasienRanapRme, RmeOption } from '@/types';

type RoomOption = RmeOption & {
    description?: string;
    kelas?: string | null;
    tarif?: number;
    status?: string | null;
};

const props = defineProps<{
    patient: PasienRanapRme | null;
    availableRooms: RoomOption[];
}>();

const open = defineModel<boolean>('open', { required: true });

const search = ref('');
const form = reactive({
    kd_kamar: '',
    tgl_masuk: '',
    jam_masuk: '',
    diagnosa_awal: '',
    kd_penyakit: '',
    opsi_pindah_kamar: '3',
    processing: false,
    errors: {} as Record<string, string>,
});

const tanggalMasuk = useTanggalCalendar(toRef(form, 'tgl_masuk'));
const selectedRoom = computed(() => props.availableRooms.find((room) => room.value === form.kd_kamar) ?? null);
const opsiPindahKamar = [
    {
        value: '1',
        label: 'Kamar lama dihapus dari billing',
        description: 'Data kamar lama diganti menjadi kamar baru dan tanggal masuk mengikuti saat pindah.',
    },
    {
        value: '2',
        label: 'Ganti kamar pada data lama',
        description: 'Data kamar lama tetap satu record, kamar dan tarif diganti ke kamar baru.',
    },
    {
        value: '3',
        label: 'Statuskan pindah kamar',
        description: 'Kamar lama ditutup sebagai Pindah Kamar, lama inap dihitung, pasien masuk ke kamar baru.',
    },
    {
        value: '4',
        label: 'Statuskan pindah dengan tarif tertinggi',
        description: 'Seperti opsi 3, tetapi billing kamar lama memakai tarif tertinggi antara kamar lama dan baru.',
    },
];
const filteredRooms = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.availableRooms;
    }

    return props.availableRooms.filter((room) => {
        return room.label.toLowerCase().includes(query)
            || (room.description ?? '').toLowerCase().includes(query)
            || (room.kelas ?? '').toLowerCase().includes(query);
    });
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.kd_kamar = '';
        form.tgl_masuk = tanggalHariIni();
        form.jam_masuk = waktuSekarang();
        form.diagnosa_awal = props.patient.diagnosa_awal || '';
        form.kd_penyakit = '';
        form.opsi_pindah_kamar = '3';
        form.errors = {};
        search.value = '';
    },
    { immediate: true },
);

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.post(pindahKamarRanap.url(), {
        no_rawat: props.patient.no_rawat,
        kd_kamar: form.kd_kamar,
        tgl_masuk: form.tgl_masuk,
        jam_masuk: form.jam_masuk,
        diagnosa_awal: form.diagnosa_awal,
        kd_penyakit: form.kd_penyakit || null,
        opsi_pindah_kamar: form.opsi_pindah_kamar,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['patients', 'filters', 'availableRooms']),
        reset: ['patients'],
        onSuccess: (page) => {
            if (isFeedbackSuccess(page)) {
                open.value = false;
            }
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
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Pindah Kamar Rawat Inap</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-sky-500/8 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="soft-primary" size="sm">{{ patient.nm_bangsal }}</Badge>
                        <Badge variant="soft-indigo" size="sm">{{ patient.kd_kamar }}</Badge>
                        <Badge variant="muted" size="sm">{{ patient.kelas }}</Badge>
                    </div>
                    <p class="mt-2 text-lg font-semibold leading-tight">{{ patient.nm_pasien }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Masuk {{ patient.tgl_masuk }} {{ patient.jam_masuk }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>Kamar Tujuan</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" class="pl-9" placeholder="Cari kamar, bangsal, atau kelas..." />
                    </div>
                    <div class="max-h-64 overflow-y-auto rounded-md border">
                        <button
                            v-for="room in filteredRooms"
                            :key="room.value"
                            type="button"
                            :class="cn(
                                'grid w-full gap-1 border-b px-3 py-2.5 text-left last:border-b-0 hover:bg-muted/50',
                                form.kd_kamar === room.value ? 'bg-primary/10 text-primary hover:bg-primary/10' : ''
                            )"
                            @click="form.kd_kamar = room.value"
                        >
                            <span class="inline-flex items-center gap-2 text-sm font-semibold">
                                <DoorOpen class="size-4" />
                                {{ room.label }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ room.description }}</span>
                        </button>
                        <div v-if="!filteredRooms.length" class="px-3 py-6 text-center text-sm text-muted-foreground">
                            Kamar kosong tidak ditemukan.
                        </div>
                    </div>
                    <InputError :message="form.errors.kd_kamar" />
                    <Badge v-if="selectedRoom" variant="soft-success" size="sm" class="w-fit">
                        <Bed class="size-3" />
                        Kamar tujuan {{ selectedRoom.label }}
                    </Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Tanggal Masuk Kamar Baru</Label>
                        <Popover v-model:open="tanggalMasuk.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start text-left font-normal">
                                    <CalendarIcon class="size-4" />
                                    {{ tanggalMasuk.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalMasuk.value" initial-focus />
                            </PopoverContent>
                        </Popover>
                        <InputError :message="form.errors.tgl_masuk" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="jam-masuk-pindah">Jam Masuk Kamar Baru</Label>
                        <Input id="jam-masuk-pindah" v-model="form.jam_masuk" type="time" />
                        <InputError :message="form.errors.jam_masuk" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Diagnosa Awal Kamar Baru</Label>
                    <AutocompleteDiagnosaRanap
                        v-model:diagnosa="form.diagnosa_awal"
                        v-model:kode-penyakit="form.kd_penyakit"
                    />
                    <InputError :message="form.errors.diagnosa_awal" />
                </div>

                <div class="grid gap-2">
                    <Label>Opsi Pindah Kamar</Label>
                    <Select v-model="form.opsi_pindah_kamar">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih opsi pindah kamar" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in opsiPindahKamar" :key="option.value" :value="option.value">
                                {{ option.value }}. {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        {{ opsiPindahKamar.find((option) => option.value === form.opsi_pindah_kamar)?.description }}
                    </p>
                    <InputError :message="form.errors.opsi_pindah_kamar" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.kd_kamar">
                        <Spinner v-if="form.processing" class="size-4" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
