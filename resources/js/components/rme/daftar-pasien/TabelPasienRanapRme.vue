<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import {
    Bed,
    CreditCard,
    DoorOpen,
    FileText,
    IdCard,
    LogOut,
    PenLine,
    ScrollText,
    Stethoscope,
    Trash2,
    UserCog,
    UserRound,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import type { PaginatedRme, PasienRanapRme, RmeOption } from '@/types';

const props = defineProps<{
    patients: PaginatedRme<PasienRanapRme>;
    loading?: boolean;
    dischargeStatuses: RmeOption[];
}>();

const emit = defineEmits<{
    periksa: [patient: PasienRanapRme];
    editDpjp: [patient: PasienRanapRme];
    pindahKamar: [patient: PasienRanapRme];
    batalRanap: [patient: PasienRanapRme];
    pulangkan: [patient: PasienRanapRme];
    editPulang: [patient: PasienRanapRme];
    ubahStatusPulang: [patient: PasienRanapRme, status: string];
    ttv: [patient: PasienRanapRme];
}>();

const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.patients.data.length,
    total: () => props.patients.total,
    loading: () => props.loading ?? false,
});

function varianJenisKelamin(jenisKelamin: string | null): BadgeVariants['variant'] {
    return jenisKelamin === 'L' ? 'soft-info' : jenisKelamin === 'P' ? 'soft-indigo' : 'muted';
}

function varianPulang(status: string): BadgeVariants['variant'] {
    if (status === '-') {
        return 'soft-success';
    }

    if (['Meninggal', 'Pulang Paksa', 'Atas Permintaan Sendiri'].includes(status)) {
        return 'soft-destructive';
    }

    if (status === 'Pindah Kamar') {
        return 'soft-warning';
    }

    return 'soft-info';
}

function isTerisi(value: 0 | 1 | boolean | string | null | undefined): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function sudahDiperiksaDokter(patient: PasienRanapRme): boolean {
    return isTerisi(patient.sudah_diperiksa_dokter);
}

function sudahDiperiksaPerawat(patient: PasienRanapRme): boolean {
    return isTerisi(patient.sudah_diperiksa_perawat);
}

function classStatusPemeriksaan(patient: PasienRanapRme): string | undefined {
    if (sudahDiperiksaDokter(patient)) {
        return '[&>td]:!bg-success/10 hover:[&>td]:!bg-success/15 dark:[&>td]:!bg-success/15 dark:hover:[&>td]:!bg-success/20';
    }

    if (sudahDiperiksaPerawat(patient)) {
        return '[&>td]:!bg-warning/15 hover:[&>td]:!bg-warning/20 dark:[&>td]:!bg-warning/20 dark:hover:[&>td]:!bg-warning/25';
    }

    return undefined;
}

function labelStatusPulang(status: string): string {
    return status === '-' ? 'Masih dirawat' : status;
}

function pasienMasihDirawat(patient: PasienRanapRme): boolean {
    return patient.stts_pulang === '-';
}

function daftarDpjp(value: string | null): RmeOption[] {
    if (!value) {
        return [];
    }

    return value.split('||').map((item) => {
        const [kode, nama] = item.split('::');

        return {
            value: kode,
            label: nama || kode,
        };
    });
}

function diagnosaSep(patient: PasienRanapRme): string {
    return patient.nmdiagnosaawal || patient.diagawal || '';
}
</script>

