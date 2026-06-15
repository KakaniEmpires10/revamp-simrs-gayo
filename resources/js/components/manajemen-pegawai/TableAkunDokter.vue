<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { KeyRound, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { updateStatus } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunDokterController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
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
import { useFeedback } from '@/composables/useFeedback';
import { useScrollTabelManual } from '@/composables/useScrollTabelManual';
import type { DoctorAccount, Paginated } from '@/types';

const props = defineProps<{
    doctors: Paginated<DoctorAccount>;
    loading?: boolean;
}>();

defineEmits<{
    editProfile: [doctor: DoctorAccount];
    editAccount: [doctor: DoctorAccount];
    deleteAccount: [doctor: DoctorAccount];
}>();

const statusOverrides = ref<Record<string, '0' | '1'>>({});
const processingStatusIds = ref<Set<string>>(new Set());
const feedback = useFeedback();
const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.doctors.data.length,
    total: () => props.doctors.total,
    loading: () => props.loading ?? false,
});

function doctorStatus(doctor: DoctorAccount): '0' | '1' {
    return statusOverrides.value[doctor.kd_dokter] ?? doctor.status;
}

function setStatusProcessing(doctorId: string, processing: boolean): void {
    const next = new Set(processingStatusIds.value);

    if (processing) {
        next.add(doctorId);
    } else {
        next.delete(doctorId);
    }

    processingStatusIds.value = next;
}

function isStatusProcessing(doctorId: string): boolean {
    return processingStatusIds.value.has(doctorId);
}

function toggleDoctorStatus(doctor: DoctorAccount, checked: boolean | 'indeterminate'): void {
    if (checked === 'indeterminate' || isStatusProcessing(doctor.kd_dokter)) {
        return;
    }

    const previousStatus = doctorStatus(doctor);
    const nextStatus = checked ? '1' : '0';

    statusOverrides.value = {
        ...statusOverrides.value,
        [doctor.kd_dokter]: nextStatus,
    };
    setStatusProcessing(doctor.kd_dokter, true);

    router.patch(
        updateStatus.url(doctor.kd_dokter),
        { status: nextStatus },
        {
            only: ['doctors'],
            preserveScroll: true,
            onSuccess: () => {
                feedback.success(
                    nextStatus === '1'
                        ? `${doctor.nm_dokter} berhasil diaktifkan.`
                        : `${doctor.nm_dokter} berhasil dinonaktifkan.`,
                );
            },
            onError: () => {
                statusOverrides.value = {
                    ...statusOverrides.value,
                    [doctor.kd_dokter]: previousStatus,
                };
                feedback.error('Status dokter gagal diubah. Coba lagi.');
            },
            onNetworkError: () => {
                statusOverrides.value = {
                    ...statusOverrides.value,
                    [doctor.kd_dokter]: previousStatus,
                };
                feedback.error('Koneksi bermasalah. Status dokter belum berhasil diubah.');
            },
            onFinish: () => {
                setStatusProcessing(doctor.kd_dokter, false);
            },
        },
    );
}

</script>

