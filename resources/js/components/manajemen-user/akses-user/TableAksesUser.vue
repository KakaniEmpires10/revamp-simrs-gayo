<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { CheckCircle2, Trash2, UserCog } from '@lucide/vue';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useScrollTabelManual } from '@/composables/useScrollTabelManual';
import type { Paginated, AksesUser } from '@/types';

const props = defineProps<{
    users: Paginated<AksesUser>;
    loading?: boolean;
}>();

defineEmits<{
    edit: [user: AksesUser];
    delete: [user: AksesUser];
}>();

const accountTypeBadgeVariants: Record<string, BadgeVariants['variant']> = {
    dokter: 'soft-info',
    petugas: 'soft-success',
};

const { infiniteScroll } = useScrollTabelManual({
    itemCount: () => props.users.data.length,
    total: () => props.users.total,
    loading: () => props.loading ?? false,
});

function accountTypeBadgeVariant(status: string | null): BadgeVariants['variant'] {
    return accountTypeBadgeVariants[status?.toLowerCase() ?? ''] ?? 'soft-warning';
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <InfiniteScroll
            ref="infiniteScroll"
            data="users"
            items-element="#user-access-table-body"
            only-next
            manual
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            class="w-[14%]">
                            User ID
                        </TableHead>
                        <TableHead
                            class="w-[30%]">
                            Informasi Nama
                        </TableHead>
                        <TableHead
                            class="w-[18%] text-center">
                            Tipe Akun
                        </TableHead>
                        <TableHead
                            class="w-[20%] text-center">
                            Level Akses
                        </TableHead>
                        <TableHead
                            class="w-[18%] text-right">
                            Manajemen
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody id="user-access-table-body">
                    <template v-if="loading">
                        <TableRow v-for="index in 8" :key="`loading-${index}`">
                            <TableCell class="w-[14%] py-3.5">
                                <Skeleton class="h-5 w-24" />
                            </TableCell>
                            <TableCell class="w-[30%] py-3.5">
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-48" />
                                    <Skeleton class="h-3 w-28" />
                                </div>
                            </TableCell>
                            <TableCell class="w-[18%] py-3.5">
                                <Skeleton class="mx-auto h-6 w-20 rounded-full" />
                            </TableCell>
                            <TableCell class="w-[20%] py-3.5">
                                <Skeleton class="mx-auto h-6 w-24" />
                            </TableCell>
                            <TableCell class="w-[18%] py-3.5">
                                <div class="flex justify-end gap-2">
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                    <Skeleton class="h-9 w-9 rounded-lg" />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="users.data.length">
                        <TableRow v-for="user in users.data" :key="user.id_user_decrypted">
                            <TableCell class="w-[14%] py-3.5">
                                <span class="font-mono text-base font-semibold text-primary">
                                    {{ user.id_user_decrypted || '----------' }}
                                </span>
                            </TableCell>

                            <TableCell class="w-[30%] min-w-0 py-3.5">
                                <p
                                    class="truncate text-base font-semibold text-foreground transition-colors group-hover:text-primary">
                                    {{ user.nama || 'Nama belum tersedia' }}
                                </p>
                                <p class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">
                                    System Legacy Entry
                                </p>
                            </TableCell>

                            <TableCell class="w-[18%] py-3.5 text-center">
                                <Badge
                                    size="sm"
                                    :variant="accountTypeBadgeVariant(user.status)"
                                >
                                    {{ user.status || 'Tidak diketahui' }}
                                </Badge>
                            </TableCell>

                            <TableCell class="w-[20%] py-3.5 text-center">
                                <Badge v-if="user.alias_group" variant="outline-primary" size="sm" rounded="md">
                                    <span class="truncate">{{ user.alias_group }}</span>
                                </Badge>
                                <span v-else class="text-sm text-muted-foreground">-</span>
                            </TableCell>

                            <TableCell class="w-[18%] py-3.5">
                                <div class="flex justify-end gap-2">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button size="icon" class="h-9 w-9 rounded-lg"
                                                :variant="user.alias_group ? 'default' : 'secondary'"
                                                @click="$emit('edit', user)">
                                                <CheckCircle2 v-if="user.alias_group" class="size-4" />
                                                <UserCog v-else class="size-4" />
                                                <span class="sr-only">Kelola akses</span>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ user.alias_group ? 'Ubah level akses' : 'Tetapkan level akses' }}
                                        </TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button size="icon" variant="soft-destructive"
                                                class="h-9 w-9 rounded-lg"
                                                :disabled="!user.alias_group" @click="$emit('delete', user)">
                                                <Trash2 class="size-4" />
                                                <span class="sr-only">Hapus level akses</span>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            Hapus level akses
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <TableEmpty
                        v-else
                        :colspan="5"
                        icon="search"
                        severity="info"
                        title="Data pengguna tidak ditemukan"
                        description="Coba ubah kata kunci pencarian atau filter level akses."
                    />
                </TableBody>
            </Table>

            <template #next="{ loading: loadingNext, hasMore }">
                <div v-if="loadingNext" class="space-y-2 pt-1">
                    <div v-for="index in 3" :key="index" class="rounded-lg border bg-card p-4 shadow-sm">
                        <div class="grid grid-cols-[14%_30%_18%_20%_18%] items-center gap-2">
                            <Skeleton class="h-5 w-24" />
                            <div class="space-y-2">
                                <Skeleton class="h-4 w-48" />
                                <Skeleton class="h-3 w-28" />
                            </div>
                            <Skeleton class="mx-auto h-6 w-20 rounded-full" />
                            <Skeleton class="mx-auto h-6 w-24" />
                            <div class="flex justify-start gap-2">
                                <Skeleton class="h-9 w-9 rounded-lg" />
                                <Skeleton class="h-9 w-9 rounded-lg" />
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else-if="!hasMore && users.data.length" class="pt-2 text-center text-sm text-muted-foreground">
                    Semua data yang sesuai filter sudah ditampilkan.
                </p>
            </template>
        </InfiniteScroll>

        <footer class="flex flex-col gap-3 pt-1 text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm">
                <template v-if="loading">
                    Mengambil data terbaru...
                </template>
                <template v-else>
                    Ditampilkan
                    <span class="font-semibold text-foreground">{{ users.data.length }}</span>
                    dari
                    <span class="font-semibold text-foreground">{{ users.total }}</span>
                    entitas user
                </template>
            </p>
        </footer>
    </section>
</template>
