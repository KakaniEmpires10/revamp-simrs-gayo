<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { Activity, AlertTriangle, ArrowDownNarrowWide, ArrowRightLeft, ArrowUpNarrowWide, Bed, ChevronDown, ChevronRight, CreditCard, FileCheck2, FileText, FlaskConical, Hospital, IdCard, Pencil, Pill, ScrollText, Stethoscope, UserRound, Waves } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import type { PaginatedRme, PasienRalanRme } from '@/types';

const props = defineProps<{
    patients: PaginatedRme<PasienRalanRme>;
    loading?: boolean;
    dataKey?: string;
    emptyTitle?: string;
    order?: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    ubahOrder: [order: 'asc' | 'desc'];
    periksa: [patient: PasienRalanRme];
    pindahRawatInap: [patient: PasienRalanRme];
    rujukInternal: [patient: PasienRalanRme];
    ubahDokter: [patient: PasienRalanRme];
    ubahDiagnosa: [patient: PasienRalanRme];
    ttv: [patient: PasienRalanRme];
}>();

const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.patients.data.length,
    total: () => props.patients.total,
    loading: () => props.loading ?? false,
});
const expandedRows = ref<Set<string>>(new Set());

const itemDokumenRme = computed(() => [
    { key: 'ada_sep', label: 'SEP', icon: FileText, wajib: true },
    { key: 'ada_cppt', label: 'CPPT', icon: Activity, wajib: true },
    { key: 'ada_resume', label: 'Resume', icon: FileCheck2, wajib: true },
    { key: 'ada_lab', label: 'Lab', icon: FlaskConical, wajib: false },
    { key: 'ada_radiologi', label: 'Radiologi', icon: FileCheck2, wajib: false },
    { key: 'ada_fisioterapi', label: 'Fisioterapi', icon: Waves, wajib: false },
    { key: 'ada_resep', label: 'Resep', icon: Pill, wajib: false },
    { key: 'ada_tindakan', label: 'Tindakan', icon: Stethoscope, wajib: false },
] as const);
const semuaTerbuka = computed(() => {
    if (!props.patients.data.length) {
        return false;
    }

    return props.patients.data.every((patient) => expandedRows.value.has(patient.no_rawat));
});

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

function varianJenisKelamin(jenisKelamin: string | null): BadgeVariants['variant'] {
    return jenisKelamin === 'L' ? 'soft-info' : jenisKelamin === 'P' ? 'soft-indigo' : 'muted';
}

function isRanap(patient: PasienRalanRme): boolean {
    return patient.is_ranap === true || patient.is_ranap === 1;
}

function isTerisi(value: 0 | 1 | boolean | string | null | undefined): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function punyaSep(patient: PasienRalanRme): boolean {
    return isTerisi(patient.ada_sep) || Boolean(patient.no_sep);
}

function isBpjs(patient: PasienRalanRme): boolean {
    return patient.png_jawab.toLowerCase().includes('bpjs') || Boolean(patient.no_peserta);
}

function labelDiagnosaIgd(patient: PasienRalanRme): string {
    if (!patient.kd_penyakit_igd) {
        return 'Diagnosa belum ada';
    }

    return `${patient.kd_penyakit_igd}: ${patient.nm_penyakit_igd || '-'}`;
}

function sudahDiperiksaDokter(patient: PasienRalanRme): boolean {
    return isTerisi(patient.sudah_diperiksa_dokter);
}

function sudahDiperiksaPerawat(patient: PasienRalanRme): boolean {
    return isTerisi(patient.sudah_diperiksa_perawat);
}

function classStatusPemeriksaan(patient: PasienRalanRme): string | undefined {
    if (sudahDiperiksaDokter(patient)) {
        return '[&>td]:!bg-success/10 hover:[&>td]:!bg-success/15 dark:[&>td]:!bg-success/15 dark:hover:[&>td]:!bg-success/20';
    }

    if (sudahDiperiksaPerawat(patient)) {
        return '[&>td]:!bg-warning/15 hover:[&>td]:!bg-warning/20 dark:[&>td]:!bg-warning/20 dark:hover:[&>td]:!bg-warning/25';
    }

    if (isRanap(patient)) {
        return '[&>td]:!bg-warning/10 hover:[&>td]:!bg-warning/15';
    }

    return undefined;
}

function isExpanded(noRawat: string): boolean {
    return expandedRows.value.has(noRawat);
}

function toggleDetail(noRawat: string): void {
    const next = new Set(expandedRows.value);

    if (next.has(noRawat)) {
        next.delete(noRawat);
    } else {
        next.add(noRawat);
    }

    expandedRows.value = next;
}

function jumlahWajibTerisi(patient: PasienRalanRme): number {
    return itemDokumenRme.value.filter((item) => item.wajib && isTerisi(patient[item.key])).length;
}

function itemWajibCount(): number {
    return itemDokumenRme.value.filter((item) => item.wajib).length;
}

