<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';

type ApprovalRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ApprovalRow[];
    };
    loading: boolean;
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data persetujuan SEP tidak ditemukan',
    'Gagal mengambil persetujuan SEP',
    'Coba ubah periode bulan dan tahun untuk mencari data persetujuan SEP.',
));

function value(row: ApprovalRow, ...keys: string[]): string {
    for (const key of keys) {
        const item = row[key];

        if (item !== null && item !== undefined && String(item) !== '') {
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
                <TableHead>No Kartu</TableHead>
                <TableHead>Tanggal SEP</TableHead>
                <TableHead>Pelayanan</TableHead>
                <TableHead>Pengajuan</TableHead>
                <TableHead>Keterangan</TableHead>
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
                <TableRow v-for="row in result.rows" :key="`${value(row, 'noKartu')}-${value(row, 'tglSep')}`">
                    <TableCell class="font-mono text-sm font-semibold">{{ value(row, 'noKartu') }}</TableCell>
                    <TableCell>
                        <Badge variant="soft-primary" size="sm">{{ value(row, 'tglSep', 'tglSEP') }}</Badge>
                    </TableCell>
                    <TableCell>{{ value(row, 'jnsPelayanan', 'jenisPelayanan') }}</TableCell>
                    <TableCell>{{ value(row, 'jnsPengajuan', 'jenisPengajuan') }}</TableCell>
                    <TableCell>{{ value(row, 'keterangan') }}</TableCell>
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
