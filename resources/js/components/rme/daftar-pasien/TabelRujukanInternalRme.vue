<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { AlertTriangle, ArrowDownNarrowWide, ArrowRight, ArrowUpNarrowWide, Bed, Hospital, IdCard, ScrollText, Stethoscope, UserRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useScrollTabelManual } from '@/composables/useScrollTabelManual';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import type { PaginatedRme, PasienRujukanInternalRme } from '@/types';

const props = defineProps<{
    patients: PaginatedRme<PasienRujukanInternalRme>;
    loading?: boolean;
    order?: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    ubahOrder: [order: 'asc' | 'desc'];
    periksa: [patient: PasienRujukanInternalRme];
    pindahRawatInap: [patient: PasienRujukanInternalRme];
    ttv: [patient: PasienRujukanInternalRme];
}>();

const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.patients.data.length,
    total: () => props.patients.total,
    loading: () => props.loading ?? false,
});

function varianJenisKelamin(jenisKelamin: string | null): BadgeVariants['variant'] {
    return jenisKelamin === 'L' ? 'soft-info' : jenisKelamin === 'P' ? 'soft-indigo' : 'muted';
}

function varianStatus(status: string): BadgeVariants['variant'] {
    if (['Sudah', 'Berkas Diterima'].includes(status)) {
        return 'soft-success';
    }

    if (['Batal', 'Meninggal'].includes(status)) {
        return 'soft-destructive';
    }

    if (['Dirawat', 'Dirujuk'].includes(status)) {
        return 'soft-info';
    }

    return 'soft-warning';
}

function isTerisi(value: 0 | 1 | boolean | string | null | undefined): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function sudahDiperiksaDokter(patient: PasienRujukanInternalRme): boolean {
    return isTerisi(patient.sudah_diperiksa_dokter);
}

function sudahDiperiksaPerawat(patient: PasienRujukanInternalRme): boolean {
    return isTerisi(patient.sudah_diperiksa_perawat);
}

function isRanap(patient: PasienRujukanInternalRme): boolean {
    return isTerisi(patient.is_ranap);
}

function classStatusPemeriksaan(patient: PasienRujukanInternalRme): string | undefined {
    if (sudahDiperiksaDokter(patient)) {
        return '[&>td]:!bg-success/10 hover:[&>td]:!bg-success/15 dark:[&>td]:!bg-success/15 dark:hover:[&>td]:!bg-success/20';
    }

    if (sudahDiperiksaPerawat(patient)) {
        return '[&>td]:!bg-warning/15 hover:[&>td]:!bg-warning/20 dark:[&>td]:!bg-warning/20 dark:hover:[&>td]:!bg-warning/25';
    }

    return undefined;
}

function toggleOrder(): void {
    emit('ubahOrder', props.order === 'desc' ? 'asc' : 'desc');
}
</script>

