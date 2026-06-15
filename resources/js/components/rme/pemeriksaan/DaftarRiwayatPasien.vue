<script setup lang="ts">
import { Activity, ClipboardList, FlaskConical, Pill, Radio, ReceiptText, Stethoscope } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import type { RiwayatPasienKunjungan } from '@/types';

const props = defineProps<{
    histories: RiwayatPasienKunjungan[];
}>();

const orderedHistories = computed(() => props.histories);

function layananLabel(history: RiwayatPasienKunjungan): string {
    if (history.status_lanjut === 'Ranap') {
        return 'Rawat Inap';
    }

    if (history.kd_poli === 'IGDK') {
        return 'IGD';
    }

    return 'Rawat Jalan';
}

function ringkasanItems(history: RiwayatPasienKunjungan) {
    return [
        {
            key: 'cppt',
            label: `CPPT ${history.jumlah_cppt}`,
            icon: ClipboardList,
            active: history.jumlah_cppt > 0,
        },
        {
            key: 'lab',
            label: `Lab ${history.jumlah_lab}`,
            icon: FlaskConical,
            active: history.jumlah_lab > 0,
        },
        {
            key: 'radiologi',
            label: `Radiologi ${history.jumlah_radiologi}`,
            icon: Radio,
            active: history.jumlah_radiologi > 0,
        },
        {
            key: 'resep',
            label: `Resep ${history.jumlah_resep}`,
            icon: Pill,
            active: history.jumlah_resep > 0,
        },
    ];
}
</script>

<template>
    <section class="grid gap-3">
        <article
            v-for="history in orderedHistories"
            :key="history.no_rawat"
            class="rounded-lg border bg-background p-4"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="soft-primary" rounded="md">{{ layananLabel(history) }}</Badge>
                        <Badge variant="soft-secondary" rounded="md">{{ history.png_jawab }}</Badge>
                        <Badge variant="outline" rounded="md">{{ history.stts }}</Badge>
                    </div>

                    <div class="space-y-1">
                        <p class="text-base font-semibold">{{ history.tgl_registrasi }} {{ history.jam_reg }}</p>
                        <p class="text-sm text-muted-foreground">{{ history.no_rawat }} · No. Reg {{ history.no_reg }}</p>
                    </div>
                </div>

                <div class="grid gap-1 text-sm lg:text-right">
                    <span class="inline-flex items-center gap-1 font-medium lg:justify-end">
                        <Stethoscope class="size-4 text-muted-foreground" />
                        {{ history.nm_dokter }}
                    </span>
                    <span class="text-muted-foreground">{{ history.nm_poli }}</span>
                </div>
            </div>

            <Separator class="my-4" />

            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(280px,auto)] lg:items-start">
                <div class="grid gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-sm font-medium">
                            <ReceiptText class="size-4 text-muted-foreground" />
                            SEP
                        </span>
                        <Badge v-if="history.no_sep" variant="soft-success" rounded="md">{{ history.no_sep }}</Badge>
                        <Badge v-else variant="soft-warning" rounded="md">Belum ada SEP</Badge>
                    </div>

                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ history.diagnosa_sep || history.diagnosa_pasien || 'Diagnosa belum tercatat pada kunjungan ini.' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <Badge
                        v-for="item in ringkasanItems(history)"
                        :key="item.key"
                        :variant="item.active ? 'soft-success' : 'soft-secondary'"
                        rounded="md"
                        class="gap-1"
                    >
                        <component :is="item.icon" class="size-3.5" />
                        {{ item.label }}
                    </Badge>
                </div>
            </div>
        </article>

        <div v-if="!histories.length" class="grid min-h-[240px] place-items-center rounded-lg border border-dashed bg-muted/20 p-8 text-center">
            <div class="space-y-2">
                <Activity class="mx-auto size-9 text-muted-foreground" />
                <p class="font-medium">Riwayat pasien belum tersedia</p>
                <p class="text-sm text-muted-foreground">Belum ada kunjungan terdahulu yang dapat ditampilkan.</p>
            </div>
        </div>
    </section>
</template>
