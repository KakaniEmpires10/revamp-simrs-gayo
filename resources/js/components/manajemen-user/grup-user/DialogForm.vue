<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
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
import type { GrupAuth } from '@/types';

const props = defineProps<{
    group: GrupAuth | null;
}>();

const isOpen = defineModel<boolean>('open', { required: true });

const formTitle = computed(() =>
    props.group ? 'Ubah Level Akses' : 'Tambah Level Akses',
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ formTitle }}</DialogTitle>
                <DialogDescription>
                    Alias akan mengikuti pola lama SIMRS berdasarkan nama level
                    akses.
                </DialogDescription>
            </DialogHeader>

            <Form
                :action="group ? update.url(group.id) : store.url()"
                :method="group ? 'patch' : 'post'"
                :options="{ preserveScroll: true }"
                @success="isOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <div class="grid gap-2">
                    <Label for="name">Nama Level Akses</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="group?.name"
                        placeholder="Contoh: Petugas Pendaftaran"
                    />
                    <InputError :message="errors.name || errors.alias" />
                </div>

                <div class="grid gap-2">
                    <Label for="keterangan">Keterangan</Label>
                    <Input
                        id="keterangan"
                        name="keterangan"
                        :default-value="group?.keterangan"
                        placeholder="Keterangan singkat"
                    />
                    <InputError :message="errors.keterangan" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="isOpen = false"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
