<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Save, Search, Stethoscope } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { updateDpjpRanap } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import type { PasienRanapRme, RmeOption } from '@/types';

const props = defineProps<{
    patient: PasienRanapRme | null;
    doctors: RmeOption[];
}>();

const open = defineModel<boolean>('open', { required: true });

const search = ref('');
const form = reactive({
    kode_dokter: [] as string[],
    processing: false,
    errors: {} as Record<string, string>,
});

const filteredDoctors = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.doctors;
    }

    return props.doctors.filter((doctor) => doctor.label.toLowerCase().includes(query) || doctor.value.toLowerCase().includes(query));
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.kode_dokter = daftarDpjp(props.patient.dokter_pj_ranap).map((doctor) => doctor.value);
        form.errors = {};
        search.value = '';
    },
    { immediate: true },
);

function daftarDpjp(value: string | null): RmeOption[] {
    if (!value) {
        return [];
    }

    return value.split('||').map((item) => {
        const [kode, nama] = item.split('::');

        return {
            value: kode,
            label: nama || kode,
        };
    });
}

function toggleDokter(kodeDokter: string, checked: boolean | 'indeterminate'): void {
    const selected = new Set(form.kode_dokter);

    if (checked === true) {
        selected.add(kodeDokter);
    } else {
        selected.delete(kodeDokter);
    }

    form.kode_dokter = Array.from(selected);
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.patch(updateDpjpRanap.url(), {
        no_rawat: props.patient.no_rawat,
        kode_dokter: form.kode_dokter,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['patients', 'filters']),
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
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit Dokter PJ Ranap</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-emerald-500/8 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="muted" size="sm" class="font-mono">RM {{ patient.no_rkm_medis }}</Badge>
                        <Badge variant="soft-primary" size="sm">{{ patient.nm_bangsal }}</Badge>
                        <Badge variant="soft-indigo" size="sm">{{ patient.kd_kamar }}</Badge>
                    </div>
                    <p class="mt-2 text-lg font-semibold leading-tight">{{ patient.nm_pasien }}</p>
                </div>

                <div class="grid gap-2">
                    <Label>Dokter Penanggung Jawab</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" class="pl-9" placeholder="Cari nama atau kode dokter..." />
                    </div>
                    <div class="max-h-72 overflow-y-auto rounded-md border">
                        <label
                            v-for="doctor in filteredDoctors"
                            :key="doctor.value"
                            class="flex cursor-pointer items-center gap-3 border-b px-3 py-2.5 last:border-b-0 hover:bg-muted/50"
                        >
                            <Checkbox
                                :model-value="form.kode_dokter.includes(doctor.value)"
                                @update:model-value="toggleDokter(doctor.value, $event)"
                            />
                            <Stethoscope class="size-4 text-muted-foreground" />
                            <span class="text-sm font-medium">{{ doctor.label }}</span>
                        </label>
                        <div v-if="!filteredDoctors.length" class="px-3 py-6 text-center text-sm text-muted-foreground">
                            Dokter tidak ditemukan.
                        </div>
                    </div>
                    <InputError :message="form.errors.kode_dokter || form.errors['kode_dokter.0']" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.kode_dokter.length">
                        <Spinner v-if="form.processing" class="size-4" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
