<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, ArrowLeft, CheckCircle2, Clock3, Loader2, PlugZap, Server, WifiOff } from '@lucide/vue';
import { ref } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { connection } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Cek Koneksi BPJS', href: connection() },
        ],
    },
});

defineProps<{
    result: {
        summary: {
            total: number;
            online: number;
            offline: number;
            average_duration_ms: number | null;
        };
        services: Array<{
            id: string;
            title: string;
            description: string;
            category: string;
            method: string;
            target: string;
            status: 'online' | 'offline';
            metadata: {
                code: string;
                message: string;
            };
            duration_ms: number;
            speed: {
                label: string;
                variant: 'success' | 'warning' | 'destructive';
            };
        }>;
    } | null;
}>();

const isChecking = ref(false);

function checkConnection(): void {
    router.get(
        connection.url(),
        { check: 1 },
        {
            only: ['result'],
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                isChecking.value = true;
            },
            onFinish: () => {
                isChecking.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Cek Koneksi BPJS" />

    <div class="contents">
        <PageHeader
            title="Cek Koneksi BPJS"
            description="Validasi konfigurasi dan koneksi layanan VClaim sebelum digunakan untuk SEP, rujukan, dan monitoring kunjungan."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="bpjsIndex()">
                        <ArrowLeft class="size-4" />
                        Kembali
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Card>
            <CardContent class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <PlugZap class="size-5" />
                    </div>
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-foreground">Monitoring Service BPJS</h2>
                            <Badge :variant="result?.summary.offline === 0 ? 'success' : 'soft-warning'" rounded="md">
                                {{ result ? `${result.summary.online}/${result.summary.total} Online` : 'Belum dicek' }}
                            </Badge>
                        </div>
                        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                            Pengecekan memakai beberapa endpoint BPJS seperti di aplikasi registrasi agar latency tiap service terlihat terpisah.
                        </p>
                    </div>
                </div>

                <div v-if="result" class="grid gap-2 sm:grid-cols-2 md:min-w-72">
                    <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Clock3 class="size-3.5" />
                            Rata-rata Response
                        </div>
                        <p class="mt-1 font-mono text-lg font-semibold text-foreground">
                            {{ result.summary.average_duration_ms ? `${result.summary.average_duration_ms} ms` : '-' }}
                        </p>
                    </div>
                    <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Activity class="size-3.5" />
                            Offline
                        </div>
                        <p class="mt-1 text-lg font-semibold text-foreground">
                            {{ result.summary.offline }} service
                        </p>
                    </div>
                </div>

                <Button :disabled="isChecking" @click="checkConnection">
                    <Loader2 v-if="isChecking" class="size-4 animate-spin" />
                    <PlugZap v-else class="size-4" />
                    Cek Semua Service
                </Button>
            </CardContent>
        </Card>

        <Alert v-if="result" :variant="result.summary.offline === 0 ? 'soft-success' : 'soft-warning'">
            <CheckCircle2 v-if="result.summary.offline === 0" class="size-4" />
            <WifiOff v-else class="size-4" />
            <AlertTitle>{{ result.summary.offline === 0 ? 'Semua service BPJS online' : 'Sebagian service BPJS bermasalah' }}</AlertTitle>
            <AlertDescription>
                {{ result.summary.online }} dari {{ result.summary.total }} service merespons.
                <span v-if="result.summary.average_duration_ms" class="font-medium">Rata-rata response {{ result.summary.average_duration_ms }} ms.</span>
            </AlertDescription>
        </Alert>

        <section v-if="result" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="service in result.services"
                :key="service.id"
                class="overflow-hidden"
                :class="service.status === 'online' ? 'border-success/20' : 'border-destructive/25'"
            >
                <CardContent class="flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div
                                class="grid size-10 shrink-0 place-items-center rounded-md"
                                :class="service.status === 'online' ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'"
                            >
                                <Server class="size-5" />
                            </div>
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-foreground">{{ service.title }}</h3>
                                    <Badge variant="outline" size="xs" rounded="md">{{ service.category }}</Badge>
                                </div>
                                <p class="text-sm leading-5 text-muted-foreground">{{ service.description }}</p>
                            </div>
                        </div>
                        <Badge :variant="service.status === 'online' ? 'success' : 'destructive'" rounded="md">
                            {{ service.status === 'online' ? 'Online' : 'Offline' }}
                        </Badge>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-md border bg-background/70 px-3 py-2">
                            <p class="text-xs text-muted-foreground">Response</p>
                            <p class="font-mono text-base font-semibold">{{ service.duration_ms }} ms</p>
                        </div>
                        <div class="rounded-md border bg-background/70 px-3 py-2">
                            <p class="text-xs text-muted-foreground">Kecepatan</p>
                            <Badge :variant="service.speed.variant" size="sm" rounded="md">{{ service.speed.label }}</Badge>
                        </div>
                    </div>

                    <div class="rounded-md bg-muted/40 px-3 py-2">
                        <div class="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                            <span>{{ service.method }}</span>
                            <span>Kode {{ service.metadata.code }}</span>
                        </div>
                        <p class="mt-1 truncate font-mono text-xs text-muted-foreground">{{ service.target }}</p>
                        <p class="mt-2 text-sm text-foreground">{{ service.metadata.message }}</p>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
