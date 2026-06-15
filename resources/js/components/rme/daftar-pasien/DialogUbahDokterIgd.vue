<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Check, ChevronsUpDown, Save, Stethoscope } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { reference } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { updateDokterIgd } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { cn } from '@/lib/utils';
import type { PasienRalanRme, RegistrationOption } from '@/types';

const props = defineProps<{
    patient: PasienRalanRme | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    kd_dokter: '',
    processing: false,
    errors: {} as Record<string, string>,
});

const doctorOpen = ref(false);
const doctorOptions = ref<RegistrationOption[]>([]);
const doctorLoading = ref(false);
const doctorError = ref('');

const selectedDoctorLabel = computed(() => {
    return doctorOptions.value.find((doctor) => doctor.value === form.kd_dokter)?.label || props.patient?.nm_dokter || 'Pilih dokter IGD';
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.kd_dokter = props.patient.kd_dokter;
        form.errors = {};
        doctorOptions.value = [];
        doctorError.value = '';
        void loadDoctors();
    },
    { immediate: true },
);

async function loadDoctors(query = ''): Promise<void> {
    doctorLoading.value = true;
    doctorError.value = '';

    try {
        const response = await fetch(reference.url({ query: { type: 'doctor', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat data dokter.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        doctorOptions.value = payload.data ?? [];
    } catch {
        doctorError.value = 'Gagal memuat data dokter IGD.';
        doctorOptions.value = [];
    } finally {
        doctorLoading.value = false;
    }
}

const cariDokter = useDebounceFn((query: string) => {
    void loadDoctors(query);
}, 300);

function pilihDokter(option: RegistrationOption): void {
    form.kd_dokter = option.value;
    doctorOpen.value = false;
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.patch(updateDokterIgd.url(), {
        no_rawat: props.patient.no_rawat,
        kd_dokter: form.kd_dokter,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: feedbackOnly(['patients', 'filters']),
        reset: ['patients'],
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
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Ganti Dokter IGD</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-sky-500/8 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="soft-primary" size="sm" class="font-mono">
                            RM {{ patient.no_rkm_medis }}
                        </Badge>
                        <Badge variant="soft-indigo" size="sm">
                            {{ patient.png_jawab }}
                        </Badge>
                        <Badge variant="muted" size="sm">
                            {{ patient.nm_poli }}
                        </Badge>
                    </div>
                    <p class="mt-2 text-lg font-semibold leading-tight">{{ patient.nm_pasien }}</p>
                </div>

                <div class="grid gap-2">
                    <Label>Dokter IGD</Label>
                    <Popover v-model:open="doctorOpen">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                role="combobox"
                                :aria-expanded="doctorOpen"
                                class="w-full justify-between"
                            >
                                <span class="truncate">{{ selectedDoctorLabel }}</span>
                                <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0" align="start">
                            <Command>
                                <CommandInput
                                    placeholder="Cari dokter IGD..."
                                    @update:model-value="(value) => cariDokter(String(value))"
                                />
                                <CommandList>
                                    <div v-if="doctorLoading" class="flex items-center gap-2 px-3 py-3 text-sm text-muted-foreground">
                                        <Spinner class="size-4" />
                                        Memuat dokter...
                                    </div>
                                    <CommandEmpty v-else>{{ doctorError || 'Dokter tidak ditemukan.' }}</CommandEmpty>
                                    <CommandGroup v-if="doctorOptions.length">
                                        <CommandItem
                                            v-for="doctor in doctorOptions"
                                            :key="doctor.value"
                                            :value="doctor.label"
                                            @select="pilihDokter(doctor)"
                                        >
                                            <Check
                                                class="mr-2 size-4"
                                                :class="cn(form.kd_dokter === doctor.value ? 'opacity-100' : 'opacity-0')"
                                            />
                                            <Stethoscope class="mr-2 size-4 text-muted-foreground" />
                                            {{ doctor.label }}
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.kd_dokter" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.kd_dokter">
                        <Spinner v-if="form.processing" class="size-4" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
