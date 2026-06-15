<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    Bed,
    Ban,
    CircleDot,
    FileText,
    Pencil,
    RotateCcw,
    ScrollText,
    Siren,
    Tag,
    Ticket,
    TriangleAlert,
    Trash2,
} from '@lucide/vue';
import {
    gelangPasien,
    labelGelang,
    noAntreanPoli,
} from '@/actions/App/Http/Controllers/Pendaftaran/CetakBerkasPasienController';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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

import type {
    PaginatedResponse,
    RegisteredPatientRow,
} from '@/types';

const props = defineProps<{
    patients: PaginatedResponse<RegisteredPatientRow>;
    loading?: boolean;
}>();

const emit = defineEmits<{
    edit: [patient: RegisteredPatientRow];
    hapus: [patient: RegisteredPatientRow];
    batal: [patient: RegisteredPatientRow];
    pindahRawatInap: [patient: RegisteredPatientRow];
    rujukInternal: [patient: RegisteredPatientRow];
    spri: [patient: RegisteredPatientRow];
}>();

const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.patients.data.length,
    total: () => props.patients.total,
    loading: () => props.loading ?? false,
});

function varianJenisKelamin(jenisKelamin: string | null): BadgeVariants['variant'] {
    if (jenisKelamin === 'L') {
        return 'soft-info';
    }

    if (jenisKelamin === 'P') {
        return 'soft-indigo';
    }

    return 'muted';
}

function varianStatusPeriksa(status: string): BadgeVariants['variant'] {
    const normalizedStatus = status.toLowerCase();

    if (normalizedStatus === 'sudah' || normalizedStatus === 'berkas diterima') {
        return 'soft-success';
    }

    if (normalizedStatus === 'batal' || normalizedStatus === 'meninggal') {
        return 'soft-destructive';
    }

    if (normalizedStatus === 'dirawat') {
        return 'soft-info';
    }

    return 'soft-warning';
}

function varianStatusBayar(status: string): BadgeVariants['variant'] {
    return status.toLowerCase() === 'sudah bayar' ? 'soft-success' : 'soft-warning';
}

function varianStatusDaftar(status: string): BadgeVariants['variant'] {
    return status.toLowerCase() === 'baru' ? 'soft-primary' : 'soft-indigo';
}

function labelAksiBatal(patient: RegisteredPatientRow): string {
    return patient.stts === 'Batal' ? 'Aktifkan Kembali Pendaftaran' : 'Batalkan Pendaftaran';
}

