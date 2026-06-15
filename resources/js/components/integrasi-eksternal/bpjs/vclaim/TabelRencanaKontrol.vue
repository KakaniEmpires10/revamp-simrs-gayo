<script setup lang="ts">
import { computed } from 'vue';
import { FileText, Pencil, Trash2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';

type ControlPlanRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ControlPlanRow[];
    };
    loading: boolean;
}>();

const emit = defineEmits<{
    edit: [row: ControlPlanRow];
    hapus: [row: ControlPlanRow];
    cetak: [event: Event];
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data SKDP tidak ditemukan',
    'Gagal mengambil data SKDP',
    'Coba ubah rentang tanggal untuk menemukan rencana kontrol rawat jalan.',
));

function value(row: ControlPlanRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function letterNumber(row: ControlPlanRow | null): string {
    return value(row, 'noSuratKontrol', 'noSurat');
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>No SKDP / No SEP</TableHead>
                <TableHead>Peserta</TableHead>
                <TableHead>Tanggal Rencana</TableHead>
                <TableHead>Poli & Dokter</TableHead>
                <TableHead class="min-w-56 text-center">Aksi</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 5" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="letterNumber(row)">
                    <TableCell>
                        <p class="font-mono text-sm font-semibold">{{ letterNumber(row) }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ value(row, 'noSep', 'noSEP', 'noSepAsalKontrol') }}</p>
                    </TableCell>
                    <TableCell>
                        <p class="font-semibold">{{ value(row, 'nama') }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ value(row, 'noKartu') }}</p>
                    </TableCell>
                    <TableCell>
                        <Badge variant="soft-primary" size="sm">
                            {{ value(row, 'tglRencanaKontrol', 'tglKontrol') }}
                        </Badge>
                    </TableCell>
                    <TableCell>
                        <p class="font-medium">{{ value(row, 'namaPoliTujuan', 'namaPoli', 'poliTujuan') }}</p>
                        <p class="text-xs text-muted-foreground">{{ value(row, 'namaDokter', 'dokter') }}</p>
                    </TableCell>
                    <TableCell>
                        <TooltipProvider>
                            <div class="flex flex-wrap items-center justify-center gap-1.5">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-indigo" size="sm" @click="emit('edit', row)">
                                            <Pencil class="size-3.5" />
                                            Edit
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Edit data SKDP.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button as="a" href="#" variant="soft-info" size="sm" @click="emit('cetak', $event)">
                                            <FileText class="size-3.5" />
                                            Cetak
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Cetak SKDP belum diaktifkan.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-destructive" size="sm" @click="emit('hapus', row)">
                                            <Trash2 class="size-3.5" />
                                            Hapus
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Hapus SKDP dari BPJS dan database lokal.</TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="5"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