<template>
    <section class="flex flex-col gap-3">
        <InfiniteScroll ref="infiniteScroll" data="doctors" items-element="#doctor-account-table-body" only-next manual>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            Kode
                        </TableHead>
                        <TableHead>
                            Dokter
                        </TableHead>
                        <TableHead>
                            Spesialis
                        </TableHead>
                        <TableHead class="text-center">
                            Status
                        </TableHead>
                        <TableHead class="text-center">
                            Akun
                        </TableHead>
                        <TableHead class="text-center">
                            Manajemen
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody id="doctor-account-table-body">
                    <template v-if="loading">
                        <TableRow v-for="index in 6" :key="`loading-${index}`">
                            <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                            <TableCell>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-44" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-36" />
                                    <Skeleton class="h-3 w-24" />
                                </div>
                            </TableCell>
                            <TableCell><Skeleton class="mx-auto h-5 w-8 rounded-full" /></TableCell>
                            <TableCell><Skeleton class="mx-auto h-6 w-20 rounded-full" /></TableCell>
                            <TableCell>
                                <div class="flex justify-center gap-2">
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="doctors.data.length">
                        <TableRow v-for="doctor in doctors.data" :key="doctor.kd_dokter">
                        <TableCell class="font-mono text-base font-semibold text-primary">
                            {{ doctor.kd_dokter }}
                        </TableCell>
                        <TableCell>
                            <p class="font-semibold text-foreground">{{ doctor.nm_dokter }}</p>
                            <p class="text-xs text-muted-foreground">KTP: {{ doctor.no_ktp || '-' }}</p>
                        </TableCell>
                        <TableCell>
                            <p class="font-medium">{{ doctor.nm_sps || '-' }}</p>
                            <p class="text-xs text-muted-foreground">{{ doctor.no_ijn_praktek || '-' }}</p>
                        </TableCell>
                        <TableCell class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Switch
                                            :model-value="doctorStatus(doctor) === '1'"
                                            :disabled="isStatusProcessing(doctor.kd_dokter)"
                                            class="data-[state=checked]:!border-success data-[state=checked]:!bg-success data-[state=unchecked]:!border-destructive data-[state=unchecked]:!bg-destructive"
                                            thumb-class="data-[state=checked]:!bg-success-foreground data-[state=unchecked]:!bg-destructive-foreground"
                                            @update:model-value="toggleDoctorStatus(doctor, $event)"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{ doctorStatus(doctor) === '1' ? 'Nonaktifkan dokter' : 'Aktifkan dokter' }}
                                    </TooltipContent>
                                </Tooltip>
                                <span
                                    class="min-w-14 text-left text-xs font-medium"
                                    :class="doctorStatus(doctor) === '1' ? 'text-success' : 'text-destructive'"
                                >
                                    {{ doctorStatus(doctor) === '1' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge
                                size="sm"
                                :variant="doctor.password_decrypted ? 'soft-success' : 'soft-warning'"
                            >
                                {{ doctor.password_decrypted ? 'Aktif' : 'Belum Ada' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex justify-center gap-2">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" variant="secondary" class="h-9 w-9 rounded-lg" @click="$emit('editProfile', doctor)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit data dokter</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Edit data dokter</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" class="h-9 w-9 rounded-lg" @click="$emit('editAccount', doctor)">
                                            <KeyRound class="size-4" />
                                            <span class="sr-only">Kelola akun login</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Kelola akun login</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="icon"
                                            variant="soft-destructive"
                                            class="h-9 w-9 rounded-lg"
                                            :disabled="!doctor.password_decrypted"
                                            @click="$emit('deleteAccount', doctor)"
                                        >
                                            <Trash2 class="size-4" />
                                            <span class="sr-only">Hapus akun login</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Hapus akun login</TooltipContent>
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
                        title="Data dokter tidak ditemukan"
                        description="Coba ubah kata kunci, filter status, atau filter akun untuk menemukan data dokter."
                    />
                </TableBody>
            </Table>

            <template #next="{ loading: loadingNext, hasMore }">
                <div v-if="loadingNext" class="space-y-2 pt-1">
                    <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                        <div class="grid grid-cols-[16%_26%_22%_10%_10%_16%] items-center gap-2">
                            <Skeleton class="h-5 w-24" />
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-44" />
                                <Skeleton class="h-3 w-28" />
                            </div>
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-36" />
                                <Skeleton class="h-3 w-24" />
                            </div>
                            <Skeleton class="mx-auto h-5 w-8 rounded-full" />
                            <Skeleton class="mx-auto h-6 w-20 rounded-full" />
                            <div class="flex justify-start gap-2">
                                <Skeleton class="h-9 w-9 rounded-lg" />
                                <Skeleton class="h-9 w-9 rounded-lg" />
                                <Skeleton class="h-9 w-9 rounded-lg" />
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else-if="!hasMore && doctors.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                    Semua data sudah ditampilkan.
                </p>
            </template>
        </InfiniteScroll>

        <div class="flex items-center justify-between px-1">
            <p class="text-sm text-muted-foreground">
                <template v-if="loading">Mengambil data terbaru...</template>
                <template v-else>Ditampilkan <span class="font-semibold text-foreground">{{ doctors.data.length }}</span> dari
                <span class="font-semibold text-foreground">{{ doctors.total }}</span> dokter
                </template>
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
