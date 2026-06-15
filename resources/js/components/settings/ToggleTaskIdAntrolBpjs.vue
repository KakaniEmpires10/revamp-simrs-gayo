<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import PreferensiIntegrasiController from '@/actions/App/Http/Controllers/Settings/PreferensiIntegrasiController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';

const props = defineProps<{
    enabled: boolean;
}>();

const form = useForm({
    bpjs_antrol_task_id_enabled: props.enabled,
});

function submit(): void {
    form.patch(PreferensiIntegrasiController.updateBpjs.url(), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['bpjsAntrolTaskIdEnabled'] }),
    });
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>BPJS Antrol</CardTitle>
        </CardHeader>
        <CardContent>
            <form class="space-y-4" @submit.prevent="submit">
                <div class="flex items-center justify-between gap-4 rounded-md border p-4">
                    <div class="space-y-1">
                        <p class="text-sm font-medium">
                            Kirim Task ID Antrol
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Saat nonaktif, pelayanan SIMRS tetap berjalan dan pengiriman dicatat sebagai dilewati.
                        </p>
                    </div>
                    <Switch
                        :model-value="form.bpjs_antrol_task_id_enabled"
                        class="data-[state=checked]:!border-success data-[state=checked]:!bg-success data-[state=unchecked]:!border-destructive data-[state=unchecked]:!bg-destructive"
                        thumb-class="data-[state=checked]:!bg-success-foreground data-[state=unchecked]:!bg-destructive-foreground"
                        @update:model-value="form.bpjs_antrol_task_id_enabled = Boolean($event)"
                    />
                </div>

                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" />
                    Simpan Pengaturan
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
