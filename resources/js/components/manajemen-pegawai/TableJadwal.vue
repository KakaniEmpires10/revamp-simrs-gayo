<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import ScheduleClinicCombobox from '@/components/manajemen-pegawai/ComboboxPoliJadwal.vue';
import type { BadgeVariants } from '@/components/ui/badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useScrollTabelManual } from '@/composables/useScrollTabelManual';
import type { ClinicOption, Paginated, PracticeSchedule } from '@/types';

const props = defineProps<{
    schedules: Paginated<PracticeSchedule>;
    clinics: ClinicOption[];
    loading?: boolean;
}>();

defineEmits<{
    edit: [schedule: PracticeSchedule];
    delete: [schedule: PracticeSchedule];
}>();

const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.schedules.data.length,
    total: () => props.schedules.total,
    loading: () => props.loading ?? false,
});
const dayBadgeVariants: Record<string, BadgeVariants['variant']> = {
    SENIN: 'soft-primary',
    SELASA: 'soft-info',
    RABU: 'soft-success',
    KAMIS: 'soft-indigo',
    JUMAT: 'soft-warning',
    SABTU: 'soft-secondary',
    AKHAD: 'soft-destructive',
};
</script>

<template>
    <section class="flex flex-col gap-3">
    <InfiniteScroll ref="infiniteScroll" data="schedules" items-element="#practice-schedule-table-body" only-next manual>
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Dokter</TableHead>
                    <TableHead>Poliklinik</TableHead>
                    <TableHead class="text-center">Hari</TableHead>
                    <TableHead class="text-center">Jam</TableHead>
                    <TableHead class="text-center">Kuota</TableHead>
                    <TableHead class="text-center">Manajemen</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody id="practice-schedule-table-body">
                <template v-if="loading">
                    <TableRow v-for="index in 6" :key="`loading-${index}`">
                        <TableCell>
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-44" />
                                <Skeleton class="h-3 w-24" />
                            </div>
                        </TableCell>
                        <TableCell><Skeleton class="h-10 w-full rounded-lg" /></TableCell>
                        <TableCell><Skeleton class="mx-auto h-6 w-20 rounded-full" /></TableCell>
                        <TableCell><Skeleton class="mx-auto h-4 w-28" /></TableCell>
                        <TableCell><Skeleton class="mx-auto h-4 w-10" /></TableCell>
                        <TableCell>
                            <div class="flex justify-center gap-2">
                                <Skeleton class="h-9 w-9 rounded-lg" />
                                <Skeleton class="h-9 w-9 rounded-lg" />
                            </div>
                        </TableCell>
                    </TableRow>
                </template>

                <template v-else-if="schedules.data.length">
                    <TableRow
                        v-for="schedule in schedules.data"
                        :key="`${schedule.kd_dokter}-${schedule.hari_kerja}-${schedule.jam_mulai}`"
                    >
                        <TableCell>
                            <p class="font-semibold text-foreground">{{ schedule.nm_dokter }}</p>
                            <p class="font-mono text-xs text-muted-foreground">{{ schedule.kd_dokter }}</p>
                        </TableCell>
                        <TableCell>
                            <ScheduleClinicCombobox :schedule="schedule" :clinics="clinics" />
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge :variant="dayBadgeVariants[schedule.hari_kerja] ?? 'soft-info'" size="sm">
                                {{ schedule.hari_kerja }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-center font-mono">
                            {{ schedule.jam_mulai?.slice(0, 5) }} - {{ schedule.jam_selesai?.slice(0, 5) || '--:--' }}
                        </TableCell>
                        <TableCell class="text-center">{{ schedule.kuota ?? '-' }}</TableCell>
                        <TableCell>
                            <div class="flex justify-center gap-2">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" class="h-9 w-9 rounded-lg" @click="$emit('edit', schedule)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Ubah jadwal</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Ubah jadwal</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" variant="soft-destructive" class="h-9 w-9 rounded-lg" @click="$emit('delete', schedule)">
                                            <Trash2 class="size-4" />
                                            <span class="sr-only">Hapus jadwal</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Hapus jadwal</TooltipContent>
                                </Tooltip>
                            </div>
                        </TableCell>
                    </TableRow>
                </template>
                <TableEmpty
                    v-else
                    :colspan="6"
                    icon="search"
                    severity="info"
                    title="Jadwal praktek tidak ditemukan"
                    description="Coba ubah filter poli, dokter, atau hari untuk menemukan jadwal praktek."
                />
            </TableBody>
        </Table>

        <template #next="{ loading: loadingNext, hasMore }">
            <div v-if="loadingNext" class="space-y-2 pt-1">
                <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                    <div class="grid grid-cols-[23%_25%_13%_16%_8%_15%] items-center gap-2">
                        <div class="space-y-2">
                            <Skeleton class="h-4 w-44" />
                            <Skeleton class="h-3 w-24" />
                        </div>
                        <Skeleton class="h-10 w-full rounded-lg" />
                        <Skeleton class="mx-auto h-6 w-20 rounded-full" />
                        <Skeleton class="mx-auto h-4 w-28" />
                        <Skeleton class="mx-auto h-4 w-10" />
                        <div class="flex justify-start gap-2">
                            <Skeleton class="h-9 w-9 rounded-lg" />
                            <Skeleton class="h-9 w-9 rounded-lg" />
                        </div>
                    </div>
                </div>
            </div>
            <p v-else-if="!hasMore && schedules.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                Semua data sudah ditampilkan.
            </p>
        </template>
    </InfiniteScroll>

        <div class="flex items-center justify-between px-1">
            <p class="text-sm text-muted-foreground">
                <template v-if="loading">Mengambil data terbaru...</template>
                <template v-else>Ditampilkan <span class="font-semibold text-foreground">{{ schedules.data.length }}</span> dari
                <span class="font-semibold text-foreground">{{ schedules.total }}</span> jadwal
                </template>
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
