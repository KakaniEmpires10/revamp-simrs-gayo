<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';

type PrbValue = string | number | PrbRecord | null | undefined;

interface PrbRecord {
    [key: string]: PrbValue;
}

type PrbRow = PrbRecord;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: PrbRow[];
    };
    loading: boolean;
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data PRB tidak ditemukan',
    'Gagal mengambil data PRB',
    'Coba ubah rentang tanggal untuk menemukan data Program Rujuk Balik.',
));

function nestedValue(row: PrbRow, key: string): PrbValue {
    return key
        .split('.')
        .reduce<PrbValue>((current, segment) => {
            if (current === null || current === undefined || typeof current !== 'object') {
                return undefined;
            }

            return current[segment];
        }, row);
}

function value(row: PrbRow, ...keys: string[]): string {
    for (const key of keys) {
        const item = nestedValue(row, key);

        if (item !== null && item !== undefined && typeof item !== 'object' && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>No SRB</TableHead>
                <TableHead>Peserta</TableHead>
                <TableHead>No SEP</TableHead>
                <TableHead>Program</TableHead>
                <TableHead>DPJP</TableHead>
                <TableHead>Keterangan</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 6" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="value(row, 'noSRB', 'noSrb')">
                    <TableCell>
                        <p class="font-mono text-sm font-semibold">{{ value(row, 'noSRB', 'noSrb') }}</p>
                        <Badge variant="soft-success" size="sm" class="mt-1">{{ value(row, 'tglSRB', 'tglSrb') }}</Badge>
                    </TableCell>
                    <TableCell>
                        <p class="font-semibold">{{ value(row, 'peserta.nama', 'nama', 'namaPeserta') }}</p>
                        <p class="mb-1 text-xs text-muted-foreground">{{ value(row, 'peserta.alamat', 'alamat') }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <Badge variant="soft-info" size="sm" class="font-mono">{{ value(row, 'peserta.noKartu', 'noKartu') }}</Badge>
                            <Badge variant="soft-indigo" size="sm">{{ value(row, 'peserta.noTelepon', 'noTelepon') }}</Badge>
                        </div>
                    </TableCell>
                    <TableCell class="font-mono text-sm">{{ value(row, 'noSEP', 'noSep') }}</TableCell>
                    <TableCell>
                        <p class="font-medium">{{ value(row, 'programPRB.nama', 'programPrb.nama', 'programPRB') }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ value(row, 'programPRB.kode', 'programPrb.kode') }}</p>
                    </TableCell>
                    <TableCell>
                        <p class="font-medium">{{ value(row, 'DPJP.nama', 'dpjp.nama', 'dpjp', 'namaDokter') }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ value(row, 'DPJP.kode', 'dpjp.kode') }}</p>
                    </TableCell>
                    <TableCell>
                        <p>{{ value(row, 'keterangan') }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ value(row, 'saran') }}</p>
                    </TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="6"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
