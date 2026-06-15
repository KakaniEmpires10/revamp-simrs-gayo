<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, Save } from '@lucide/vue';
import { computed, reactive, toRef, watch } from 'vue';
import { storeProfile, updateProfile } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunDokterController';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { AppSelect } from '@/components/ui/form';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import type { DoctorAccount, SpecialistOption } from '@/types';

const props = defineProps<{
    specialists: SpecialistOption[];
}>();

const open = defineModel<boolean>('open', { required: true });
const doctor = defineModel<DoctorAccount | null>('doctor', { required: true });

const form = reactive({
    kd_dokter: '',
    nm_dokter: '',
    no_ktp: '',
    jk: 'L',
    tmp_lahir: '',
    tgl_lahir: '',
    gol_drh: '-',
    agama: '',
    almt_tgl: '',
    no_telp: '',
    stts_nikah: '',
    kd_sps: '-',
    alumni: '',
    no_nip: '',
    no_ijn_praktek: '',
    status: '1',
    processing: false,
});

const tanggalLahir = useTanggalCalendar(toRef(form, 'tgl_lahir'));

const isEditing = computed(() => Boolean(doctor.value));
const specialistOptions = computed(() => props.specialists.map((specialist) => ({
    value: specialist.kd_sps,
    label: specialist.nm_sps,
})));

const genderOptions = [
    { value: 'L', label: 'Laki-laki' },
    { value: 'P', label: 'Perempuan' },
];

const bloodOptions = ['-', 'A', 'B', 'O', 'AB'].map((value) => ({ value, label: value }));
const marriageOptions = ['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA', 'JOMBLO'].map((value) => ({ value, label: value }));
const statusOptions = [
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Non Aktif' },
];

watch([open, doctor], () => {
    if (!open.value) {
        return;
    }

    form.kd_dokter = doctor.value?.kd_dokter ?? '';
    form.nm_dokter = doctor.value?.nm_dokter ?? '';
    form.no_ktp = doctor.value?.no_ktp ?? '';
    form.jk = doctor.value?.jk ?? 'L';
    form.tmp_lahir = doctor.value?.tmp_lahir ?? '';
    form.tgl_lahir = doctor.value?.tgl_lahir ?? '';
    form.gol_drh = doctor.value?.gol_drh ?? '-';
    form.agama = doctor.value?.agama ?? '';
    form.almt_tgl = doctor.value?.almt_tgl ?? '';
    form.no_telp = doctor.value?.no_telp ?? '';
    form.stts_nikah = doctor.value?.stts_nikah ?? '';
    form.kd_sps = doctor.value?.kd_sps ?? '-';
    form.alumni = doctor.value?.alumni ?? '';
    form.no_nip = doctor.value?.no_nip ?? '';
    form.no_ijn_praktek = doctor.value?.no_ijn_praktek ?? '';
    form.status = doctor.value?.status ?? '1';
});

function submit(): void {
    form.processing = true;

    const payload = {
        kd_dokter: form.kd_dokter,
        nm_dokter: form.nm_dokter,
        no_ktp: form.no_ktp,
        jk: form.jk,
        tmp_lahir: form.tmp_lahir,
        tgl_lahir: form.tgl_lahir,
        gol_drh: form.gol_drh,
        agama: form.agama,
        almt_tgl: form.almt_tgl,
        no_telp: form.no_telp,
        stts_nikah: form.stts_nikah,
        kd_sps: form.kd_sps,
        alumni: form.alumni,
        no_nip: form.no_nip,
        no_ijn_praktek: form.no_ijn_praktek,
        status: form.status,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            doctor.value = null;
        },
        onFinish: () => {
            form.processing = false;
        },
    };

    if (doctor.value) {
        router.patch(updateProfile.url(doctor.value.kd_dokter), payload, options);

        return;
    }

    router.post(storeProfile.url(), payload, options);
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Edit Dokter' : 'Tambah Dokter' }}</DialogTitle>
            </DialogHeader>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2">
                    <Label>Kode Dokter / NIK</Label>
                    <Input v-model="form.kd_dokter" :disabled="isEditing" />
                </label>
                <label class="grid gap-2">
                    <Label>Nama</Label>
                    <Input v-model="form.nm_dokter" />
                </label>
                <label class="grid gap-2">
                    <Label>No. KTP</Label>
                    <Input v-model="form.no_ktp" />
                </label>
                <label class="grid gap-2">
                    <Label>Jenis Kelamin</Label>
                    <AppSelect v-model="form.jk" :options="genderOptions" />
                </label>
                <label class="grid gap-2">
                    <Label>Tempat Lahir</Label>
                    <Input v-model="form.tmp_lahir" />
                </label>
                <label class="grid gap-2">
                    <Label>Tanggal Lahir</Label>
                    <Popover v-model:open="tanggalLahir.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalLahir.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalLahir.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                </label>
                <label class="grid gap-2">
                    <Label>Golongan Darah</Label>
                    <AppSelect v-model="form.gol_drh" :options="bloodOptions" />
                </label>
                <label class="grid gap-2">
                    <Label>Agama</Label>
                    <Input v-model="form.agama" />
                </label>
                <label class="grid gap-2 sm:col-span-2">
                    <Label>Alamat</Label>
                    <Input v-model="form.almt_tgl" />
                </label>
                <label class="grid gap-2">
                    <Label>No. Telepon</Label>
                    <Input v-model="form.no_telp" />
                </label>
                <label class="grid gap-2">
                    <Label>Status Nikah</Label>
                    <AppSelect v-model="form.stts_nikah" :options="marriageOptions" placeholder="Pilih status" />
                </label>
                <label class="grid gap-2">
                    <Label>Spesialis</Label>
                    <AppSelect v-model="form.kd_sps" :options="specialistOptions" />
                </label>
                <label class="grid gap-2">
                    <Label>Status</Label>
                    <AppSelect v-model="form.status" :options="statusOptions" />
                </label>
                <label class="grid gap-2">
                    <Label>Alumni</Label>
                    <Input v-model="form.alumni" />
                </label>
                <label class="grid gap-2">
                    <Label>No. NIP</Label>
                    <Input v-model="form.no_nip" />
                </label>
                <label class="grid gap-2 sm:col-span-2">
                    <Label>No. Izin Praktek</Label>
                    <Input v-model="form.no_ijn_praktek" />
                </label>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="open = false">Batal</Button>
                <Button :disabled="form.processing || !form.kd_dokter || !form.nm_dokter || !form.kd_sps" @click="submit">
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
