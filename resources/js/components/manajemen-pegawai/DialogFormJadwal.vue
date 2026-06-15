<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/ManajemenPegawai/JadwalPraktekController';
import { Button } from '@/components/ui/button';
import { AppSelect, AppTimeInput } from '@/components/ui/form';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { ClinicOption, DoctorOption, PracticeSchedule } from '@/types';

const props = defineProps<{
    doctors: DoctorOption[];
    clinics: ClinicOption[];
    days: string[];
}>();

const open = defineModel<boolean>('open', { required: true });
const schedule = defineModel<PracticeSchedule | null>('schedule', { required: true });

const form = reactive({
    kd_dokter: '',
    hari_kerja: 'SENIN',
    jam_mulai: '',
    jam_selesai: '',
    kd_poli: '',
    kuota: '',
    processing: false,
});

const doctorOptions = computed(() => props.doctors.map((doctor) => ({
    value: doctor.kd_dokter,
    label: `${doctor.kd_dokter} - ${doctor.nm_dokter}`,
})));

const clinicOptions = computed(() => props.clinics.map((clinic) => ({
    value: clinic.kd_poli,
    label: clinic.nm_poli,
})));

const dayOptions = computed(() => props.days.map((day) => ({
    value: day,
    label: day,
})));

watch(
    schedule,
    (value) => {
        form.kd_dokter = value?.kd_dokter ?? '';
        form.hari_kerja = value?.hari_kerja ?? 'SENIN';
        form.jam_mulai = normalizeTime(value?.jam_mulai ?? '');
        form.jam_selesai = normalizeTime(value?.jam_selesai ?? '');
        form.kd_poli = value?.kd_poli ?? '';
        form.kuota = value?.kuota?.toString() ?? '';
    },
    { immediate: true },
);

watch(open, (value) => {
    if (value && !schedule.value) {
        form.kd_dokter = '';
        form.hari_kerja = 'SENIN';
        form.jam_mulai = '';
        form.jam_selesai = '';
        form.kd_poli = '';
        form.kuota = '';
    }
});

function normalizeTime(value: string): string {
    return value.slice(0, 5);
}

function submit(): void {
    const payload = {
        kd_dokter: form.kd_dokter,
        hari_kerja: form.hari_kerja,
        jam_mulai: form.jam_mulai,
        jam_selesai: form.jam_selesai || null,
        kd_poli: form.kd_poli,
        kuota: form.kuota || null,
    };

    form.processing = true;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            schedule.value = null;
            open.value = false;
        },
        onFinish: () => {
            form.processing = false;
        },
    };

    if (schedule.value) {
        router.patch(
            update.url({
                doctor: schedule.value.kd_dokter,
                day: schedule.value.hari_kerja,
                start: normalizeTime(schedule.value.jam_mulai),
            }),
            payload,
            options,
        );

        return;
    }

    router.post(store.url(), payload, options);
}

function closeDialog(): void {
    schedule.value = null;
    open.value = false;
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ schedule ? 'Ubah Jadwal Praktek' : 'Tambah Jadwal Praktek' }}</DialogTitle>
            </DialogHeader>

            <div class="grid gap-4">
                <label class="grid gap-2">
                    <Label>Dokter</Label>
                    <AppSelect v-model="form.kd_dokter" :options="doctorOptions" placeholder="Pilih dokter" />
                </label>
                <label class="grid gap-2">
                    <Label>Poliklinik</Label>
                    <AppSelect v-model="form.kd_poli" :options="clinicOptions" placeholder="Pilih poliklinik" />
                </label>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="grid gap-2">
                        <Label>Hari</Label>
                        <AppSelect v-model="form.hari_kerja" :options="dayOptions" />
                    </label>
                    <label class="grid gap-2">
                        <Label>Mulai</Label>
                        <AppTimeInput v-model="form.jam_mulai" />
                    </label>
                    <label class="grid gap-2">
                        <Label>Selesai</Label>
                        <AppTimeInput v-model="form.jam_selesai" />
                    </label>
                </div>
                <label class="grid gap-2">
                    <Label>Kuota</Label>
                    <Input v-model="form.kuota" type="number" min="0" inputmode="numeric" />
                </label>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="closeDialog">Batal</Button>
                <Button :disabled="form.processing || !form.kd_dokter || !form.kd_poli || !form.jam_mulai" @click="submit">
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
