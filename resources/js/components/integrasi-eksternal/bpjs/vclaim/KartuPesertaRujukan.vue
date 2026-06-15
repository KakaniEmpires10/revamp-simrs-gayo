<script setup lang="ts">
import { CreditCard, IdCard, Phone, ShieldCheck, UserRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Participant = Record<string, string | number | Record<string, string | number | null | undefined> | null | undefined>;

const props = defineProps<{
    peserta: Participant;
}>();

function participantNested(key: string, child: string): string {
    const value = props.peserta[key];

    if (value && typeof value === 'object') {
        return String(value[child] ?? '-');
    }

    return '-';
}

function participantValue(key: string): string {
    return String(props.peserta[key] ?? '-');
}
</script>

<template>
    <Card class="relative overflow-hidden border-primary/20 bg-gradient-to-br from-primary/8 via-card to-emerald-500/8">
        <svg class="pointer-events-none absolute -right-14 -bottom-24 h-56 w-96 rotate-[-15deg] text-primary/12" viewBox="0 0 420 180" fill="none" aria-hidden="true">
            <path
                d="M0 126C46 92 92 92 138 126C184 160 230 160 276 126C322 92 368 92 420 126V180H0V126Z"
                fill="currentColor"
            />
            <path
                d="M0 78C46 44 92 44 138 78C184 112 230 112 276 78C322 44 368 44 420 78"
                stroke="currentColor"
                stroke-width="14"
                stroke-linecap="round"
            />
        </svg>
        <svg class="pointer-events-none absolute -top-14 -left-6 h-36 w-64 rotate-[-18deg] text-emerald-500/10" viewBox="0 0 420 180" fill="none" aria-hidden="true">
            <path
                d="M0 96C50 132 90 132 140 96C190 60 230 60 280 96C330 132 370 132 420 96"
                stroke="currentColor"
                stroke-width="18"
                stroke-linecap="round"
            />
        </svg>

        <CardContent class="relative grid gap-5 lg:grid-cols-[1.1fr_1.7fr] lg:items-center">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="soft-success" rounded="md">Peserta BPJS</Badge>
                    <Badge variant="soft-primary" rounded="md">{{ participantNested('statusPeserta', 'keterangan') }}</Badge>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <UserRound class="size-4" />
                        <span class="text-xs font-medium uppercase tracking-wide">Informasi Peserta</span>
                    </div>
                    <p class="text-xl font-semibold leading-tight text-foreground">{{ participantValue('nama') }}</p>
                    <p class="font-mono text-sm text-muted-foreground">{{ participantValue('noKartu') }}</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <IdCard class="size-3.5" />
                        NIK
                    </div>
                    <p class="mt-1 font-mono text-sm font-semibold text-foreground">{{ participantValue('nik') }}</p>
                </div>

                <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <CreditCard class="size-3.5" />
                        No RM
                    </div>
                    <p class="mt-1 font-mono text-sm font-semibold text-foreground">{{ participantNested('mr', 'noMR') }}</p>
                </div>

                <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <Phone class="size-3.5" />
                        Telepon
                    </div>
                    <p class="mt-1 text-sm font-semibold text-foreground">{{ participantNested('mr', 'noTelepon') }}</p>
                </div>

                <div class="rounded-md border border-border/70 bg-background/70 px-3 py-2.5 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <ShieldCheck class="size-3.5" />
                        Hak Kelas
                    </div>
                    <p class="mt-1 text-sm font-semibold text-foreground">{{ participantNested('hakKelas', 'keterangan') }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
