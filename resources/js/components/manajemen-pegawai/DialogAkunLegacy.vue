<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { ref, watch } from 'vue';
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

const props = defineProps<{
    storeUrl: string;
    title: string;
    description: string;
    idLabel: string;
}>();

const account = defineModel<{
    id: string;
    name: string;
    password: string | null;
} | null>('account', { required: true });

const password = ref('');
const processing = ref(false);

watch(
    account,
    (value) => {
        password.value = value?.password || value?.id || '';
    },
    { immediate: true },
);

function saveAccount(): void {
    if (!account.value) {
        return;
    }

    processing.value = true;

    router.post(
        props.storeUrl,
        {
            id_user: account.value.id,
            password: password.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                account.value = null;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog :open="Boolean(account)" @update:open="account = null">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }} {{ account?.name || account?.id }}.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <label class="grid gap-2">
                    <Label>{{ idLabel }}</Label>
                    <Input :model-value="account?.id" disabled class="font-mono" />
                </label>

                <label class="grid gap-2">
                    <Label>Password Legacy</Label>
                    <Input v-model="password" autocomplete="off" />
                </label>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="account = null">Batal</Button>
                <Button :disabled="processing || !password" @click="saveAccount">
                    <Spinner v-if="processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
