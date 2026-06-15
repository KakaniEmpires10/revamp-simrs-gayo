<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Edit3, KeyRound, Trash2, UsersRound } from '@lucide/vue';
import { edit as editPermissions } from '@/actions/App/Http/Controllers/ManajemenUser/IzinGrupController';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { GrupAuth } from '@/types';

defineProps<{
    group: GrupAuth;
}>();

defineEmits<{
    edit: [group: GrupAuth];
    delete: [group: GrupAuth];
}>();
</script>

<template>
    <article
        class="flex min-h-56 flex-col justify-between rounded-lg border bg-card p-5 shadow-sm transition-colors hover:border-primary/40"
    >
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 space-y-1">
                    <h2 class="truncate text-lg font-semibold tracking-tight">
                        {{ group.name }}
                    </h2>
                    <p class="truncate font-mono text-xs text-muted-foreground">
                        {{ group.alias }}
                    </p>
                </div>
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                >
                    <UsersRound class="size-5" />
                </div>
            </div>

            <p class="min-h-10 text-sm leading-5 text-muted-foreground">
                {{ group.keterangan || 'Belum ada keterangan.' }}
            </p>

            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-md bg-muted p-3">
                    <p class="text-xs text-muted-foreground">Permission</p>
                    <p class="text-xl font-semibold">
                        {{ group.permissions_count ?? 0 }}
                    </p>
                </div>
                <div class="rounded-md bg-muted p-3">
                    <p class="text-xs text-muted-foreground">Pengguna</p>
                    <p class="text-xl font-semibold">
                        {{ group.users_count ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-2 border-t pt-4">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="editPermissions(group.id)">
                            <KeyRound class="size-4" />
                            Permission
                        </Link>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Kelola permission</TooltipContent>
            </Tooltip>

            <div class="flex gap-1">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button size="icon" variant="ghost" @click="$emit('edit', group)">
                            <Edit3 class="size-4" />
                            <span class="sr-only">Ubah</span>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Ubah level akses</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            size="icon"
                            variant="ghost-destructive"
                            @click="$emit('delete', group)"
                        >
                            <Trash2 class="size-4" />
                            <span class="sr-only">Hapus</span>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Hapus level akses</TooltipContent>
                </Tooltip>
            </div>
        </div>
    </article>
</template>
