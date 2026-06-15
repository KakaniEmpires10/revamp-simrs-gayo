<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { CalendarIcon, Save } from '@lucide/vue';
import { toRef } from 'vue';

type InpatientPlanRow = Record<string, string | number | null | undefined>;

type EditRencanaRawatInapForm = {
    kode_dokter: string;
    poli_kontrol: string;
    tanggal_kontrol: string;
    processing: boolean;
    errors: Partial<Record<'kode_dokter' | 'poli_kontrol' | 'tanggal_kontrol', string>>;
};

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    form: EditRencanaRawatInapForm;
    selectedRow: InpatientPlanRow | null;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const tanggalKontrol = useTanggalCalendar(toRef(props.form, 'tanggal_kontrol'));

function value(row: InpatientPlanRow | null, ...keys: string[]): string {
    for (const key of keys) {
        const item = row?.[key];

        if (item !== null && item !== undefined && String(item) !== '') {
            return String(item);
        }
    }

    return '-';
}

function letterNumber(row: InpatientPlanRow | null): string {
    return value(row, 'noSPRI', 'noSuratKontrol', 'noSurat');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit SPRI</DialogTitle>
                <DialogDescription>
                    Perubahan akan dikirim ke VClaim lalu disinkronkan ke tabel surat rawat inap lokal.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 sm:col-span-2">
                    <Label>No SPRI</Label>
                    <Input :model-value="letterNumber(selectedRow)" readonly />
                </label>
                <label class="grid gap-2">
                    <Label>Kode Dokter BPJS</Label>
                    <Input v-model="form.kode_dokter" autocomplete="off" />
                    <InputError :message="form.errors.kode_dokter" />
                </label>
                <label class="grid gap-2">
                    <Label>Kode Poli Kontrol</Label>
                    <Input v-model="form.poli_kontrol" autocomplete="off" />
                    <InputError :message="form.errors.poli_kontrol" />
                </label>
                <label class="grid gap-2 sm:col-span-2">
                    <Label>Tanggal Rencana Rawat Inap</Label>
                    <Popover v-model:open="tanggalKontrol.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalKontrol.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalKontrol.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.tanggal_kontrol" />
                </label>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="open = false">Batal</Button>
                <Button
                    :disabled="form.processing || !form.kode_dokter || !form.poli_kontrol || !form.tanggal_kontrol"
                    @click="emit('submit')"
                >
                    <Spinner v-if="form.processing" />
                    <Save v-else class="size-4" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
