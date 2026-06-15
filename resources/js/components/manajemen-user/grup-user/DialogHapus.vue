<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { destroy as destroyGroup } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { GrupAuth } from '@/types';

const group = defineModel<GrupAuth | null>('group', { required: true });

function deleteGroup(): void {
    if (!group.value) {
        return;
    }

    router.delete(destroyGroup.url(group.value.id), {
        preserveScroll: true,
        onFinish: () => {
            group.value = null;
        },
    });
}
</script>

<template>
    <Dialog :open="Boolean(group)" @update:open="group = null">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus level akses?</DialogTitle>
                <DialogDescription>
                    Level akses "{{ group?.name }}" akan dihapus jika belum
                    memiliki permission.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="secondary" @click="group = null">Batal</Button>
                <Button variant="destructive" @click="deleteGroup">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
