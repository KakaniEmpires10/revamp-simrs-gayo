<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { Search, UserPlus, UserRoundX, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { search as searchPatients } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { PatientSearchMode, RegistrationPatient } from '@/types';

const props = defineProps<{
    mode: PatientSearchMode;
    createHref: string;
}>();

const emit = defineEmits<{
    select: [patient: RegistrationPatient];
}>();

const keyword = ref('');
const patients = ref<RegistrationPatient[]>([]);
const loading = ref(false);
const searched = ref(false);
let abortController: AbortController | undefined;
let searchSequence = 0;

const placeholder = computed(() => {
    if (props.mode === 'nik') {
        return 'Masukkan NIK pasien';
    }

    if (props.mode === 'no_peserta') {
        return 'Masukkan nomor peserta BPJS';
    }

    return 'Masukkan nomor rekam medis';
});

watch(
    () => props.mode,
    () => {
        keyword.value = '';
        patients.value = [];
        searched.value = false;
    },
);

watch(keyword, (value) => {
    searchSequence += 1;
    abortController?.abort();

    if (value.trim().length < 2) {
        patients.value = [];
        searched.value = false;
        loading.value = false;

        return;
    }
});

watchDebounced(keyword, (value) => {
    const query = value.trim();

    if (query.length < 2) {
        return;
    }

    void loadPatients(query);
}, {
    debounce: 300,
    maxWait: 1200,
});

async function loadPatients(query: string): Promise<void> {
    const currentSequence = ++searchSequence;
    loading.value = true;
    searched.value = false;
    abortController = new AbortController();

    try {
        const response = await fetch(searchPatients.url({ query: { mode: props.mode, query } }), {
            signal: abortController.signal,
        });

        if (!response.ok) {
            if (currentSequence === searchSequence) {
                patients.value = [];
            }

            return;
        }

        const payload = await response.json() as { data: RegistrationPatient[] };

        if (currentSequence === searchSequence) {
            patients.value = payload.data;
        }
    } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
            if (currentSequence === searchSequence) {
                patients.value = [];
            }
        }
    } finally {
        if (currentSequence === searchSequence) {
            loading.value = false;
            searched.value = true;
        }
    }
}

function selectPatient(patient: RegistrationPatient): void {
    emit('select', patient);
}

function clearSearch(): void {
    keyword.value = '';
    patients.value = [];
    searched.value = false;
    loading.value = false;
    searchSequence += 1;
    abortController?.abort();
}

defineExpose({
    clearSearch,
});
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Search class="text-muted-foreground pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2" />
            <Input
                v-model="keyword"
                class="h-14 rounded-lg pl-12 pr-24 text-base"
                :placeholder="placeholder"
                autocomplete="off"
                inputmode="numeric"
            />
            <div class="absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-1.5">
                <Spinner v-if="loading" />
                <Button
                    v-if="keyword"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Bersihkan pencarian pasien"
                    @click="clearSearch"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <div
            v-if="patients.length || (searched && keyword.trim().length >= 2 && !loading)"
            class="border-border scrollbar-dropdown divide-y bg-background absolute z-20 mt-2 max-h-85 w-full overflow-auto rounded-lg border shadow-lg"
        >
            <button
                v-for="patient in patients"
                :key="patient.no_rkm_medis"
                type="button"
                class="grid w-full gap-1 px-4 py-3 text-left outline-none"
                :class="[
                    patient.jk === 'L' ? 'hover:bg-blue-50 focus:bg-blue-100 dark:hover:bg-blue-600/10 dark:focus:bg-blue-700' : 'hover:bg-pink-50 focus:bg-pink-100 dark:hover:bg-pink-600/10 dark:focus:bg-pink-700/10',
                ]"
                @click="selectPatient(patient)"
            >
                <div class="flex items-end justify-between gap-3">
                    <span class="font-bold">{{ patient.nm_pasien }}</span>
                    <span class="text-muted-foreground text-xs font-semibold font-mono">{{ patient.no_rkm_medis }}</span>
                </div>
                <div class="text-muted-foreground flex flex-wrap gap-x-2 gap-y-1 text-xs">
                    <Badge size="xs" variant="soft-indigo" class="font-medium font-mono">NIK: {{ patient.no_ktp || '-' }}</Badge>
                    <Badge size="xs" variant="soft-success" class="font-medium font-mono">Peserta: {{ patient.no_peserta || '-' }}</Badge>
                    <Badge size="xs" :variant="patient.jk === 'L' ? 'soft-info' : 'soft-destructive'"><span class="font-semibold">{{ patient.jk }}</span> / {{ patient.tgl_lahir || '-' }}</Badge>
                </div>
            </button>

            <div v-if="!patients.length" class="grid place-items-center gap-3 px-6 py-8 text-center">
                <div class="bg-muted grid size-12 place-items-center rounded-full">
                    <UserRoundX class="text-muted-foreground size-6" />
                </div>
                <div class="grid gap-1">
                    <p class="font-medium">Pasien tidak ditemukan atau tidak sesuai.</p>
                    <p class="text-muted-foreground text-sm">Periksa kembali kata kunci pencarian pasien.</p>
                </div>
                <Button as-child>
                    <Link :href="createHref">
                        <UserPlus class="size-4" />
                        Tambah Pasien Baru
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
