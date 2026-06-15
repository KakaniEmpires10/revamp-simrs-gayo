<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';

type ReferralRow = Record<string, string | number | Record<string, string | number | null | undefined> | null | undefined>;
type Participant = Record<string, string | number | Record<string, string | number | null | undefined> | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ReferralRow[];
        peserta: Participant;
    } | null;
    loading: boolean;
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result?.metadata,
    'Data rujukan tidak ditemukan',
    'Gagal mengambil data rujukan',
    'Pilih No Kartu atau No KTP, lalu masukkan identitas peserta untuk mencari rujukan.',
));

function nested(row: ReferralRow, key: string, child: string): string {
    const value = row[key];

    if (value && typeof value === 'object') {
        return String(value[child] ?? '-');
    }

    return '-';
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>No Rujukan</TableHead>
                <TableHead>Peserta</TableHead>
                <TableHead>Perujuk</TableHead>
                <TableHead>Poli</TableHead>
                <TableHead>Diagnosa</TableHead>
                <TableHead>Tanggal</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 4" :key="index">
                    <TableCell v-for="cell in 6" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result?.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="String(row.noKunjungan ?? row.noRujukan)">
                    <TableCell class="font-mono text-sm font-semibold">{{ row.noKunjungan ?? row.noRujukan ?? '-' }}</TableCell>
                    <TableCell>
                        <p class="font-semibold">{{ nested(row, 'peserta', 'nama') }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ nested(row, 'peserta', 'noKartu') }}</p>
                    </TableCell>
                    <TableCell>{{ nested(row, 'provPerujuk', 'nama') }}</TableCell>
                    <TableCell>{{ nested(row, 'poliRujukan', 'nama') }}</TableCell>
                    <TableCell>{{ nested(row, 'diagnosa', 'nama') }}</TableCell>
                    <TableCell>
                        <Badge variant="soft-primary" size="sm">{{ row.tglKunjungan ?? row.tglRujukan ?? '-' }}</Badge>
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
