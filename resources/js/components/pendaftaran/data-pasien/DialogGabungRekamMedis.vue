<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { GitMerge, TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    mergeMedicalRecord,
    search as cariDataPasien,
} from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AppAsyncCombobox } from '@/components/ui/form';
import type { AppAsyncComboboxOption } from '@/components/ui/form/types';
import { Spinner } from '@/components/ui/spinner';
import { feedbackOnly } from '@/composables/useFeedback';
import type { RegistrationPatient } from '@/types';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    patient: RegistrationPatient | null;
}>();

const noRmLama = ref('');
const noRmBaru = ref('');
const noRmLamaLabel = ref('');
const noRmBaruLabel = ref('');
const searchLama = ref('');
const searchBaru = ref('');
const optionsLama = ref<AppAsyncComboboxOption[]>([]);
const optionsBaru = ref<AppAsyncComboboxOption[]>([]);
const loadingLama = ref(false);
const loadingBaru = ref(false);
const errorLama = ref('');
const errorBaru = ref('');
const processing = ref(false);

const canSubmit = computed(() => noRmLama.value !== '' && noRmBaru.value !== '' && noRmLama.value !== noRmBaru.value);

watch(
    () => [open.value, props.patient] as const,
    ([isOpen, patient]) => {
        if (!isOpen) {
            return;
        }

        noRmLama.value = patient?.no_rkm_medis ?? '';
        noRmLamaLabel.value = patient ? `${patient.no_rkm_medis} - ${patient.nm_pasien}` : '';
        noRmBaru.value = '';
        noRmBaruLabel.value = '';
        searchLama.value = '';
        searchBaru.value = '';
        optionsLama.value = [];
        optionsBaru.value = [];
        errorLama.value = '';
        errorBaru.value = '';
    },
    { immediate: true },
);

async function cariPasien(keyword: string, target: 'lama' | 'baru'): Promise<void> {
    const normalizedKeyword = keyword.trim();

    if (normalizedKeyword.length < 2) {
        return;
    }

    if (target === 'lama') {
        loadingLama.value = true;
        errorLama.value = '';
    } else {
        loadingBaru.value = true;
        errorBaru.value = '';
    }

    try {
        const response = await fetch(cariDataPasien.url({ query: { query: normalizedKeyword } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Pencarian pasien gagal dimuat.');
        }

        const payload = await response.json() as { data: AppAsyncComboboxOption[] };

        if (target === 'lama') {
            optionsLama.value = payload.data;
        } else {
            optionsBaru.value = payload.data;
        }
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Pencarian pasien gagal dimuat.';

        if (target === 'lama') {
            errorLama.value = message;
        } else {
            errorBaru.value = message;
        }
    } finally {
        loadingLama.value = false;
        loadingBaru.value = false;
    }
}

function pilihLama(option: AppAsyncComboboxOption): void {
    noRmLamaLabel.value = option.label;
}

function pilihBaru(option: AppAsyncComboboxOption): void {
    noRmBaruLabel.value = option.label;
}

function submit(): void {
    if (!canSubmit.value || processing.value) {
        return;
    }

    processing.value = true;

    router.post(
        mergeMedicalRecord.url(),
        {
            no_rm_lama: noRmLama.value,
            no_rm_baru: noRmBaru.value,
        },
        {
            preserveScroll: true,
            only: feedbackOnly(['patients', 'filters']),
            onSuccess: () => {
                open.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Gabung RM</DialogTitle>
                <DialogDescription>
                    Pindahkan riwayat pendaftaran dari RM lama ke RM baru, lalu hapus RM lama.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <Alert variant="soft-warning" size="sm">
                    <TriangleAlert class="size-4" />
                    <AlertTitle>Periksa data sebelum menyimpan</AlertTitle>
                    <AlertDescription>
                        Proses ini digunakan untuk duplikasi pasien. Pastikan RM baru adalah data pasien yang benar.
                    </AlertDescription>
                </Alert>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2">
                        <span class="text-sm font-medium">No RM Lama</span>
                        <AppAsyncCombobox
                            v-model="noRmLama"
                            v-model:search="searchLama"
                            :options="optionsLama"
                            :display-value="noRmLamaLabel"
                            :loading="loadingLama"
                            :error="errorLama"
                            placeholder="Pilih RM lama"
                            search-placeholder="Cari RM lama"
                            :min-search-length="2"
                            prompt-title="Cari pasien lama"
                            empty-title="Pasien tidak ditemukan"
                            @search="(keyword) => cariPasien(keyword, 'lama')"
                            @select="pilihLama"
                        />
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-medium">No RM Baru</span>
                        <AppAsyncCombobox
                            v-model="noRmBaru"
                            v-model:search="searchBaru"
                            :options="optionsBaru"
                            :display-value="noRmBaruLabel"
                            :loading="loadingBaru"
                            :error="errorBaru"
                            placeholder="Pilih RM baru"
                            search-placeholder="Cari RM baru"
                            :min-search-length="2"
                            prompt-title="Cari pasien aktif"
                            empty-title="Pasien tidak ditemukan"
                            @search="(keyword) => cariPasien(keyword, 'baru')"
                            @select="pilihBaru"
                        />
                    </label>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" :disabled="processing" @click="open = false">
                    Batal
                </Button>
                <Button type="button" :disabled="!canSubmit || processing" @click="submit">
                    <Spinner v-if="processing" />
                    <GitMerge v-else class="size-4" />
                    {{ processing ? 'Memproses...' : 'Gabungkan RM' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
