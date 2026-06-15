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

type ReferralRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ReferralRow[];
    };
    loading: boolean;
}>();

const emit = defineEmits<{
    edit: [row: ReferralRow];
    hapus: [row: ReferralRow];
    cetak: [event: Event];
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data rujukan tidak ditemukan',
    'Gagal mengambil data rujukan',
    'Coba ubah rentang tanggal untuk menemukan data rujukan keluar.',
));

function value(row: ReferralRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function referralNumber(row: ReferralRow | null): string {
    return value(row, 'noRujukan', 'noKunjungan');
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>No Rujukan</TableHead>
                <TableHead>Peserta</TableHead>
                <TableHead>PPK Dirujuk</TableHead>
                <TableHead class="min-w-56 text-center">Aksi</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 4" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="referralNumber(row)">
                    <TableCell class="font-mono text-sm font-semibold">{{ referralNumber(row) }}</TableCell>
                    <TableCell>
                        <p class="font-semibold">{{ row.nama ?? '-' }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <Badge variant="soft-info" size="sm" class="font-mono">{{ row.noKartu ?? '-' }}</Badge>
                            <Badge variant="soft-primary" size="sm" class="font-mono">SEP: {{ row.noSep ?? '-' }}</Badge>
                        </div>
                    </TableCell>
                    <TableCell>
                        <p class="font-medium">{{ row.ppkDirujuk ?? '-' }} - {{ row.namaPpkDirujuk ?? '-' }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <Badge variant="soft-primary" size="sm">{{ row.tglRujukan ?? '-' }}</Badge>
                            <Badge :variant="row.jnsPelayanan === '1' ? 'soft-warning' : 'soft-success'" size="sm">
                                {{ row.jnsPelayanan === '1' ? 'Rawat Inap' : 'Rawat Jalan' }}
                            </Badge>
                        </div>
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
                                    <TooltipContent>Edit rujukan keluar BPJS.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button as="a" href="#" variant="soft-info" size="sm" @click="emit('cetak', $event)">
                                            <FileText class="size-3.5" />
                                            Cetak
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Cetak rujukan belum diaktifkan.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-destructive" size="sm" @click="emit('hapus', row)">
                                            <Trash2 class="size-3.5" />
                                            Hapus
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Hapus rujukan dari BPJS dan database lokal.</TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="4"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