function itemOpsionalTerisiCount(patient: PasienRalanRme): number {
    return itemDokumenRme.value.filter((item) => !item.wajib && isTerisi(patient[item.key])).length;
}

function varianDokumen(patient: PasienRalanRme, item: typeof itemDokumenRme.value[number]): BadgeVariants['variant'] {
    if (isTerisi(patient[item.key])) {
        return item.wajib ? 'soft-success' : 'soft-info';
    }

    return item.wajib ? 'soft-warning' : 'muted';
}

function toggleOrder(): void {
    emit('ubahOrder', props.order === 'desc' ? 'asc' : 'desc');
}

function toggleSemuaDetail(): void {
    if (semuaTerbuka.value) {
        expandedRows.value = new Set();

        return;
    }

    expandedRows.value = new Set(props.patients.data.map((patient) => patient.no_rawat));
}
</script>

<template>
    <section class="flex min-w-0 flex-col gap-3">
        <div v-if="!loading && patients.data.length" class="flex items-center gap-2 px-1 text-xs italic text-muted-foreground">
            <ChevronRight class="size-3.5" />
            <span>Catatan: buka baris pasien untuk melihat kelengkapan data RME.</span>
        </div>

        <div class="w-full max-w-full min-w-0 overflow-x-auto">
            <InfiniteScroll
                ref="infiniteScroll"
                :data="dataKey ?? 'patients'"
                items-element="#rme-igd-table-body"
                only-next
                manual
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-10">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                :disabled="!patients.data.length"
                                                :aria-label="semuaTerbuka ? 'Tutup semua kelengkapan RME' : 'Buka semua kelengkapan RME'"
                                                @click="toggleSemuaDetail"
                                            >
                                                <component :is="semuaTerbuka ? ChevronDown : ChevronRight" class="size-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ semuaTerbuka ? 'Tutup semua baris yang termuat' : 'Buka semua baris yang termuat' }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableHead>
                            <TableHead class="w-44 text-center">Aksi</TableHead>
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
                                                :aria-label="order === 'desc' ? 'Urutkan dari pasien paling awal' : 'Urutkan dari pasien terbaru'"
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
                            <TableHead>Dokter / Poli</TableHead>
                            <TableHead>Penjamin / SEP</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody id="rme-igd-table-body">
                        <template v-if="loading">
                            <TableRow v-for="index in 6" :key="`loading-${index}`">
                                <TableCell><Skeleton class="size-8 rounded-md" /></TableCell>
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
                                <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-36" />
                                        <Skeleton class="h-3 w-28" />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <Skeleton class="h-5 w-28 rounded-full" />
                                        <Skeleton class="h-3 w-40" />
                                    </div>
                                </TableCell>
                                <TableCell><Skeleton class="h-6 w-20 rounded-full" /></TableCell>
                            </TableRow>
                        </template>

                        <template v-else-if="patients.data.length">
                            <template
                                v-for="patient in patients.data"
                                :key="patient.no_rawat"
                            >
                                <TableRow
                                    class="align-middle"
                                    :class="[
                                        classStatusPemeriksaan(patient),
                                        isExpanded(patient.no_rawat) ? 'border-b-transparent' : undefined,
                                    ]"
                                >
                                    <TableCell>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        class="size-8"
                                                        :aria-expanded="isExpanded(patient.no_rawat)"
                                                        :aria-label="isExpanded(patient.no_rawat) ? 'Sembunyikan kelengkapan RME' : 'Tampilkan kelengkapan RME'"
                                                        @click="toggleDetail(patient.no_rawat)"
                                                    >
                                                        <component :is="isExpanded(patient.no_rawat) ? ChevronDown : ChevronRight" class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    {{ isExpanded(patient.no_rawat) ? 'Sembunyikan kelengkapan RME' : 'Lihat kelengkapan RME' }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </TableCell>
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
                                                    <DropdownMenuItem @click="emit('rujukInternal', patient)">
                                                        <ArrowRightLeft class="mr-2 size-4" />
                                                        Rujuk Internal
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

                                            <div class="flex flex-wrap gap-1">
                                                <Badge variant="soft-primary" size="sm" class="font-mono">
                                                    Reg {{ patient.no_reg }}
                                                </Badge>
                                                <Badge variant="muted" size="sm" class="font-mono">
                                                    RM {{ patient.no_rkm_medis }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="grid min-w-60 gap-2">
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
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <Badge variant="soft-info" size="sm" class="max-w-56 font-mono">
                                                    <IdCard class="size-3" />
                                                    <span class="truncate">{{ patient.no_ktp || '-' }}</span>
                                                </Badge>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="grid min-w-32 gap-1">
                                            <span class="font-medium">{{ patient.tgl_registrasi }}</span>
                                            <span class="text-xs text-muted-foreground">{{ patient.jam_reg }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="grid min-w-48 gap-2">
                                            <div class="grid gap-1">
                                                <span class="inline-flex items-center gap-1.5 text-[15px] font-semibold leading-tight">
                                                    <Stethoscope class="size-4 text-muted-foreground" />
                                                    {{ patient.nm_dokter }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <Hospital class="size-3.5" />
                                                    {{ patient.nm_poli }}
                                                </span>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                class="w-fit"
                                                @click="emit('ubahDokter', patient)"
                                            >
                                                <Pencil class="size-3.5" />
                                                Ganti dokter
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="grid min-w-60 gap-1.5">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <Badge variant="soft-indigo" size="sm">
                                                    <CreditCard class="size-3" />
                                                    {{ patient.png_jawab }}
                                                </Badge>
                                                <Badge variant="muted" size="sm" class="max-w-44 font-mono">
                                                    <span class="truncate">SEP: {{ patient.no_sep || '-' }}</span>
                                                </Badge>
                                            </div>
                                            <TooltipProvider v-if="punyaSep(patient) && patient.nmdiagnosaawal">
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Badge variant="soft-info" size="sm" class="max-w-60 justify-start">
                                                            <span class="truncate">Diagnosa SEP: {{ patient.nmdiagnosaawal }}</span>
                                                        </Badge>
                                                    </TooltipTrigger>
                                                    <TooltipContent class="max-w-sm">
                                                        <p>{{ patient.nmdiagnosaawal }}</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            <Button
                                                v-else
                                                type="button"
                                                variant="ghost"
                                                class="h-auto max-w-60 justify-start px-0 py-0 text-left"
                                                @click="emit('ubahDiagnosa', patient)"
                                            >
                                                <Badge
                                                    :variant="patient.kd_penyakit_igd ? 'soft-success' : 'soft-warning'"
                                                    size="sm"
                                                    class="max-w-60 justify-start"
                                                >
                                                    <span class="truncate">{{ isBpjs(patient) ? (patient.no_sep ? labelDiagnosaIgd(patient) : `SEP belum dibuat · ${labelDiagnosaIgd(patient)}`) : labelDiagnosaIgd(patient) }}</span>
                                                </Badge>
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="grid min-w-36 gap-1.5">
                                            <Badge :variant="varianStatus(patient.stts)" size="sm">
                                                {{ patient.stts }}
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

                                <TableRow
                                    v-if="isExpanded(patient.no_rawat)"
                                    class="bg-muted/20 hover:bg-muted/20"
                                >
                                    <TableCell />
                                    <TableCell :colspan="7" class="py-3">
                                        <div class="grid gap-3 rounded-md border border-dashed bg-background/80 p-3">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground">Kelengkapan data RME</p>
                                                    <p class="text-xs text-muted-foreground">
                                                        Wajib {{ jumlahWajibTerisi(patient) }} dari {{ itemWajibCount() }} terisi. Opsional {{ itemOpsionalTerisiCount(patient) }} item tersedia.
                                                    </p>
                                                </div>
                                                <Badge :variant="jumlahWajibTerisi(patient) === itemWajibCount() ? 'soft-success' : 'soft-warning'" size="sm">
                                                    {{ jumlahWajibTerisi(patient) === itemWajibCount() ? 'Data wajib lengkap' : 'Data wajib belum lengkap' }}
                                                </Badge>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <Badge
                                                    v-for="item in itemDokumenRme"
                                                    :key="item.key"
                                                    :variant="varianDokumen(patient, item)"
                                                    size="sm"
                                                    rounded="md"
                                                    class="h-7 px-2.5"
                                                >
                                                    <component :is="item.icon" class="size-3.5" />
                                                    {{ item.label }}
                                                    <span class="text-[10px] opacity-80">
                                                        {{ isTerisi(patient[item.key]) ? 'Terisi' : 'Kosong' }}
                                                    </span>
                                                </Badge>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </template>

                        <TableEmpty
                            v-else
                            :colspan="8"
                            icon="search"
                            severity="info"
                            :title="emptyTitle ?? 'Pasien rawat jalan tidak ditemukan'"
                            description="Coba ubah rentang tanggal, poli, atau kata kunci pencarian."
                        />
                    </TableBody>
                </Table>

                <template #next="{ loading: loadingNext, hasMore }">
                    <div v-if="loadingNext" class="space-y-2 pt-1">
                        <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                            <div class="grid grid-cols-[4%_12%_14%_22%_10%_14%_16%_8%] items-center gap-3">
                                <Skeleton class="h-5 w-32" />
                                <div class="space-y-2">
                                    <Skeleton class="h-8 w-28 rounded-md" />
                                    <Skeleton class="h-8 w-28 rounded-md" />
                                </div>
                                <Skeleton class="h-5 w-36" />
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
                                    <Skeleton class="h-5 w-28 rounded-full" />
                                    <Skeleton class="h-3 w-40" />
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
                <span class="font-semibold text-foreground">{{ patients.total }}</span> pasien
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
