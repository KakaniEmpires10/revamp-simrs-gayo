<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Atur ulang kata sandi',
        description: 'Pemulihan akun SIMRS dikelola melalui sistem lama',
    },
});

defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Atur ulang kata sandi" />

    <p class="text-center text-sm text-muted-foreground">
        Pengaturan ulang kata sandi dikelola melalui sistem SIMRS lama.
    </p>
</template>
