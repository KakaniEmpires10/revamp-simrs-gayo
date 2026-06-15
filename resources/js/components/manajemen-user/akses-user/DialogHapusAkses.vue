<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { destroy } from '@/actions/App/Http/Controllers/ManajemenUser/AksesUserController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { AksesUser } from '@/types';

const userPendingDelete = defineModel<AksesUser | null>('user', {
    required: true,
});

function deleteAccess(): void {
    if (!userPendingDelete.value) {
        return;
    }

    router.delete(destroy.url(userPendingDelete.value.id_user_decrypted), {
        preserveScroll: true,
        onFinish: () => {
            userPendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Dialog :open="Boolean(userPendingDelete)" @update:open="userPendingDelete = null">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus level akses pengguna?</DialogTitle>
                <DialogDescription>
                    Level akses untuk
                    {{ userPendingDelete?.nama || userPendingDelete?.id_user_decrypted }}
                    akan dikosongkan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="secondary" @click="userPendingDelete = null">
                    Batal
                </Button>
                <Button variant="destructive" @click="deleteAccess">
                    Hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