<template>
    <section class="flex min-w-0 flex-col gap-3">
        <div class="w-full max-w-full min-w-0 overflow-x-auto">
            <InfiniteScroll
                ref="infiniteScroll"
                data="patients"
                items-element="#rme-rujukan-internal-table-body"
                only-next
                manual
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-40 text-center">Aksi</TableHead>
                            <TableHead>No Rawat</TableHead>
                            <TableHead>Pasien</TableHead>
                            <TableHead>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                class="-ml-3 h-8 px-2"
                                                :aria-label="order === 'desc' ? 'Urutkan dari rujukan paling awal' : 'Urutkan dari rujukan terbaru'"
                                                @click="toggleOrder"
                                            >
                                                Waktu
                                                <component :is="order === 'desc' ? ArrowUpNarrowWide : ArrowDownNarrowWide" class="size-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ order === 'desc' ? 'Urutan Terbaru' : 'Urutan Terlama' }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableHead>
                            <TableHead>Asal</TableHead>
                            <TableHead>Tujuan Internal</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody id="rme-rujukan-internal-table-body">
                        <template v-if="loading">
                            <TableRow v-for="index in 6" :key="`loading-${index}`">
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-8 w-28 rounded-md" />
                                        <Skeleton class="h-8 w-28 rounded-md" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-5 w-32" /></TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-44" />
                                        <Skeleton class="h-3 w-52" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-36" />
                                        <Skeleton class="h-3 w-28" />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-36" />
                                        <Skeleton class="h-3 w-28" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-6 w-20 rounded-full" /></TableCell>
                            </TableRow>
                        </template>

                        <template v-else-if="patients.data.length">
                            <TableRow
                                v-for="patient in patients.data"
                                :key="`${patient.no_rawat}-${patient.kd_poli_tujuan}-${patient.kd_dokter_tujuan}`"
                                class="align-middle"
                                :class="classStatusPemeriksaan(patient)"
                            >
                                <TableCell>
                                    <div class="grid min-w-36 justify-items-start gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            class="w-full"
                                            @click="emit('periksa', patient)"
                                        >
                                            <Stethoscope class="size-4" />
                                            Periksa
                                        </Button>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    size="sm"
                                                    class="w-full"
                                                >
                                                    <ScrollText class="size-4" />
                                                    Tindakan Lain
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-56" align="start">
                                                <DropdownMenuItem :disabled="isRanap(patient)" @click="emit('pindahRawatInap', patient)">
                                                    <Bed class="mr-2 size-4" />
                                                    {{ isRanap(patient) ? 'Sudah Rawat Inap' : 'Pindah Rawat Inap' }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-40 gap-1">
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger class="w-fit">
                                                    <button type="button"
                                                        class="w-fit font-mono text-[15px] font-semibold leading-tight text-primary underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                        @click="emit('ttv', patient)">
                                                        {{ patient.no_rawat }}
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    klik untuk Isi/Edit TTV Pasien
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <Badge variant="muted" size="sm" class="font-mono">
                                            RM {{ patient.no_rkm_medis }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-40 gap-2">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <UserRound class="size-4 text-muted-foreground" />
                                            <span class="font-semibold leading-tight">{{ patient.nm_pasien }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <Badge :variant="varianJenisKelamin(patient.jk)" size="sm">
                                                {{ labelJenisKelamin(patient.jk) }}
                                            </Badge>
                                            <Badge variant="soft-warning" size="sm">
                                                {{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}
                                            </Badge>
                                            <br>
                                            <Badge variant="soft-info" size="sm" class="font-mono">
                                                <IdCard class="size-3" />
                                                {{ patient.no_ktp || '-' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid gap-1">
                                        <span class="font-medium">{{ patient.tgl_registrasi }}</span>
                                        <span class="text-xs text-muted-foreground">{{ patient.jam_reg }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-56 gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 text-[15px] font-semibold leading-tight">
                                            <Stethoscope class="size-4 text-muted-foreground" />
                                            {{ patient.nm_dokter_awal }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Hospital class="size-3.5" />
                                            {{ patient.nm_poli_awal }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-56 gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 text-[15px] font-semibold leading-tight text-primary">
                                            <ArrowRight class="size-4" />
                                            {{ patient.nm_dokter_tujuan }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Hospital class="size-3.5" />
                                            {{ patient.nm_poli_tujuan }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-40 gap-1.5">
                                        <Badge :variant="varianStatus(patient.stts)" size="sm">
                                            {{ patient.stts }}
                                        </Badge>
                                        <Badge variant="soft-indigo" size="sm">
                                            {{ patient.png_jawab }}
                                        </Badge>
                                        <Badge :variant="patient.stts_daftar === 'Baru' ? 'soft-primary' : 'soft-indigo'" size="sm">
                                            Pasien {{ patient.stts_daftar }}
                                        </Badge>
                                        <Badge v-if="isRanap(patient)" variant="warning" size="sm">
                                            <AlertTriangle class="size-3" />
                                            Sudah ranap
                                        </Badge>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>

                        <TableEmpty
                            v-else
                            :colspan="7"
                            icon="search"
                            severity="info"
                            title="Rujukan internal tidak ditemukan"
                            description="Coba ubah rentang tanggal, poli asal, poli tujuan, atau kata kunci pencarian."
                        />
                    </TableBody>
                </Table>

                <template #next="{ loading: loadingNext, hasMore }">
                    <div v-if="loadingNext" class="space-y-2 pt-1">
                        <div v-for="index in 3" :key="index" class="p-4">
                            <div class="grid grid-cols-[12%_16%_24%_12%_16%_16%_8%] items-center gap-3">
                                <div class="space-y-2">
                                    <Skeleton class="h-8 w-28 rounded-md" />
                                    <Skeleton class="h-8 w-28 rounded-md" />
                                </div>
                                <Skeleton class="h-5 w-32" />
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-44" />
                                    <Skeleton class="h-3 w-52" />
                                </div>
                                <Skeleton class="h-5 w-24" />
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-36" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-36" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                                <Skeleton class="h-6 w-20 rounded-full" />
                            </div>
                        </div>
                    </div>
                    <p v-else-if="!hasMore && patients.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                        Semua data sudah ditampilkan.
                    </p>
                </template>
            </InfiniteScroll>
        </div>

        <div class="flex items-center justify-between px-1">
            <p class="text-sm text-muted-foreground">
                Ditampilkan <span class="font-semibold text-foreground">{{ patients.data.length }}</span> dari
                <span class="font-semibold text-foreground">{{ patients.total }}</span> rujukan
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
