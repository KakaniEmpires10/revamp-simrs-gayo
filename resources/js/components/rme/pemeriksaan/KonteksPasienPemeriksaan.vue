<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, Bed, CalendarClock, CreditCard, Hash, Hospital, IdCard, Stethoscope, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ambilUrlListRme } from '@/composables/useRmeListNavigation';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import type { KonteksPasienPemeriksaan } from '@/types';

const props = defineProps<{
    patient: KonteksPasienPemeriksaan;
}>();

const kelasLabel = computed(() => {
    if (props.patient.status_lanjut === 'Ranap' && props.patient.kelas) {
        return `Kelas ${props.patient.kelas}`;
    }

    if (props.patient.kelas_sep) {
        return `Kelas SEP ${props.patient.kelas_sep}`;
    }

    return 'Kelas -';
});

function kembali(): void {
    router.visit(ambilUrlListRme(props.patient.asal), {
        preserveScroll: true,
    });
}
</script>

<template>
    <section class="sticky top-0 z-30 -mx-2 bg-background/95 px-2 py-2 backdrop-blur supports-[backdrop-filter]:bg-background/80">
        <div class="relative overflow-hidden rounded-lg border border-primary/20 bg-linear-to-br from-primary/8 via-card to-info/8 p-3 shadow-sm">
            <UserRound class="absolute -right-7 -bottom-9 size-28 rotate-[-10deg] text-primary/10" />

            <div class="relative flex min-w-0 flex-col gap-2">
                <div class="flex flex-col min-w-0 md:flex-row md:items-center gap-3">
                    <Button type="button" variant="soft-warning" size="icon" aria-label="Kembali ke daftar pasien" @click="kembali">
                        <ArrowLeft class="size-4" />
                    </Button>

                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <h1 class="truncate text-lg font-semibold leading-tight text-foreground">
                                {{ patient.nm_pasien }}
                            </h1>
                            <Badge variant="soft-info" size="sm" rounded="md">{{ labelJenisKelamin(patient.jk) }}</Badge>
                            <Badge variant="soft-secondary" size="sm" rounded="md">{{ patient.stts_daftar }}</Badge>
                            <Badge variant="soft-success" size="sm" rounded="md">
                                <CreditCard class="size-3" />
                                {{ patient.png_jawab }}
                            </Badge>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            <Badge variant="soft-info" size="sm" rounded="md" class="font-mono">
                                <IdCard class="size-3" />
                                No RM: {{ patient.no_rkm_medis }}
                            </Badge>
                            <Badge variant="soft-primary" size="sm" rounded="md" class="font-mono">
                                <Hash class="size-3" />
                                No Rawat: {{ patient.no_rawat }}
                            </Badge>
                            <Badge variant="soft-indigo" size="sm" rounded="md">
                                <Bed class="size-3" />
                                {{ kelasLabel }}
                            </Badge>
                            <Badge variant="soft-secondary" size="sm" rounded="md" class="max-w-full justify-start">
                            <Hospital class="size-3" />
                            <span class="truncate">{{ patient.status_lanjut === 'Ranap' && patient.nm_bangsal ?
                                patient.nm_bangsal : patient.nm_poli }}</span>
                        </Badge>
                        <Badge variant="soft-secondary" size="sm" rounded="md" class="max-w-full justify-start">
                            <Stethoscope class="size-3" />
                            <span class="truncate">{{ patient.nm_dokter }}</span>
                        </Badge>
                        <Badge variant="soft-secondary" size="sm" rounded="md">
                            <CalendarClock class="size-3" />
                            {{ patient.tgl_registrasi }} {{ patient.jam_reg }}
                        </Badge>
                        <Badge variant="soft-secondary" size="sm" rounded="md">
                            {{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}
                        </Badge>
                        <Badge :variant="patient.no_sep ? 'soft-success' : 'soft-warning'" size="sm" rounded="md"
                            class="max-w-full justify-start font-mono">
                            <BadgeCheck class="size-3" />
                            <span class="truncate">{{ patient.no_sep ? `SEP: ${patient.no_sep}` : 'SEP belum tersedia'
                                }}</span>
                        </Badge>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
