<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Activity, IdCard, LoaderCircle, Save, Stethoscope, UserRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { ttvPasien, simpanTtvPasien } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import InputError from '@/components/InputError.vue';
import GcsPopover from '@/components/rme/pemeriksaan/GcsPopover.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Skeleton } from '@/components/ui/skeleton';
import { bolehInputAngkaTtv, bolehInputDesimalTtv, bolehInputTensiTtv, sanitasiAngkaTtv, sanitasiDesimalTtv, sanitasiTensiTtv } from '@/composables/useInputTtv';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import type { PasienDialogTtvRme, TtvPasienRme } from '@/types';

const props = defineProps<{
    patient: PasienDialogTtvRme | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    no_rawat: '',
    suhu_tubuh: '',
    tensi: '',
    nadi: '',
    respirasi: '',
    spo2: '',
    tinggi: '',
    berat: '',
    gcs: '',
    kesadaran: 'Compos Mentis',
});

const ttvLoading = ref(false);
const isLoading = computed(() => ttvLoading.value);
const lokasiPasien = computed(() => {
    if (!props.patient) {
        return '-';
    }

    return props.patient.nm_bangsal || props.patient.nm_poli_tujuan || props.patient.nm_poli || props.patient.status_lanjut;
});
const dokterPasien = computed(() => props.patient?.nm_dokter_tujuan || props.patient?.nm_dokter || '-');

watch(
    () => [open.value, props.patient?.no_rawat],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        muatTtv();
    },
    { immediate: true },
);

async function muatTtv(): Promise<void> {
    if (!props.patient) {
        return;
    }

    form.clearErrors();
    ttvLoading.value = true;

    try {
        const response = await fetch(ttvPasien.url({
            query: { no_rawat: props.patient.no_rawat },
        }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal mengambil tanda vital pasien.');
        }

        const payload = await response.json() as { ttv: TtvPasienRme };
        isiForm(payload.ttv);
    } catch {
        form.setError('no_rawat', 'Tanda vital pasien gagal dimuat. Tutup dialog lalu buka kembali.');
    } finally {
        ttvLoading.value = false;
    }
}

function isiForm(ttv: TtvPasienRme): void {
    form.no_rawat = props.patient?.no_rawat ?? '';
    form.suhu_tubuh = ttv.suhu_tubuh;
    form.tensi = ttv.tensi;
    form.nadi = ttv.nadi;
    form.respirasi = ttv.respirasi;
    form.spo2 = ttv.spo2;
    form.tinggi = ttv.tinggi;
    form.berat = ttv.berat;
    form.gcs = ttv.gcs;
    form.kesadaran = ttv.kesadaran || 'Compos Mentis';
}

function simpan(): void {
    if (!props.patient) {
        return;
    }

    form.no_rawat = props.patient.no_rawat;

    form.put(simpanTtvPasien.url(), {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['flash']),
        onSuccess: (page) => {
            if (isFeedbackSuccess(page)) {
                open.value = false;
            }
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Tanda Vital Pasien</DialogTitle>
                <DialogDescription>
                    Simpan nilai TTV terbaru untuk nomor rawat pasien.
                </DialogDescription>
            </DialogHeader>

            <div v-if="patient" class="grid gap-5">
                <section class="rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-sky-500/8 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <UserRound class="size-4 text-muted-foreground" />
                                <h3 class="truncate text-lg font-semibold leading-tight">{{ patient.nm_pasien }}</h3>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge variant="soft-primary" size="sm" class="font-mono">RM {{ patient.no_rkm_medis }}</Badge>
                                <Badge variant="muted" size="sm" class="font-mono">{{ patient.no_rawat }}</Badge>
                                <Badge variant="soft-info" size="sm">{{ labelJenisKelamin(patient.jk) }}</Badge>
                                <Badge variant="soft-warning" size="sm">{{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}</Badge>
                                <Badge variant="soft-indigo" size="sm">{{ patient.png_jawab }}</Badge>
                            </div>
                        </div>
                        <Badge variant="soft-success" rounded="md" class="w-fit">
                            <Activity class="size-3.5" />
                            TTV Terbaru
                        </Badge>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <Stethoscope class="size-4" />
                            <span class="truncate">{{ dokterPasien }}</span>
                        </div>
                        <div class="truncate text-muted-foreground">{{ lokasiPasien }}</div>
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <IdCard class="size-4" />
                            <span class="truncate">{{ patient.no_ktp || '-' }}</span>
                        </div>
                    </div>
                </section>

                <form class="grid gap-4" @submit.prevent="simpan">
                    <div v-if="isLoading" class="grid gap-4 md:grid-cols-4">
                        <Skeleton v-for="index in 8" :key="index" class="h-16 rounded-md" />
                    </div>

                    <template v-else>
                        <InputError :message="form.errors.no_rawat" />

                        <div class="grid gap-4 md:grid-cols-4">
                            <label class="grid content-start gap-2">
                                <Label for="ttv_suhu">Suhu</Label>
                                <Input id="ttv_suhu" :model-value="form.suhu_tubuh" inputmode="decimal" placeholder="36.5" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.suhu_tubuh = sanitasiDesimalTtv($event)" />
                                <InputError :message="form.errors.suhu_tubuh" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_tensi">Tensi</Label>
                                <Input id="ttv_tensi" :model-value="form.tensi" inputmode="numeric" placeholder="120/80" @beforeinput="bolehInputTensiTtv" @update:model-value="form.tensi = sanitasiTensiTtv($event)" />
                                <InputError :message="form.errors.tensi" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_nadi">Nadi</Label>
                                <Input id="ttv_nadi" :model-value="form.nadi" inputmode="numeric" placeholder="80" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.nadi = sanitasiAngkaTtv($event)" />
                                <InputError :message="form.errors.nadi" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_respirasi">Respirasi</Label>
                                <Input id="ttv_respirasi" :model-value="form.respirasi" inputmode="numeric" placeholder="20" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.respirasi = sanitasiAngkaTtv($event)" />
                                <InputError :message="form.errors.respirasi" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_spo2">SpO2</Label>
                                <Input id="ttv_spo2" :model-value="form.spo2" inputmode="numeric" placeholder="98" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.spo2 = sanitasiAngkaTtv($event)" />
                                <InputError :message="form.errors.spo2" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_tinggi">Tinggi</Label>
                                <Input id="ttv_tinggi" :model-value="form.tinggi" inputmode="decimal" placeholder="165" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.tinggi = sanitasiDesimalTtv($event)" />
                                <InputError :message="form.errors.tinggi" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label for="ttv_berat">Berat</Label>
                                <Input id="ttv_berat" :model-value="form.berat" inputmode="decimal" placeholder="60" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.berat = sanitasiDesimalTtv($event)" />
                                <InputError :message="form.errors.berat" />
                            </label>
                            <label class="grid content-start gap-2">
                                <Label>GCS</Label>
                                <GcsPopover v-model:gcs="form.gcs" v-model:kesadaran="form.kesadaran" />
                                <InputError :message="form.errors.gcs" />
                            </label>
                            <label class="grid content-start gap-2 md:col-span-2">
                                <Label for="ttv_kesadaran">Kesadaran</Label>
                                <Input id="ttv_kesadaran" v-model="form.kesadaran" readonly />
                                <InputError :message="form.errors.kesadaran" />
                            </label>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                                Batal
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                                <Save v-else class="size-4" />
                                Simpan TTV
                            </Button>
                        </DialogFooter>
                    </template>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>
