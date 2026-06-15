<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import type { TwoFactorConfigContent } from '@/types';

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Kode pemulihan',
            description:
                'Konfirmasi akses akun dengan memasukkan salah satu kode pemulihan darurat Anda.',
            buttonText: 'masuk menggunakan kode autentikasi',
        };
    }

    return {
        title: 'Kode autentikasi',
        description:
            'Masukkan kode autentikasi dari aplikasi autentikator Anda.',
        buttonText: 'masuk menggunakan kode pemulihan',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <Head title="Autentikasi dua faktor" />

    <div class="space-y-6">
        <p class="text-center text-sm text-muted-foreground">
            Autentikasi dua faktor dinonaktifkan untuk login SIMRS lama.
        </p>
    </div>
</template>
