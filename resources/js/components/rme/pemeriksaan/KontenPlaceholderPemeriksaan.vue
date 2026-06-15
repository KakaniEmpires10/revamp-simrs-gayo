<script setup lang="ts">
import { Construction } from '@lucide/vue';
import type { PemeriksaanMenu } from '@/types';

defineProps<{
    menu: PemeriksaanMenu | undefined;
}>();
</script>

<template>
    <section class="grid min-h-[360px] place-items-center rounded-lg border border-dashed bg-muted/20 p-8 text-center">
        <div class="max-w-md space-y-3">
            <div class="mx-auto grid size-12 place-items-center rounded-full bg-primary/10 text-primary">
                <Construction class="size-6" />
            </div>
            <div class="space-y-1">
                <h2 class="text-lg font-semibold">{{ menu?.label ?? 'Pemeriksaan' }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ menu?.description ?? 'Modul pemeriksaan ini akan dilanjutkan setelah layout CPPT disetujui.' }}
                </p>
            </div>
        </div>
    </section>
</template>
