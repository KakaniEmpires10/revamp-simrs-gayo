<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DaftarRiwayatPasien from '@/components/rme/pemeriksaan/DaftarRiwayatPasien.vue';
import KonteksPasienPemeriksaan from '@/components/rme/pemeriksaan/KonteksPasienPemeriksaan.vue';
import { riwayat as riwayatRoute } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import type { KonteksPasienPemeriksaan as PatientContext, RiwayatPasienKunjungan } from '@/types';

defineProps<{
    patient: PatientContext;
    limit: number | null;
    histories: RiwayatPasienKunjungan[];
}>();

defineOptions({
    layout: {
        forceSidebarCollapsed: true,
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Riwayat Pasien', href: riwayatRoute().url },
        ],
    },
});
</script>

<template>
    <Head :title="`Riwayat - ${patient.nm_pasien}`" />

    <div class="contents">
        <KonteksPasienPemeriksaan :patient="patient" />

        <div class="grid gap-4">
            <div class="space-y-1">
                <h1 class="text-xl font-semibold">
                    {{ limit === 3 ? '3 Riwayat Kunjungan Terakhir' : 'Seluruh Riwayat Kunjungan' }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Riwayat dibuka di tab terpisah agar form pemeriksaan tetap bisa digunakan.
                </p>
            </div>

            <DaftarRiwayatPasien :histories="histories" />
        </div>
    </div>
</template>
