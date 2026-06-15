<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, LogOut, Save } from '@lucide/vue';
import { computed, reactive, toRef, watch } from 'vue';
import { pulangkanRanap, updatePulangRanap } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
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
import type { PasienRanapRme, RmeOption } from '@/types';

const props = defineProps<{
    patient: PasienRanapRme | null;
    statuses: RmeOption[];
    mode: 'create' | 'edit';
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    tgl_keluar: '',
    jam_keluar: '',
    stts_pulang: '',
    diagnosa_akhir: '',
    kd_penyakit: '',
    processing: false,
    errors: {} as Record<string, string>,
});

const tanggalKeluar = useTanggalCalendar(toRef(form, 'tgl_keluar'));
const title = computed(() => props.mode === 'edit' ? 'Edit Pulang Pasien' : 'Pulangkan Pasien');
const usableStatuses = computed(() => props.statuses.filter((status) => !['-', 'Pindah Kamar'].includes(status.value)));

watch(
    () => [open.value, props.patient, props.mode],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.tgl_keluar = props.mode === 'edit' && props.patient.tgl_keluar && props.patient.tgl_keluar !== '0000-00-00'
            ? props.patient.tgl_keluar
            : tanggalHariIni();
        form.jam_keluar = props.mode === 'edit' && props.patient.jam_keluar && props.patient.jam_keluar !== '00:00:00'
            ? props.patient.jam_keluar.slice(0, 5)
            : waktuSekarang();
        form.stts_pulang = props.mode === 'edit' && props.patient.stts_pulang !== '-'
            ? props.patient.stts_pulang
            : 'Atas Persetujuan Dokter';
        form.diagnosa_akhir = props.patient.diagnosa_akhir && props.patient.diagnosa_akhir !== '-'
            ? props.patient.diagnosa_akhir
            : '';
        form.kd_penyakit = '';
        form.errors = {};
    },
    { immediate: true },
);

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    const endpoint = props.mode === 'edit' ? updatePulangRanap.url() : pulangkanRanap.url();
    const method = props.mode === 'edit' ? router.patch : router.post;

    method(endpoint, {
        no_rawat: props.patient.no_rawat,
        tgl_keluar: form.tgl_keluar,
        jam_keluar: form.jam_keluar,
        stts_pulang: form.stts_pulang,
        diagnosa_akhir: form.diagnosa_akhir,
        kd_penyakit: form.kd_penyakit || null,
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
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-amber-500/8 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="muted" size="sm" class="font-mono">RM {{ patient.no_rkm_medis }}</Badge>
                        <Badge variant="soft-primary" size="sm">{{ patient.nm_bangsal }}</Badge>
                        <Badge variant="soft-indigo" size="sm">{{ patient.kd_kamar }}</Badge>
                    </div>
                    <p class="mt-2 text-lg font-semibold leading-tight">{{ patient.nm_pasien }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Masuk {{ patient.tgl_masuk }} {{ patient.jam_masuk }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Tanggal Keluar</Label>
                        <Popover v-model:open="tanggalKeluar.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-start text-left font-normal">
                                    <CalendarIcon class="size-4" />
                                    {{ tanggalKeluar.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalKeluar.value" initial-focus />
                            </PopoverContent>
                        </Popover>
                        <InputError :message="form.errors.tgl_keluar" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="jam-keluar-ranap">Jam Keluar</Label>
                        <Input id="jam-keluar-ranap" v-model="form.jam_keluar" type="time" />
                        <InputError :message="form.errors.jam_keluar" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Status Pulang</Label>
                    <Select v-model="form.stts_pulang">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih status pulang" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in usableStatuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.stts_pulang" />
                </div>

                <div class="grid gap-2">
                    <Label>Diagnosa Akhir</Label>
                    <AutocompleteDiagnosaRanap
                        v-model:diagnosa="form.diagnosa_akhir"
                        v-model:kode-penyakit="form.kd_penyakit"
                    />
                    <InputError :message="form.errors.diagnosa_akhir" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="size-4" />
                        <component :is="mode === 'edit' ? Save : LogOut" v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
