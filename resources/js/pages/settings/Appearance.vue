<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import FeedbackModeToggle from '@/components/FeedbackModeToggle.vue';
import Heading from '@/components/Heading.vue';
import ToggleNavigasiPemeriksaan from '@/components/rme/pemeriksaan/ToggleNavigasiPemeriksaan.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pengaturan tampilan',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Pengaturan tampilan" />

    <h1 class="sr-only">Pengaturan tampilan</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Pengaturan tampilan"
            description="Sesuaikan tampilan aplikasi untuk akun Anda"
        />
        <AppearanceTabs />

        <Heading
            variant="small"
            title="Feedback aksi"
            description="Pilih bagaimana aplikasi menampilkan hasil tindakan Anda"
        />
        <FeedbackModeToggle />

        <Heading
            variant="small"
            title="Navigasi pemeriksaan"
            description="Pilih model navigasi halaman pemeriksaan pasien"
        />
        <ToggleNavigasiPemeriksaan />
    </div>
</template>
