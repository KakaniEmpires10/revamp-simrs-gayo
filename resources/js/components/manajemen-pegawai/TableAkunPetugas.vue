<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { KeyRound, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { updateStatus } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunPetugasController';
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
import type { Paginated, StaffAccount } from '@/types';

const props = defineProps<{
    staff: Paginated<StaffAccount>;
    loading?: boolean;
}>();

defineEmits<{
    editProfile: [staff: StaffAccount];
    editAccount: [staff: StaffAccount];
    deleteAccount: [staff: StaffAccount];
}>();

const statusOverrides = ref<Record<string, '0' | '1'>>({});
const processingStatusIds = ref<Set<string>>(new Set());
const feedback = useFeedback();
const { infiniteScroll, isFetchingNext } = useScrollTabelManual({
    itemCount: () => props.staff.data.length,
    total: () => props.staff.total,
    loading: () => props.loading ?? false,
});

function staffStatus(staff: StaffAccount): '0' | '1' {
    return statusOverrides.value[staff.nip] ?? staff.status;
}

function setStatusProcessing(staffId: string, processing: boolean): void {
    const next = new Set(processingStatusIds.value);

    if (processing) {
        next.add(staffId);
    } else {
        next.delete(staffId);
    }

    processingStatusIds.value = next;
}

function isStatusProcessing(staffId: string): boolean {
    return processingStatusIds.value.has(staffId);
}

function toggleStaffStatus(staff: StaffAccount, checked: boolean | 'indeterminate'): void {
    if (checked === 'indeterminate' || isStatusProcessing(staff.nip)) {
        return;
    }

    const previousStatus = staffStatus(staff);
    const nextStatus = checked ? '1' : '0';

    statusOverrides.value = {
        ...statusOverrides.value,
        [staff.nip]: nextStatus,
    };
    setStatusProcessing(staff.nip, true);

    router.patch(
        updateStatus.url(staff.nip),
        { status: nextStatus },
        {
            only: ['staff'],
            preserveScroll: true,
            onSuccess: () => {
                feedback.success(
                    nextStatus === '1'
                        ? `${staff.nama} berhasil diaktifkan.`
                        : `${staff.nama} berhasil dinonaktifkan.`,
                );
            },
            onError: () => {
                statusOverrides.value = {
                    ...statusOverrides.value,
                    [staff.nip]: previousStatus,
                };
                feedback.error('Status petugas gagal diubah. Coba lagi.');
            },
            onNetworkError: () => {
                statusOverrides.value = {
                    ...statusOverrides.value,
                    [staff.nip]: previousStatus,
                };
                feedback.error('Koneksi bermasalah. Status petugas belum berhasil diubah.');
            },
            onFinish: () => {
                setStatusProcessing(staff.nip, false);
            },
        },
    );
}

</script>

<template>
    <section class="flex flex-col gap-3">
        <InfiniteScroll ref="infiniteScroll" data="staff" items-element="#staff-account-table-body" only-next manual>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            NIP
                        </TableHead>
                        <TableHead>
                            Petugas
                        </TableHead>
                        <TableHead>
                            Jabatan
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
                <TableBody id="staff-account-table-body">
                    <template v-if="loading">
                        <TableRow v-for="index in 6" :key="`loading-${index}`">
                            <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                            <TableCell>
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-44" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                            </TableCell>
                            <TableCell><Skeleton class="h-4 w-36" /></TableCell>
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

                    <template v-else-if="staff.data.length">
                        <TableRow v-for="person in staff.data" :key="person.nip">
                        <TableCell class="font-mono text-base font-semibold text-primary">
                            {{ person.nip }}
                        </TableCell>
                        <TableCell>
                            <p class="font-semibold text-foreground">{{ person.nama }}</p>
                            <p class="text-xs text-muted-foreground">KTP: {{ person.no_ktp || '-' }}</p>
                        </TableCell>
                        <TableCell>{{ person.nm_jbtn || '-' }}</TableCell>
                        <TableCell class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Switch
                                            :model-value="staffStatus(person) === '1'"
                                            :disabled="isStatusProcessing(person.nip)"
                                            class="data-[state=checked]:!border-success data-[state=checked]:!bg-success data-[state=unchecked]:!border-destructive data-[state=unchecked]:!bg-destructive"
                                            thumb-class="data-[state=checked]:!bg-success-foreground data-[state=unchecked]:!bg-destructive-foreground"
                                            @update:model-value="toggleStaffStatus(person, $event)"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{ staffStatus(person) === '1' ? 'Nonaktifkan petugas' : 'Aktifkan petugas' }}
                                    </TooltipContent>
                                </Tooltip>
                                <span
                                    class="min-w-14 text-left text-xs font-medium"
                                    :class="staffStatus(person) === '1' ? 'text-success' : 'text-destructive'"
                                >
                                    {{ staffStatus(person) === '1' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge
                                size="sm"
                                :variant="person.password_decrypted ? 'soft-success' : 'soft-warning'"
                            >
                                {{ person.password_decrypted ? 'Aktif' : 'Belum Ada' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex justify-center gap-2">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" variant="secondary" class="h-9 w-9 rounded-lg" @click="$emit('editProfile', person)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit data petugas</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Edit data petugas</TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button size="icon" class="h-9 w-9 rounded-lg" @click="$emit('editAccount', person)">
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
                                            :disabled="!person.password_decrypted"
                                            @click="$emit('deleteAccount', person)"
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
                        title="Data petugas tidak ditemukan"
                        description="Coba ubah kata kunci, filter status, atau filter akun untuk menemukan data petugas."
                    />
                </TableBody>
            </Table>

            <template #next="{ loading: loadingNext, hasMore }">
                <div v-if="loadingNext" class="space-y-2 pt-1">
                    <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                        <div class="grid grid-cols-[16%_28%_20%_10%_10%_16%] items-center gap-2">
                            <Skeleton class="h-5 w-24" />
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-44" />
                                <Skeleton class="h-3 w-28" />
                            </div>
                            <Skeleton class="h-4 w-36" />
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
                <p v-else-if="!hasMore && staff.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                    Semua data sudah ditampilkan.
                </p>
            </template>
        </InfiniteScroll>

        <div class="flex items-center justify-between px-1">
            <p class="text-sm text-muted-foreground">
                <template v-if="loading">Mengambil data terbaru...</template>
                <template v-else>Ditampilkan <span class="font-semibold text-foreground">{{ staff.data.length }}</span> dari
                <span class="font-semibold text-foreground">{{ staff.total }}</span> petugas
                </template>
            </p>
            <p v-if="isFetchingNext" class="text-sm text-muted-foreground">Mengambil data lanjutan...</p>
        </div>
    </section>
</template>
