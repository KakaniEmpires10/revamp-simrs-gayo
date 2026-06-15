<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Lupa kata sandi',
        description: 'Pemulihan akun SIMRS dikelola melalui sistem lama',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa kata sandi" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span
                >Pengaturan ulang kata sandi dikelola melalui sistem SIMRS
                lama.</span
            >
            <TextLink :href="login()">Kembali masuk</TextLink>
        </div>
    </div>
</template>
