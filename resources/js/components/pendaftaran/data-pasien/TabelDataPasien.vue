<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Edit3, GitMerge, Trash2 } from '@lucide/vue';
import { edit as editDataPasien } from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { hitungUmur, labelJenisKelamin } from '@/lib/pasien';
import type { PaginatedResponse, RegistrationPatient } from '@/types';

defineProps<{
    patients: PaginatedResponse<RegistrationPatient>;
}>();

const emit = defineEmits<{
    hapus: [patient: RegistrationPatient];
    gabungRm: [patient: RegistrationPatient];
}>();

function genderVariant(jenisKelamin: string | null | undefined): 'soft-info' | 'soft-warning' | 'muted' {
    if (jenisKelamin === 'L') {
        return 'soft-info';
    }

    if (jenisKelamin === 'P') {
        return 'soft-warning';
    }

    return 'muted';
}

function teksWilayahPj(patient: RegistrationPatient): string {
    return [
        patient.kelurahanpj,
        patient.kecamatanpj,
        patient.kabupatenpj,
    ].filter(Boolean).join(', ') || '-';
}
</script>

<template>
    <section class="grid gap-4">
        <div class="w-full max-w-full min-w-0 overflow-x-auto">
            <Table class="min-w-285">
                <TableHeader>
                    <TableRow>
                        <TableHead>No RM</TableHead>
                        <TableHead>Pasien</TableHead>
                        <TableHead>Identitas</TableHead>
                        <TableHead>Kontak & Alamat</TableHead>
                        <TableHead>Penanggung Jawab</TableHead>
                        <TableHead class="text-center">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="patient in patients.data" :key="patient.no_rkm_medis" class="align-middle">
                        <TableCell class="align-middle font-mono text-base font-semibold text-primary">
                            {{ patient.no_rkm_medis }}
                        </TableCell>
                        <TableCell class="min-w-0 align-middle">
                            <div class="grid gap-1.5">
                                <p class="truncate font-medium text-foreground">
                                    {{ patient.nm_pasien }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge :variant="genderVariant(patient.jk)" size="sm">
                                        {{ labelJenisKelamin(patient.jk) }}
                                    </Badge>
                                    <Badge variant="soft-primary" size="sm">
                                        Usia: {{ hitungUmur(patient.tgl_lahir) }}
                                    </Badge>
                                    <Badge variant="muted" size="sm">
                                        Daftar: {{ patient.umur || '-' }}
                                    </Badge>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="align-middle">
                            <div class="flex max-w-90 flex-wrap gap-1.5">
                                <Badge variant="soft-info" size="sm">
                                    NIK: {{ patient.no_ktp || '-' }}
                                </Badge>
                                <Badge variant="soft-success" size="sm">
                                    BPJS: {{ patient.no_peserta || '-' }}
                                </Badge>
                                <Badge variant="soft-warning" size="sm">
                                    {{ patient.png_jawab || patient.kd_pj || '-' }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell class="min-w-0 align-middle">
                            <div class="grid gap-1 text-sm">
                                <p class="truncate">
                                    <span class="text-muted-foreground">Telepon:</span>
                                    <span class="ml-1 font-medium">{{ patient.no_tlp || '-' }}</span>
                                </p>
                                <p class="line-clamp-2 max-w-80">
                                    <span class="text-muted-foreground">Alamat:</span>
                                    <span class="ml-1 font-medium">{{ patient.alamat || '-' }}</span>
                                </p>
                            </div>
                        </TableCell>
                        <TableCell class="min-w-0 align-middle">
                            <div class="grid gap-1 text-sm">
                                <p class="truncate font-medium">
                                    {{ patient.namakeluarga || '-' }}
                                </p>
                                <p class="truncate text-muted-foreground">
                                    {{ patient.keluarga || '-' }}
                                </p>
                                <p class="line-clamp-2 max-w-80 text-muted-foreground">
                                    {{ patient.alamatpj || '-' }}
                                </p>
                                <p class="line-clamp-2 max-w-80 text-muted-foreground">
                                    {{ teksWilayahPj(patient) }}
                                </p>
                            </div>
                        </TableCell>
                        <TableCell class="align-middle">
                            <div class="flex justify-center gap-2">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button as-child type="button" variant="outline" size="icon" class="size-8">
                                                <Link :href="editDataPasien(patient.no_rkm_medis)">
                                                    <Edit3 class="size-4" />
                                                    <span class="sr-only">Edit pasien</span>
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Edit pasien</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="soft-primary"
                                                size="icon"
                                                class="size-8"
                                                @click="emit('gabungRm', patient)"
                                            >
                                                <GitMerge class="size-4" />
                                                <span class="sr-only">Gabung RM</span>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Gabung RM</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="soft-destructive"
                                                size="icon"
                                                class="size-8"
                                                @click="emit('hapus', patient)"
                                            >
                                                <Trash2 class="size-4" />
                                                <span class="sr-only">Hapus pasien</span>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Hapus pasien</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableEmpty v-if="!patients.data.length" :colspan="6" icon="search">
                        Data pasien tidak ditemukan.
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
            <p class="text-muted-foreground">
                Menampilkan {{ patients.from || 0 }}-{{ patients.to || 0 }} dari {{ patients.total }} pasien
            </p>
            <div class="flex flex-wrap gap-1">
                <Button
                    v-for="link in patients.links"
                    :key="link.label"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                >
                    <Link :href="link.url || '#'" preserve-scroll preserve-state>
                        <span v-html="link.label" />
                    </Link>
                </Button>
            </div>
        </div>
    </section>
</template>
