<script setup lang="ts">
import { ref, watch } from 'vue';
import { wilayahSearch as wilayahSearchDataPasien } from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import { AppAsyncCombobox } from '@/components/ui/form';
import type { AppAsyncComboboxOption } from '@/components/ui/form/types';

type JenisWilayah = 'kelurahan' | 'kecamatan' | 'kabupaten' | 'propinsi';

const props = withDefaults(defineProps<{
    jenis: JenisWilayah;
    placeholder: string;
    searchPlaceholder: string;
    minSearchLength?: number;
    disabled?: boolean;
}>(), {
    minSearchLength: 1,
    disabled: false,
});

const modelValue = defineModel<string>({ default: '' });

const options = ref<AppAsyncComboboxOption[]>([]);
const displayValue = ref('');
const searchTerm = ref('');
const loading = ref(false);
const error = ref('');
const selectedValue = ref('');

function titleCase(jenis: JenisWilayah): string {
    switch (jenis) {
        case 'kelurahan':
            return 'Kelurahan';
        case 'kecamatan':
            return 'Kecamatan';
        case 'kabupaten':
            return 'Kabupaten';
        case 'propinsi':
            return 'Propinsi';
        default:
            return 'Wilayah';
    }
}

async function ambilWilayah(keyword: string): Promise<void> {
    const trimmed = keyword.trim();

    if (trimmed.length < props.minSearchLength) {
        options.value = [];
        error.value = '';

        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const response = await fetch(wilayahSearchDataPasien.url({
            query: {
                jenis: props.jenis,
                query: trimmed,
            },
        }), {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json() as { data: AppAsyncComboboxOption[] };

        if (!response.ok) {
            throw new Error('Gagal memuat data wilayah.');
        }

        options.value = payload.data;
    } catch {
        options.value = [];
        error.value = `Data ${titleCase(props.jenis).toLowerCase()} gagal dimuat.`;
    } finally {
        loading.value = false;
    }
}

async function muatLabelTerpilih(value: string): Promise<void> {
    const trimmed = value.trim();

    if (!trimmed) {
        displayValue.value = '';
        options.value = [];

        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const response = await fetch(wilayahSearchDataPasien.url({
            query: {
                jenis: props.jenis,
                query: trimmed,
            },
        }), {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json() as { data: AppAsyncComboboxOption[] };

        if (!response.ok) {
            throw new Error('Gagal memuat data wilayah terpilih.');
        }

        options.value = payload.data;

        const exact = payload.data.find((option) => option.value === trimmed);
        displayValue.value = exact?.label ?? payload.data[0]?.label ?? '';
        selectedValue.value = exact?.value ?? trimmed;
    } catch {
        displayValue.value = '';
        options.value = [];
        error.value = `Data ${titleCase(props.jenis).toLowerCase()} gagal dimuat.`;
    } finally {
        loading.value = false;
    }
}

function pilihWilayah(option: AppAsyncComboboxOption): void {
    selectedValue.value = option.value;
    displayValue.value = option.label;
}

watch(modelValue, (value) => {
    if (!value) {
        displayValue.value = '';
        selectedValue.value = '';
        options.value = [];

        return;
    }

    if (value === selectedValue.value && displayValue.value) {
        return;
    }

    void muatLabelTerpilih(value);
}, { immediate: true });
</script>

<template>
    <AppAsyncCombobox
        v-model="modelValue"
        v-model:search="searchTerm"
        :options="options"
        :display-value="displayValue"
        :placeholder="placeholder"
        :search-placeholder="searchPlaceholder"
        :loading="loading"
        :error="error"
        :disabled="disabled"
        :min-search-length="minSearchLength"
        prompt-title="Cari data wilayah"
        :prompt-description="`Ketik minimal ${minSearchLength} karakter untuk mencari data wilayah.`"
        empty-title="Data tidak ditemukan"
        empty-description="Coba gunakan kata kunci lain."
        @search="ambilWilayah"
        @select="pilihWilayah"
    />
</template>
