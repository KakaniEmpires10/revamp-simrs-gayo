<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import KontenPlaceholderPemeriksaan from '@/components/rme/pemeriksaan/KontenPlaceholderPemeriksaan.vue';
import KonteksPasienPemeriksaan from '@/components/rme/pemeriksaan/KonteksPasienPemeriksaan.vue';
import LayoutPemeriksaan from '@/components/rme/pemeriksaan/LayoutPemeriksaan.vue';
import TombolRiwayatPemeriksaan from '@/components/rme/pemeriksaan/TombolRiwayatPemeriksaan.vue';
import { cppt as cpptRoute } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import type { KonteksPasienPemeriksaan as PatientContext, PemeriksaanMenu } from '@/types';
import type { PemeriksaanNavigationMode } from '@/types/ui';

const props = defineProps<{
    patient: PatientContext;
    menus: PemeriksaanMenu[];
    activeMenu: string;
    navigationMode: PemeriksaanNavigationMode;
    riwayatUrls: {
        lastThree: string;
        all: string;
    };
}>();

const active = props.menus.find((menu) => menu.key === props.activeMenu);
const mode = ref<PemeriksaanNavigationMode>(props.navigationMode);

defineOptions({
    layout: {
        forceSidebarCollapsed: true,
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Pemeriksaan', href: cpptRoute().url },
        ],
    },
});
</script>

<template>
    <Head :title="`${active?.label ?? 'Pemeriksaan'} - ${patient.nm_pasien}`" />

    <div class="contents">
        <KonteksPasienPemeriksaan :patient="patient" />

        <LayoutPemeriksaan v-model:mode="mode" :menus="menus" :active-menu="activeMenu">
            <KontenPlaceholderPemeriksaan :menu="active" />
        </LayoutPemeriksaan>

        <TombolRiwayatPemeriksaan :urls="riwayatUrls" />
    </div>
</template>
