<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Check, Save, Search } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { reference } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { updateDiagnosaIgd } from '@/actions/App/Http/Controllers/Rme/DaftarPasienRmeController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverAnchor, PopoverContent } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly, isFeedbackSuccess } from '@/composables/useFeedback';
import { cn } from '@/lib/utils';
import type { PasienRalanRme, RegistrationOption } from '@/types';

const props = defineProps<{
    patient: PasienRalanRme | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = reactive({
    kd_penyakit: '',
    diagnosa: '',
    processing: false,
    errors: {} as Record<string, string>,
});

const diagnosisOpen = ref(false);
const diagnosisLoading = ref(false);
const diagnosisError = ref('');
const diagnosisOptions = ref<RegistrationOption[]>([]);
const inputRef = ref<InstanceType<typeof Input> | null>(null);

const selectedDiagnosisLabel = computed(() => {
    if (!form.kd_penyakit) {
        return '';
    }

    return `${form.kd_penyakit} - ${form.diagnosa}`;
});

watch(
    () => [open.value, props.patient],
    () => {
        if (!open.value || !props.patient) {
            return;
        }

        form.kd_penyakit = props.patient.kd_penyakit_igd || '';
        form.diagnosa = props.patient.nm_penyakit_igd || '';
        form.errors = {};
        diagnosisOptions.value = [];
        diagnosisError.value = '';
        diagnosisOpen.value = false;
    },
    { immediate: true },
);

async function loadDiagnoses(query: string): Promise<void> {
    if (query.trim().length < 2) {
        diagnosisOptions.value = [];
        diagnosisError.value = '';
        diagnosisOpen.value = false;

        return;
    }

    diagnosisLoading.value = true;
    diagnosisError.value = '';
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
        diagnosisOpen.value = true;
    } catch {
        diagnosisError.value = 'Gagal memuat referensi diagnosa.';
        diagnosisOptions.value = [];
        diagnosisOpen.value = true;
    } finally {
        diagnosisLoading.value = false;
    }
}

const cariDiagnosa = useDebounceFn((query: string) => {
    void loadDiagnoses(query);
}, 300);

function ubahDiagnosa(value: string | number): void {
    form.diagnosa = String(value);
    form.kd_penyakit = '';
    cariDiagnosa(form.diagnosa);
}

function pilihDiagnosa(diagnosis: RegistrationOption): void {
    form.kd_penyakit = diagnosis.value;
    form.diagnosa = diagnosis.description || diagnosis.label;
    diagnosisOpen.value = false;
    inputRef.value?.$el?.focus?.();
}

function submit(): void {
    if (!props.patient) {
        return;
    }

    form.processing = true;
    form.errors = {};

    router.patch(updateDiagnosaIgd.url(), {
        no_rawat: props.patient.no_rawat,
        kd_penyakit: form.kd_penyakit,
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
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Isi Diagnosa IGD</DialogTitle>
                <DialogDescription v-if="patient">
                    {{ patient.no_rawat }} - {{ patient.nm_pasien }}
                </DialogDescription>
            </DialogHeader>

            <form v-if="patient" class="grid gap-5" @submit.prevent="submit">
                <div class="rounded-lg border border-warning/30 bg-linear-to-br from-warning/10 via-card to-primary/8 p-4">
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
                    <p v-if="selectedDiagnosisLabel" class="mt-1 text-sm text-muted-foreground">
                        Diagnosa saat ini: {{ selectedDiagnosisLabel }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>Diagnosa ICD10</Label>
                    <Popover v-model:open="diagnosisOpen">
                        <PopoverAnchor as-child>
                            <div class="relative">
                                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    ref="inputRef"
                                    :model-value="form.diagnosa"
                                    class="pl-9"
                                    placeholder="Ketik minimal 2 karakter, mis. A09 atau demam"
                                    @update:model-value="ubahDiagnosa"
                                    @focus="diagnosisOpen = diagnosisOptions.length > 0 || diagnosisLoading || Boolean(diagnosisError)"
                                />
                            </div>
                        </PopoverAnchor>
                        <PopoverContent
                            class="w-[var(--reka-popover-trigger-width)] p-0"
                            align="start"
                            @open-auto-focus.prevent
                        >
                            <Command>
                                <CommandList>
                                    <div v-if="diagnosisLoading" class="flex items-center gap-2 px-3 py-3 text-sm text-muted-foreground">
                                        <Spinner class="size-4" />
                                        Memuat diagnosa...
                                    </div>
                                    <CommandEmpty v-else>{{ diagnosisError || 'Diagnosa tidak ditemukan.' }}</CommandEmpty>
                                    <CommandGroup v-if="diagnosisOptions.length">
                                        <CommandItem
                                            v-for="diagnosis in diagnosisOptions"
                                            :key="diagnosis.value"
                                            :value="diagnosis.label"
                                            @select="pilihDiagnosa(diagnosis)"
                                        >
                                            <Check
                                                class="mr-2 size-4"
                                                :class="cn(form.kd_penyakit === diagnosis.value ? 'opacity-100' : 'opacity-0')"
                                            />
                                            <div class="min-w-0">
                                                <p class="truncate font-medium">{{ diagnosis.label }}</p>
                                                <p v-if="diagnosis.description" class="truncate text-xs text-muted-foreground">
                                                    {{ diagnosis.description }}
                                                </p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.kd_penyakit" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="open = false">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.kd_penyakit">
                        <Spinner v-if="form.processing" class="size-4" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
