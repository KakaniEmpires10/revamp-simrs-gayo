<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, Save } from '@lucide/vue';
import { computed, reactive, toRef, watch } from 'vue';
import { storeProfile, updateProfile } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunPetugasController';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import type { PositionOption, StaffAccount } from '@/types';

const props = defineProps<{
    positions: PositionOption[];
}>();

const open = defineModel<boolean>('open', { required: true });
const staff = defineModel<StaffAccount | null>('staff', { required: true });

const form = reactive({
    nip: '',
    nama: '',
    no_ktp: '',
    jk: 'L',
    tmp_lahir: '',
    tgl_lahir: '',
    gol_darah: '-',
    agama: '',
    stts_nikah: '',
    alamat: '',
    kd_jbtn: '',
    no_telp: '',
    status: '1',
    processing: false,
});

const tanggalLahir = useTanggalCalendar(toRef(form, 'tgl_lahir'));

const isEditing = computed(() => Boolean(staff.value));
const positionOptions = computed(() =>
    props.positions.map((position) => ({
        value: position.kd_jbtn,
        label: position.nm_jbtn,
    })),
);

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

watch([open, staff], () => {
    if (!open.value) {
        return;
    }

    form.nip = staff.value?.nip ?? '';
    form.nama = staff.value?.nama ?? '';
    form.no_ktp = staff.value?.no_ktp ?? '';
    form.jk = staff.value?.jk ?? 'L';
    form.tmp_lahir = staff.value?.tmp_lahir ?? '';
    form.tgl_lahir = staff.value?.tgl_lahir ?? '';
    form.gol_darah = staff.value?.gol_darah ?? '-';
    form.agama = staff.value?.agama ?? '';
    form.stts_nikah = staff.value?.stts_nikah ?? '';
    form.alamat = staff.value?.alamat ?? '';
    form.kd_jbtn = staff.value?.kd_jbtn ?? props.positions[0]?.kd_jbtn ?? '';
    form.no_telp = staff.value?.no_telp ?? '';
    form.status = staff.value?.status ?? '1';
});

function submit(): void {
    form.processing = true;

    const payload = {
        nip: form.nip,
        nama: form.nama,
        no_ktp: form.no_ktp,
        jk: form.jk,
        tmp_lahir: form.tmp_lahir,
        tgl_lahir: form.tgl_lahir,
        gol_darah: form.gol_darah,
        agama: form.agama,
        stts_nikah: form.stts_nikah,
        alamat: form.alamat,
        kd_jbtn: form.kd_jbtn,
        no_telp: form.no_telp,
        status: form.status,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            staff.value = null;
        },
        onFinish: () => {
            form.processing = false;
        },
    };

    if (staff.value) {
        router.patch(updateProfile.url(staff.value.nip), payload, options);

        return;
    }

    router.post(storeProfile.url(), payload, options);
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Edit Petugas' : 'Tambah Petugas' }}</DialogTitle>
            </DialogHeader>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2">
                    <Label>NIP / NIK</Label>
                    <Input v-model="form.nip" :disabled="isEditing" />
                </label>
                <label class="grid gap-2">
                    <Label>Nama</Label>
                    <Input v-model="form.nama" />
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
                    <AppSelect v-model="form.gol_darah" :options="bloodOptions" />
                </label>
                <label class="grid gap-2">
                    <Label>Agama</Label>
                    <Input v-model="form.agama" />
                </label>
                <label class="grid gap-2 sm:col-span-2">
                    <Label>Alamat</Label>
                    <Input v-model="form.alamat" />
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
                    <Label>Jabatan</Label>
                    <AppSelect v-model="form.kd_jbtn" :options="positionOptions" placeholder="Pilih jabatan" />
                </label>
                <label class="grid gap-2">
                    <Label>Status</Label>
                    <AppSelect v-model="form.status" :options="statusOptions" />
                </label>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="open = false">Batal</Button>
                <Button :disabled="form.processing || !form.nip || !form.nama || !form.kd_jbtn" @click="submit">
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
