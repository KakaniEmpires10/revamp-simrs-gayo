<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BpjsAntrolTaskIdToggle from '@/components/settings/ToggleTaskIdAntrolBpjs.vue';
import { edit } from '@/routes/integrasi';

defineProps<{
    bpjsAntrolTaskIdEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pengaturan integrasi',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Pengaturan integrasi" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Integrasi"
            description="Kelola perilaku integrasi layanan eksternal yang dapat memengaruhi workflow SIMRS."
        />

        <BpjsAntrolTaskIdToggle :enabled="bpjsAntrolTaskIdEnabled" />
    </div>
</template>
