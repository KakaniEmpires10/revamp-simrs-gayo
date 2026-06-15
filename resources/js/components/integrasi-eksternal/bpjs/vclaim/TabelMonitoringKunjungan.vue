<script setup lang="ts">
import { ArrowLeftRight } from '@lucide/vue';
import { ClipboardList, DownloadCloud, FileText, Pencil, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
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

type VisitRow = Record<string, string | number | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: VisitRow[];
    };
    loading: boolean;
}>();

const emit = defineEmits<{
    editSep: [event: Event];
    cetakSep: [event: Event];
    kontrol: [row: VisitRow];
    rujuk: [row: VisitRow];
    tarikSep: [row: VisitRow];
    hapusSep: [row: VisitRow];
}>();

const emptyState = computed(() => statusTabelBpjs(
    props.result.metadata,
    'Data kunjungan tidak ditemukan',
    'Gagal mengambil data kunjungan',
    'Coba ubah tanggal SEP atau jenis pelayanan, lalu filter ulang data kunjungan.',
));
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>No SEP / Kartu</TableHead>
                <TableHead>Peserta</TableHead>
                <TableHead>No Rujukan</TableHead>
                <TableHead>Poli</TableHead>
                <TableHead>Tanggal</TableHead>
                <TableHead>Diagnosa</TableHead>
                <TableHead class="min-w-110 text-center">Aksi</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="loading">
                <TableRow v-for="index in 5" :key="index">
                    <TableCell v-for="cell in 7" :key="cell">
                        <Skeleton class="h-5 w-full" />
                    </TableCell>
                </TableRow>
            </template>
            <template v-else-if="result.metadata.code === '200' && result.rows.length">
                <TableRow v-for="row in result.rows" :key="String(row.noSep ?? row.noRujukan ?? row.noKartu)">
                    <TableCell>
                        <p class="font-mono text-sm font-semibold">{{ row.noSep ?? '-' }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ row.noKartu ?? '-' }}</p>
                    </TableCell>
                    <TableCell>
                        <p class="font-semibold">{{ row.nama ?? '-' }}</p>
                        <Badge variant="soft-info" size="sm">{{ row.jnsPelayanan ?? '-' }}</Badge>
                        <Badge variant="soft-warning" size="sm" class="ml-1">Kelas {{ row.kelasRawat ?? '-' }}</Badge>
                    </TableCell>
                    <TableCell class="font-mono text-sm">{{ row.noRujukan ?? '-' }}</TableCell>
                    <TableCell>{{ row.poli ?? '-' }}</TableCell>
                    <TableCell>
                        <p>{{ row.tglSep ?? '-' }}</p>
                        <p class="text-xs text-muted-foreground">Pulang: {{ row.tglPlgSep ?? '-' }}</p>
                    </TableCell>
                    <TableCell>{{ row.diagnosa ?? '-' }}</TableCell>
                    <TableCell>
                        <TooltipProvider>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button as="a" href="#" variant="soft-indigo" size="sm" @click="emit('editSep', $event)">
                                            <Pencil class="size-3.5" />
                                            Edit SEP
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Edit SEP belum diaktifkan.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button as="a" href="#" variant="soft-info" size="sm" @click="emit('cetakSep', $event)">
                                            <FileText class="size-3.5" />
                                            Cetak SEP
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Cetak SEP belum diaktifkan.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-primary" size="sm" @click="emit('kontrol', row)">
                                            <ClipboardList class="size-3.5" />
                                            Kontrol / SKDP
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Buat kontrol/SKDP dari SEP ini.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-warning" size="sm" @click="emit('rujuk', row)">
                                            <ArrowLeftRight class="size-3.5" />
                                            Rujuk
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Buat rujukan keluar dari SEP ini.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-success" size="sm" @click="emit('tarikSep', row)">
                                            <DownloadCloud class="size-3.5" />
                                            Tarik SEP
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Tarik SEP ke database lokal SIMRS.</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="soft-destructive" size="sm" @click="emit('hapusSep', row)">
                                            <Trash2 class="size-3.5" />
                                            Hapus SEP
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Hapus SEP dari BPJS dan database lokal.</TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </TableCell>
                </TableRow>
            </template>
            <TableEmpty
                v-else
                :colspan="7"
                :icon="emptyState.icon"
                :severity="emptyState.severity"
                :title="emptyState.title"
                :description="emptyState.description"
            />
        </TableBody>
    </Table>
</template>
