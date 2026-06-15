<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/ManajemenUser/AksesUserController';
import { Button } from '@/components/ui/button';
import { AppSelect } from '@/components/ui/form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { GrupAuth, AksesUser } from '@/types';

const props = defineProps<{
    groups: Pick<GrupAuth, 'id' | 'name' | 'alias'>[];
}>();

const selectedUser = defineModel<AksesUser | null>('user', { required: true });
const selectedAliasGroup = ref('');
const processing = ref(false);

const groupOptions = computed(() =>
    props.groups.map((group) => ({
        value: group.alias,
        label: group.name,
    })),
);

watch(
    selectedUser,
    (user) => {
        selectedAliasGroup.value = user?.alias_group ?? '';
    },
    { immediate: true },
);

function saveAccess(): void {
    if (!selectedUser.value) {
        return;
    }

    processing.value = true;

    router.patch(
        update.url(selectedUser.value.id_user_decrypted),
        {
            alias_group: selectedAliasGroup.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedUser.value = null;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog :open="Boolean(selectedUser)" @update:open="selectedUser = null">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Ubah level akses</DialogTitle>
                <DialogDescription>
                    Pilih level akses untuk
                    {{ selectedUser?.nama || selectedUser?.id_user_decrypted }}.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <Label for="alias_group">Level Akses</Label>
                <AppSelect
                    id="alias_group"
                    v-model="selectedAliasGroup"
                    :options="groupOptions"
                    placeholder="Pilih level akses"
                />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="selectedUser = null">Batal</Button>
                <Button :disabled="processing || !selectedAliasGroup" @click="saveAccess">
                    <Spinner v-if="processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
