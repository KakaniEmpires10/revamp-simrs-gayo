<script setup lang="ts">
import { ShieldCheck, Wifi } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    antrolConfigured: boolean;
    vclaimConfigured: boolean;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Status Integrasi</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-md border p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 font-medium">
                        <Wifi class="size-4 text-primary" />
                        Antrol
                    </div>
                    <Badge :variant="antrolConfigured ? 'success' : 'warning'">
                        {{ antrolConfigured ? 'Terkonfigurasi' : 'Belum lengkap' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Antrian online, jadwal HFIS, dan pengiriman Task ID.
                </p>
            </div>

            <div class="rounded-md border p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 font-medium">
                        <ShieldCheck class="size-4 text-primary" />
                        VClaim
                    </div>
                    <Badge :variant="vclaimConfigured ? 'success' : 'warning'">
                        {{ vclaimConfigured ? 'Terkonfigurasi' : 'Belum lengkap' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    SEP, peserta, rujukan, kontrol, dan monitoring kunjungan.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
