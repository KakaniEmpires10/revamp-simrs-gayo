<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { statusTabelBpjs } from '@/lib/statusTabelBpjs';
import { UserX } from '@lucide/vue';

type FingerprintPatient = {
    no_rkm_medis?: string | null;
    nm_pasien?: string | null;
    jk?: string | null;
};

type FingerprintRow = Record<string, string | number | FingerprintPatient | null | undefined> & {
    pasien?: FingerprintPatient | null;
};

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: FingerprintRow[];
    };
    loading: boolean;
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data finger print tidak ditemukan',
    'Gagal mengambil data finger print',
    'Coba ubah tanggal pelayanan untuk mencari data validasi finger print.',
));

function genderVariant(row: FingerprintRow): 'soft-info' | 'soft-indigo' | 'soft-warning' {
    if (patientValue(row, 'jk') === 'L') {
        return 'soft-info';
    }

    if (patientValue(row, 'jk') === 'P') {
        return 'soft-indigo';
    }

    return 'soft-warning';
}

function genderLabel(row: FingerprintRow): string {
    if (patientValue(row, 'jk') === 'L') {
        return 'Laki-laki';
    }

    if (patientValue(row, 'jk') === 'P') {
        return 'Perempuan';
    }

    return '-';
}

function hasPatient(row: FingerprintRow): boolean {
    return patientValue(row, 'no_rkm_medis') !== '-'
        || patientValue(row, 'nm_pasien') !== '-'
        || patientValue(row, 'jk') !== '-';
}

function value(row: FingerprintRow, ...keys: string[]): string {
    for (const key of keys) {
        const item = row[key];

        if (typeof item !== 'object' && item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function patientValue(row: FingerprintRow, key: keyof FingerprintPatient): string {
    const item = row.pasien?.[key];

    return item !== null && item !== undefined && String(item) !== '' ? String(item) : '-';
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Informasi Pasien</TableHead>
                <TableHead>No Kartu</TableHead>
                <TableHead>No SEP</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 3" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="`${value(row, 'noKartu')}-${value(row, 'tglPelayanan', 'tanggal')}`">
                    <TableCell>
                        <div v-if="hasPatient(row)" class="flex flex-col gap-2">
                            <span class="font-semibold text-foreground">
                                {{ patientValue(row, 'nm_pasien') }}
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                    RM {{ patientValue(row, 'no_rkm_medis') }}
                                </Badge>
                                <Badge :variant="genderVariant(row)" size="sm">
                                    {{ genderLabel(row) }}
                                </Badge>
                            </div>
                        </div>
                        <Badge v-else variant="soft-warning" size="sm" class="gap-1.5">
                            <UserX class="size-3" />
                            Data pasien tidak ditemukan di database
                        </Badge>
                    </TableCell>
                    <TableCell class="font-mono text-sm font-semibold">{{ value(row, 'noKartu') }}</TableCell>
                    <TableCell class="font-mono text-sm">{{ value(row, 'noSEP', 'noSep') }}</TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="3"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
