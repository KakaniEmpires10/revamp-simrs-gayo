<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Pendaftaran akun',
        description: 'Akun pengguna SIMRS dikelola melalui sistem lama',
    },
});
</script>

<template>
    <Head title="Pendaftaran akun" />

    <div class="flex flex-col gap-6">
        <div class="text-center text-sm text-muted-foreground">
            Akun pengguna dikelola melalui sistem SIMRS lama.
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Kembali masuk</TextLink
            >
        </div>
    </div>
</template>