function bukaCetakan(url: string): void {
    window.open(url, '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <section class="flex min-w-0 flex-col gap-3">
        <div v-if="!loading" class="w-full max-w-full min-w-0 overflow-x-auto">
            <InfiniteScroll
                ref="infiniteScroll"
                data="registeredPatients"
                items-element="#registered-patient-table-body"
                only-next
                manual
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>No Reg</TableHead>
                            <TableHead>No Rawat</TableHead>
                            <TableHead>Pasien</TableHead>
                            <TableHead>Waktu</TableHead>
                            <TableHead>Poli / Dokter</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-center">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody id="registered-patient-table-body">
                        <template v-if="patients.data.length">
                            <TableRow
                                v-for="patient in patients.data"
                                :key="patient.no_rawat"
                                :class="patient.is_ranap ? 'bg-warning/10 hover:bg-warning/15' : undefined"
                            >
                                <TableCell class="font-mono text-base font-semibold text-primary">
                                    {{ patient.no_reg }}
                                </TableCell>
                                <TableCell>
                                    <span class="font-mono font-semibold">{{ patient.no_rawat }}</span>
                                    <br>
                                    <Badge variant="soft-success" class="font-mono text-[11px]">
                                        NIK: {{ patient.no_ktp || '-' }}
                                    </Badge>
                                    <br>
                                    <Badge variant="soft-info" class="font-mono text-[11px]">
                                        BPJS: {{ patient.no_peserta || '-' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="grid min-w-72 gap-2">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="font-medium leading-tight">{{ patient.nm_pasien }}</span>
                                            <Badge variant="soft-indigo" size="sm">{{ patient.png_jawab }}</Badge>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <Badge variant="soft-primary" class="font-mono text-[11px]">
                                                RM: {{ patient.no_rkm_medis }}
                                            </Badge>
                                            <Badge :variant="varianJenisKelamin(patient.jk)" class="text-[11px]">
                                                {{ labelJenisKelamin(patient.jk) }}
                                            </Badge>
                                            <Badge variant="soft-warning" class="text-[11px]">
                                                Usia: {{ hitungUmur(patient.tgl_lahir, patient.tgl_registrasi) }}
                                            </Badge>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid gap-1">
                                        <span>{{ patient.tgl_registrasi }}</span>
                                        <span class="text-muted-foreground text-xs">{{ patient.jam_reg }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="grid gap-1">
                                        <span>{{ patient.nm_poli }}</span>
                                        <span class="text-muted-foreground text-xs">{{ patient.nm_dokter }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1.5">
                                        <Badge :variant="varianStatusPeriksa(patient.stts)" size="sm">
                                            Periksa: {{ patient.stts }}
                                        </Badge>
                                        <Badge :variant="varianStatusBayar(patient.status_bayar)" size="sm">
                                            {{ patient.status_bayar }}
                                        </Badge>
                                        <Badge :variant="varianStatusDaftar(patient.stts_daftar)" size="sm">
                                            Pasien {{ patient.stts_daftar }}
                                        </Badge>
                                        <Badge :variant="patient.is_mjkn ? 'soft-info' : 'muted'" size="sm">
                                            {{ patient.is_mjkn ? 'Online MJKN' : 'Onsite RS' }}
                                        </Badge>
                                        <Badge v-if="patient.is_ranap" variant="warning" size="sm">
                                            <TriangleAlert class="size-3" />
                                            Sudah pindah rawat inap
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="secondary" size="sm">
                                                    <ScrollText class="size-4" />
                                                    Tindakan RM
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-56" align="end">
                                                <DropdownMenuLabel>
                                                    Cetak Berkas Pasien
                                                </DropdownMenuLabel>
                                                <DropdownMenuGroup>
                                                    <DropdownMenuItem @click="bukaCetakan(noAntreanPoli.url(patient.no_rawat))">
                                                        <Ticket class="mr-2 size-4" />
                                                        No. Antrean Poli
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem @click="bukaCetakan(labelGelang.url(patient.no_rawat))">
                                                        <Tag class="mr-2 size-4" />
                                                        Label Gelang
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem @click="bukaCetakan(gelangPasien.url(patient.no_rawat))">
                                                        <CircleDot class="mr-2 size-4" />
                                                        Gelang Pasien
                                                    </DropdownMenuItem>
                                                </DropdownMenuGroup>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuLabel>BPJS</DropdownMenuLabel>
                                                <DropdownMenuGroup>
                                                    <DropdownMenuItem>
                                                        <FileText class="mr-2 size-4" />
                                                        SEP
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem>
                                                        <Siren class="mr-2 size-4" />
                                                        SEP IGD
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem>
                                                        <FileText class="mr-2 size-4" />
                                                        SEP Ranap
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem :disabled="!patient.no_peserta" @click="emit('spri', patient)">
                                                        <Bed class="mr-2 size-4" />
                                                        SPRI
                                                    </DropdownMenuItem>
                                                </DropdownMenuGroup>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem :disabled="patient.is_ranap" @click="emit('pindahRawatInap', patient)">
                                                    <Bed class="mr-2 size-4" />
                                                    {{ patient.is_ranap ? 'Sudah Rawat Inap' : 'Pindah Rawat Inap' }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="emit('rujukInternal', patient)">
                                                    <ArrowRightLeft class="mr-2 size-4" />
                                                    Rujuk Internal
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="emit('edit', patient)">
                                                    <Pencil class="mr-2 size-4" />
                                                    Edit Data Pendaftaran
                                                </DropdownMenuItem>
                                                <DropdownMenuItem variant="destructive" as-child>
                                                    <button type="button" class="w-full" @click="emit('batal', patient)">
                                                        <component :is="patient.stts === 'Batal' ? RotateCcw : Ban" class="mr-2 size-4" />
                                                        {{ labelAksiBatal(patient) }}
                                                    </button>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="soft-destructive"
                                                        class="size-8"
                                                        @click="emit('hapus', patient)"
                                                    >
                                                        <Trash2 class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Hapus pendaftaran</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-if="!patients.data.length" :colspan="7" icon="search">
                            Pasien terdaftar tidak ditemukan untuk filter ini.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <template #next="{ loading: loadingNext, hasMore }">
                    <div v-if="loadingNext" class="space-y-2 pt-1">
                        <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                            <div class="grid grid-cols-[10%_15%_25%_12%_16%_10%_12%] items-center gap-2">
                                <Skeleton class="h-5 w-14" />
                                <Skeleton class="h-5 w-32" />
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-44" />
                                    <Skeleton class="h-3 w-52" />
                                    <Skeleton class="h-3 w-40" />
                                </div>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-16" />
                                </div>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                                <Skeleton class="h-4 w-20" />
                                <div class="flex justify-center">
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else-if="!hasMore && patients.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                        Semua pendaftaran sudah ditampilkan.
                    </p>
                </template>
            </InfiniteScroll>
        </div>

        <div v-else class="w-full max-w-full min-w-0 overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>No Reg</TableHead>
                        <TableHead>No Rawat</TableHead>
                        <TableHead>Pasien</TableHead>
                        <TableHead>Waktu</TableHead>
                        <TableHead>Poli / Dokter</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-center">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="index in 6" :key="index">
                        <TableCell>
                            <Skeleton class="h-5 w-14" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-5 w-32" />
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-44" />
                                <Skeleton class="h-3 w-52" />
                                <Skeleton class="h-3 w-40" />
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-24" />
                                <Skeleton class="h-3 w-16" />
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-32" />
                                <Skeleton class="h-3 w-28" />
                            </div>
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-6 w-28 rounded-full" />
                        </TableCell>
                        <TableCell>
                            <div class="flex justify-center">
                                <Skeleton class="h-9 w-9 rounded-lg" />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 px-1 text-sm">
            <p class="text-muted-foreground">
                <template v-if="loading">Mengambil data terbaru...</template>
                <template v-else>
                    Ditampilkan <span class="font-semibold text-foreground">{{ patients.data.length }}</span> dari
                    <span class="font-semibold text-foreground">{{ patients.total }}</span> pendaftaran
                </template>
            </p>
            <p v-if="isFetchingNext" class="text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
