<script setup lang="ts">
import { computed } from 'vue';
import SearchCombobox from '@/components/manajemen-pegawai/ComboboxPencarian.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { AppSelect } from '@/components/ui/form';

const props = withDefaults(
    defineProps<{
        days: string[];
        doctors: { kd_dokter: string; nm_dokter: string }[];
        clinics: { kd_poli: string; nm_poli: string }[];
        loading?: boolean;
    }>(),
    {
        loading: false,
    },
);

defineEmits<{
    apply: [];
    clear: [];
}>();

const day = defineModel<string>('day', { required: true });
const doctor = defineModel<string>('doctor', { required: true });
const clinic = defineModel<string>('clinic', { required: true });

const dayOptions = computed(() => props.days.map((value) => ({ value, label: value })));
const doctorOptions = computed(() => props.doctors.map((value) => ({
    value: value.kd_dokter,
    label: value.nm_dokter,
})));
const clinicOptions = computed(() => props.clinics.map((value) => ({
    value: value.kd_poli,
    label: value.nm_poli,
})));
</script>

<template>
    <form class="grid gap-3 rounded-lg border bg-card p-4 shadow-sm md:grid-cols-[180px_minmax(220px,1fr)_minmax(220px,1fr)_auto] md:items-end" @submit.prevent="$emit('apply')">
        <label class="flex min-w-44 flex-col gap-2">
            <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Hari</span>
            <AppSelect v-model="day" class="h-10 rounded-lg bg-background" :options="dayOptions" placeholder="Semua Hari" />
        </label>
        <label class="flex min-w-0 flex-col gap-2">
            <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Dokter</span>
            <SearchCombobox
                v-model="doctor"
                :options="doctorOptions"
                placeholder="Semua Dokter"
                search-placeholder="Cari dokter..."
                empty-text="Dokter tidak ditemukan."
                all-label="Semua Dokter"
            />
        </label>
        <label class="flex min-w-0 flex-col gap-2">
            <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Poli</span>
            <SearchCombobox
                v-model="clinic"
                :options="clinicOptions"
                placeholder="Semua Poli"
                search-placeholder="Cari poli..."
                empty-text="Poli tidak ditemukan."
                all-label="Semua Poli"
            />
        </label>
        <TombolAksiFilter
            :processing="loading"
            submit
            @bersihkan="$emit('clear')"
        />
    </form>
</template>
