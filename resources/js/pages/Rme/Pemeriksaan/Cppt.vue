<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import FormCpptPemeriksaan from '@/components/rme/pemeriksaan/FormCpptPemeriksaan.vue';
import KonteksPasienPemeriksaan from '@/components/rme/pemeriksaan/KonteksPasienPemeriksaan.vue';
import LayoutPemeriksaan from '@/components/rme/pemeriksaan/LayoutPemeriksaan.vue';
import RiwayatCpptPemeriksaan from '@/components/rme/pemeriksaan/RiwayatCpptPemeriksaan.vue';
import TombolRiwayatPemeriksaan from '@/components/rme/pemeriksaan/TombolRiwayatPemeriksaan.vue';
import { cppt as cpptRoute } from '@/routes/rme/pemeriksaan';
import { index as rawatJalanIndex } from '@/routes/rme/rawat-jalan';
import type { DefaultFormCppt, FilterRiwayatCppt, KonteksPasienPemeriksaan as PatientContext, PemeriksaanMenu, RiwayatCppt, RmeOption } from '@/types';
import type { PemeriksaanNavigationMode } from '@/types/ui';

const props = defineProps<{
    patient: PatientContext;
    menus: PemeriksaanMenu[];
    activeMenu: string;
    navigationMode: PemeriksaanNavigationMode;
    formDefaults: DefaultFormCppt;
    canChooseOfficer: boolean;
    officerOptions: RmeOption[];
    cpptHistoryFilters: FilterRiwayatCppt;
    cpptHistoryLimit: number | null;
    cpptHistories: RiwayatCppt[];
    riwayatUrls: {
        lastThree: string;
        all: string;
    };
}>();

const mode = ref<PemeriksaanNavigationMode>(props.navigationMode);
const salinRiwayat = ref<RiwayatCppt | null>(null);
const editRiwayat = ref<RiwayatCppt | null>(null);

function salinCppt(history: RiwayatCppt): void {
    salinRiwayat.value = null;
    editRiwayat.value = null;

    nextTick(() => {
        salinRiwayat.value = history;
    });
}

function editCppt(history: RiwayatCppt): void {
    salinRiwayat.value = null;
    editRiwayat.value = null;

    nextTick(() => {
        editRiwayat.value = history;
    });
}

defineOptions({
    layout: {
        forceSidebarCollapsed: true,
        breadcrumbs: [
            { title: 'RME', href: rawatJalanIndex() },
            { title: 'Pemeriksaan', href: cpptRoute().url },
            { title: 'CPPT', href: cpptRoute().url },
        ],
    },
});
</script>

<template>
    <Head :title="`CPPT - ${patient.nm_pasien}`" />

    <div class="contents">
        <KonteksPasienPemeriksaan :patient="patient" />

        <LayoutPemeriksaan v-model:mode="mode" :menus="menus" :active-menu="activeMenu">
            <FormCpptPemeriksaan
                :patient="patient"
                :defaults="formDefaults"
                :can-choose-officer="canChooseOfficer"
                :officer-options="officerOptions"
                :salin-riwayat="salinRiwayat"
                :edit-riwayat="editRiwayat"
            />

            <RiwayatCpptPemeriksaan
                :patient="patient"
                :filters="cpptHistoryFilters"
                :limit="cpptHistoryLimit"
                :histories="cpptHistories"
                @salin="salinCppt"
                @edit="editCppt"
            />
        </LayoutPemeriksaan>

        <TombolRiwayatPemeriksaan :urls="riwayatUrls" />
    </div>
</template>
