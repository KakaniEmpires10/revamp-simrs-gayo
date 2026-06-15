<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';

type ClaimValue = string | number | ClaimRecord | null | undefined;

interface ClaimRecord {
    [key: string]: ClaimValue;
}

type ClaimRow = ClaimRecord;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ClaimRow[];
    };
    loading: boolean;
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data klaim tidak ditemukan',
    'Gagal mengambil data klaim',
    'Coba ubah tanggal, jenis pelayanan, atau status klaim.',
));

function value(row: ClaimRow, ...keys: string[]): string {
    for (const key of keys) {
        const item = nestedValue(row, key);

        if (typeof item !== 'object' && item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function nestedValue(row: ClaimRecord, key: string): ClaimValue {
    return key
        .split('.')
        .reduce<ClaimValue>((current, segment) => {
            if (current === null || current === undefined || typeof current !== 'object') {
                return undefined;
            }

            return current[segment];
        }, row);
}

function currency(row: ClaimRow, key: string): string {
    const amount = Number(nestedValue(row, key) ?? 0);

    if (!Number.isFinite(amount)) {
        return '-';
    }

    return new Intl.NumberFormat('id-ID', {
        currency: 'IDR',
        maximumFractionDigits: 0,
        style: 'currency',
    }).format(amount);
}
</script>

<template>
    <Table>
        <TableHeader variant="grouped">
            <TableRow>
                <TableHead rowspan="2" class="w-12 text-center">No</TableHead>
                <TableHead colspan="2" class="text-center">Inacbg</TableHead>
                <TableHead colspan="5" class="text-center">Biaya</TableHead>
                <TableHead rowspan="2" class="text-center">Kelas Rawat</TableHead>
                <TableHead rowspan="2" class="text-center">No FPK</TableHead>
                <TableHead rowspan="2">No SEP</TableHead>
                <TableHead colspan="3" class="text-center">Peserta</TableHead>
                <TableHead rowspan="2">Poliklinik</TableHead>
                <TableHead rowspan="2" class="text-center">Status</TableHead>
                <TableHead rowspan="2" class="text-center">Tanggal Pulang</TableHead>
                <TableHead rowspan="2" class="text-center">Tanggal SEP</TableHead>
            </TableRow>
            <TableRow>
                <TableHead>Kode</TableHead>
                <TableHead>Nama</TableHead>
                <TableHead class="text-right">Pengajuan</TableHead>
                <TableHead class="text-right">Disetujui</TableHead>
                <TableHead class="text-right">Tarif Gruper</TableHead>
                <TableHead class="text-right">Tarif RS</TableHead>
                <TableHead class="text-right">Topup</TableHead>
                <TableHead>Nama Peserta</TableHead>
                <TableHead>No Kartu</TableHead>
                <TableHead>No MR</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 18" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="(row, index) in result.rows" :key="value(row, 'noSEP', 'noSep')">
                    <TableCell class="text-center text-sm text-muted-foreground">{{ index + 1 }}</TableCell>
                    <TableCell class="font-mono text-sm">{{ value(row, 'Inacbg.kode', 'inacbg.kode') }}</TableCell>
                    <TableCell class="min-w-48">{{ value(row, 'Inacbg.nama', 'inacbg.nama') }}</TableCell>
                    <TableCell class="text-right font-mono text-sm">{{ currency(row, 'biaya.byPengajuan') }}</TableCell>
                    <TableCell class="text-right font-mono text-sm">{{ currency(row, 'biaya.bySetujui') }}</TableCell>
                    <TableCell class="text-right font-mono text-sm">{{ currency(row, 'biaya.byTarifGruper') }}</TableCell>
                    <TableCell class="text-right font-mono text-sm">{{ currency(row, 'biaya.byTarifRS') }}</TableCell>
                    <TableCell class="text-right font-mono text-sm">{{ currency(row, 'biaya.byTopup') }}</TableCell>
                    <TableCell class="text-center">{{ value(row, 'kelasRawat') }}</TableCell>
                    <TableCell class="text-center font-mono text-sm">{{ value(row, 'noFPK') }}</TableCell>
                    <TableCell class="font-mono text-sm font-semibold">{{ value(row, 'noSEP', 'noSep') }}</TableCell>
                    <TableCell class="min-w-44 font-semibold">{{ value(row, 'peserta.nama', 'namaPeserta', 'nama') }}</TableCell>
                    <TableCell class="font-mono text-sm">{{ value(row, 'peserta.noKartu', 'noKartu') }}</TableCell>
                    <TableCell class="text-center font-mono text-sm">{{ value(row, 'peserta.noMR', 'noMR') }}</TableCell>
                    <TableCell class="min-w-36">{{ value(row, 'poli') }}</TableCell>
                    <TableCell class="text-center">
                        <Badge variant="soft-info" size="sm">{{ value(row, 'status', 'statusKlaim') }}</Badge>
                    </TableCell>
                    <TableCell class="text-center">{{ value(row, 'tglPulang') }}</TableCell>
                    <TableCell class="text-center">{{ value(row, 'tglSep', 'tglSEP') }}</TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="18"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