<template>
    <section class="flex min-w-0 flex-col gap-3">
        <div class="w-full max-w-full min-w-0 overflow-x-auto">
            <InfiniteScroll
                ref="infiniteScroll"
                data="patients"
                items-element="#rme-ranap-table-body"
                only-next
                manual
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-36 text-center">Aksi</TableHead>
                            <TableHead>No Rawat</TableHead>
                            <TableHead>Pasien</TableHead>
                            <TableHead>Waktu</TableHead>
                            <TableHead>Ruang</TableHead>
                            <TableHead>Dokter PJ</TableHead>
                            <TableHead>Penjamin / SEP</TableHead>
                            <TableHead>Diagnosa</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody id="rme-ranap-table-body">
                        <template v-if="loading">
                            <TableRow v-for="index in 6" :key="`loading-${index}`">
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-8 w-28 rounded-md" />
                                        <Skeleton class="h-8 w-28 rounded-md" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-5 w-36" /></TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-44" />
                                        <Skeleton class="h-3 w-52" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-5 w-28" /></TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-36" />
                                        <Skeleton class="h-3 w-28" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-5 w-44" /></TableCell>
                                <TableCell><Skeleton class="h-5 w-44" /></TableCell>
                                <TableCell><Skeleton class="h-5 w-52" /></TableCell>
                                <TableCell><Skeleton class="h-6 w-24 rounded-full" /></TableCell>
                            </TableRow>
                        </template>

                        <template v-else-if="patients.data.length">
                            <TableRow
                                v-for="patient in patients.data"
                                :key="patient.no_rawat"
                                class="align-middle"
                                :class="classStatusPemeriksaan(patient)"
                            >
                                <TableCell>
                                    <div class="grid min-w-32 justify-items-stretch gap-2">
                                        <Button type="button" size="sm" @click="emit('periksa', patient)">
                                            <Stethoscope class="size-4" />
                                            Periksa
                                        </Button>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button type="button" variant="secondary" size="sm">
                                                    <ScrollText class="size-4" />
                                                    Tindakan Lain
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-56" align="start">
                                                <DropdownMenuItem @click="emit('editDpjp', patient)">
                                                    <UserCog class="mr-2 size-4" />
                                                    Edit PJ Ranap
                                                </DropdownMenuItem>
                                                <DropdownMenuItem :disabled="!pasienMasihDirawat(patient)" @click="emit('pindahKamar', patient)">
                                                    <Bed class="mr-2 size-4" />
                                                    Pindah Kamar
                                                </DropdownMenuItem>
                                                <DropdownMenuItem :disabled="!pasienMasihDirawat(patient)" @click="emit('batalRanap', patient)">
                                                    <Trash2 class="mr-2 size-4" />
                                                    Batal Ranap
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="pasienMasihDirawat(patient)" @click="emit('pulangkan', patient)">
                                                    <LogOut class="mr-2 size-4" />
                                                    Pulangkan Pasien
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-else @click="emit('editPulang', patient)">
                                                    <PenLine class="mr-2 size-4" />
                                                    Edit Pulang Pasien
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-40 gap-1.5">
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
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
                                        <Badge variant="muted" size="sm" class="w-fit font-mono">
                                            RM {{ patient.no_rkm_medis }}
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-56 gap-2">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <UserRound class="size-4 text-muted-foreground" />
                                            <span class="font-semibold leading-tight">{{ patient.nm_pasien }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <Badge :variant="varianJenisKelamin(patient.jk)" size="sm">
                                                {{ labelJenisKelamin(patient.jk) }}
                                            </Badge>
                                            <Badge variant="soft-warning" size="sm">
                                                {{ hitungUmur(patient.tgl_lahir, patient.tgl_masuk) }}
                                            </Badge>
                                        </div>
                                        <Badge variant="soft-info" size="sm" class="max-w-52 justify-start font-mono">
                                            <IdCard class="size-3" />
                                            <span class="truncate">{{ patient.no_ktp || '-' }}</span>
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-32 gap-1.5 text-sm">
                                        <div>
                                            <span class="text-xs text-muted-foreground">Masuk</span>
                                            <p class="font-medium">{{ patient.tgl_masuk }}</p>
                                            <p class="text-xs text-muted-foreground">{{ patient.jam_masuk }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground">Keluar</span>
                                            <p class="font-medium">{{ patient.tgl_keluar && patient.tgl_keluar !== '0000-00-00' ? patient.tgl_keluar : '-' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ patient.jam_keluar && patient.jam_keluar !== '00:00:00' ? patient.jam_keluar : '-' }}</p>
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-44 gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 font-semibold">
                                            <DoorOpen class="size-4 text-muted-foreground" />
                                            {{ patient.nm_bangsal }}
                                        </span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <Badge variant="soft-primary" size="sm">
                                                <Bed class="size-3" />
                                                {{ patient.kd_kamar }}
                                            </Badge>
                                            <Badge variant="soft-indigo" size="sm">{{ patient.kelas }}</Badge>
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="flex min-w-48 max-w-56 flex-wrap gap-1.5">
                                        <Badge
                                            v-for="doctor in daftarDpjp(patient.dokter_pj_ranap)"
                                            :key="doctor.value"
                                            variant="soft-success"
                                            size="sm"
                                            class="max-w-full justify-start"
                                        >
                                            <Stethoscope class="size-3" />
                                            <span class="truncate">{{ doctor.label }}</span>
                                        </Badge>
                                        <Badge v-if="!daftarDpjp(patient.dokter_pj_ranap).length" variant="muted" size="sm">
                                            Belum ada PJ
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-56 gap-1.5">
                                        <div class="flex flex-wrap gap-1.5">
                                            <Badge variant="soft-indigo" size="sm">
                                                <CreditCard class="size-3" />
                                                {{ patient.png_jawab }}
                                            </Badge>
                                            <Badge :variant="patient.ada_sep ? 'soft-success' : 'muted'" size="sm" class="max-w-44 font-mono">
                                                <FileText class="size-3" />
                                                <span class="truncate">SEP: {{ patient.no_sep || '-' }}</span>
                                            </Badge>
                                        </div>
                                        <TooltipProvider v-if="diagnosaSep(patient)">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Badge variant="soft-info" size="sm" class="max-w-56 justify-start">
                                                        <span class="truncate">{{ diagnosaSep(patient) }}</span>
                                                    </Badge>
                                                </TooltipTrigger>
                                                <TooltipContent class="max-w-sm">
                                                    <p>{{ diagnosaSep(patient) }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="grid min-w-56 gap-1.5">
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="max-w-56 truncate text-sm">
                                                        Awal: {{ patient.diagnosa_awal || '-' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent class="max-w-sm">
                                                    <p>{{ patient.diagnosa_awal || '-' }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="max-w-56 truncate text-xs text-muted-foreground">
                                                        Akhir: {{ patient.diagnosa_akhir || '-' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent class="max-w-sm">
                                                    <p>{{ patient.diagnosa_akhir || '-' }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button type="button" variant="ghost" class="h-auto p-0">
                                                <Badge :variant="varianPulang(patient.stts_pulang)" size="sm">
                                                    {{ labelStatusPulang(patient.stts_pulang) }}
                                                </Badge>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-56" align="end">
                                            <DropdownMenuItem
                                                v-for="status in dischargeStatuses"
                                                :key="status.value"
                                                :disabled="status.value === patient.stts_pulang"
                                                @click="emit('ubahStatusPulang', patient, status.value)"
                                            >
                                                {{ status.label }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </template>

                        <TableEmpty
                            v-else
                            :colspan="9"
                            icon="search"
                            severity="info"
                            title="Pasien rawat inap tidak ditemukan"
                            description="Coba ubah tipe filter, rentang tanggal, ruang, atau kata kunci pencarian."
                        />
                    </TableBody>
                </Table>

                <template #next="{ loading: loadingNext, hasMore }">
                    <div v-if="loadingNext" class="space-y-2 pt-1">
                        <div v-for="index in 3" :key="index" class="grid grid-cols-[12%_14%_18%_11%_13%_14%_14%_14%_10%] items-center gap-3 px-2 py-3">
                            <Skeleton class="h-8 w-28 rounded-md" />
                            <Skeleton class="h-5 w-36" />
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-44" />
                                <Skeleton class="h-3 w-52" />
                            </div>
                            <Skeleton class="h-5 w-28" />
                            <Skeleton class="h-5 w-36" />
                            <Skeleton class="h-5 w-44" />
                            <Skeleton class="h-5 w-44" />
                            <Skeleton class="h-5 w-52" />
                            <Skeleton class="h-6 w-24 rounded-full" />
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
                <span class="font-semibold text-foreground">{{ patients.total }}</span> pasien
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
