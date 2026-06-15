<script setup lang="ts">
import { DownloadCloud } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type TarikSepForm = {
    no_sep: string;
    no_rawat: string;
    processing: boolean;
    errors: Partial<Record<'no_sep' | 'no_rawat', string>>;
};

const open = defineModel<boolean>('open', { required: true });

defineProps<{
    form: TarikSepForm;
}>();

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tarik SEP</DialogTitle>
                <DialogDescription>
                    Masukkan No. Rawat untuk menyimpan SEP ke database lokal SIMRS.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <label class="grid gap-2">
                    <Label>No SEP</Label>
                    <Input v-model="form.no_sep" readonly />
                    <InputError :message="form.errors.no_sep" />
                </label>
                <label class="grid gap-2">
                    <Label>No Rawat</Label>
                    <Input v-model="form.no_rawat" maxlength="17" placeholder="no_rawat pasien sep" />
                    <InputError :message="form.errors.no_rawat" />
                </label>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                <Button :disabled="form.processing || !form.no_sep || form.no_rawat.length !== 17" @click="emit('submit')">
                    <Spinner v-if="form.processing" />
                    <DownloadCloud v-else class="size-4" />
                    Tarik SEP
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
