<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/ManajemenPegawai/JadwalPraktekController';
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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useFeedback } from '@/composables/useFeedback';
import { cn } from '@/lib/utils';
import type { ClinicOption, PracticeSchedule } from '@/types';

const props = defineProps<{
    schedule: PracticeSchedule;
    clinics: ClinicOption[];
}>();

const open = ref(false);
const processing = ref(false);
const selectedClinic = ref<ClinicOption | null>(null);
const feedback = useFeedback();

const propClinic = computed(() => props.clinics.find((clinic) => clinic.kd_poli === props.schedule.kd_poli));
const activeClinic = computed(() => selectedClinic.value ?? propClinic.value);

watch(
    () => props.schedule.kd_poli,
    () => {
        selectedClinic.value = null;
    },
);

function normalizeTime(value: string | null): string {
    return (value ?? '').slice(0, 5);
}

function changeClinic(clinic: ClinicOption): void {
    const previousClinic = activeClinic.value;

    if (processing.value || clinic.kd_poli === activeClinic.value?.kd_poli) {
        open.value = false;

        return;
    }

    selectedClinic.value = clinic;
    processing.value = true;
    open.value = false;

    router.patch(
        update.url({
            doctor: props.schedule.kd_dokter,
            day: props.schedule.hari_kerja,
            start: normalizeTime(props.schedule.jam_mulai),
        }),
        {
            kd_dokter: props.schedule.kd_dokter,
            hari_kerja: props.schedule.hari_kerja,
            jam_mulai: normalizeTime(props.schedule.jam_mulai),
            jam_selesai: normalizeTime(props.schedule.jam_selesai) || null,
            kd_poli: clinic.kd_poli,
            kuota: props.schedule.kuota,
        },
        {
            only: ['schedules'],
            preserveScroll: true,
            onSuccess: () => {
                feedback.success(`Poli jadwal ${props.schedule.nm_dokter} berhasil diubah ke ${clinic.nm_poli}.`);
            },
            onError: () => {
                selectedClinic.value = previousClinic ?? null;
                feedback.error('Poli jadwal gagal diubah. Periksa data jadwal lalu coba lagi.');
            },
            onNetworkError: () => {
                selectedClinic.value = previousClinic ?? null;
                feedback.error('Koneksi bermasalah. Poli jadwal belum berhasil diubah.');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                class="h-auto min-h-10 w-full justify-between rounded-lg px-3 py-2 text-left"
                :disabled="processing"
            >
                <span class="min-w-0">
                    <span class="block truncate font-medium">{{ activeClinic?.nm_poli ?? schedule.nm_poli ?? '-' }}</span>
                    <span class="block font-mono text-xs text-muted-foreground">{{ activeClinic?.kd_poli ?? schedule.kd_poli ?? '-' }}</span>
                </span>
                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-0" align="start">
            <Command>
                <CommandInput placeholder="Cari poli..." />
                <CommandList>
                    <CommandEmpty>Poli tidak ditemukan.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="clinic in clinics"
                            :key="clinic.kd_poli"
                            :value="`${clinic.kd_poli} ${clinic.nm_poli}`"
                            @select="changeClinic(clinic)"
                        >
                            <Check :class="cn('size-4', clinic.kd_poli === activeClinic?.kd_poli ? 'opacity-100' : 'opacity-0')" />
                            <span class="min-w-0">
                                <span class="block truncate">{{ clinic.nm_poli }}</span>
                                <span class="block font-mono text-xs text-muted-foreground">{{ clinic.kd_poli }}</span>
                            </span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
