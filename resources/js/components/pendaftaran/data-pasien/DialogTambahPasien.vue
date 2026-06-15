<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Pasien Baru</DialogTitle>
                <DialogDescription>
                    Form master pasien baru belum dimigrasikan pada tahap pendaftaran umum ini.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="open = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
