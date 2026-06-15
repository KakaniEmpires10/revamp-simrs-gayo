<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Activity, CalendarClock, ClipboardCopy, Eye, HeartPulse, LoaderCircle, Pencil, Stethoscope, Trash2 } from '@lucide/vue';
import { nextTick, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { useDeleteDialog } from '@/composables/useDeleteDialog';
import { destroy as destroyCppt } from '@/routes/rme/pemeriksaan/cppt';
import type { RiwayatCppt } from '@/types';

defineProps<{
    histories: RiwayatCppt[];
}>();

const emit = defineEmits<{
    salin: [history: RiwayatCppt];
    edit: [history: RiwayatCppt];
}>();

const selectedHistory = ref<RiwayatCppt | null>(null);
const deletingKey = ref<string | null>(null);
const { openDeleteDialog } = useDeleteDialog();

function kunci(history: RiwayatCppt): string {
    return `${history.sumber}-${history.no_rawat}-${history.tgl_perawatan}-${history.jam_rawat}`;
}

function hapus(history: RiwayatCppt): void {
    openDeleteDialog({
        title: 'Hapus CPPT?',
        description: `CPPT tanggal ${history.tgl_perawatan} jam ${history.jam_rawat} akan dihapus permanen jika Anda melanjutkan.`,
        actionLabel: 'Hapus CPPT',
        action: () => {
            deletingKey.value = kunci(history);

            router.delete(destroyCppt.url(), {
                data: {
                    key_no_rawat: history.no_rawat,
                    key_tgl_perawatan: history.tgl_perawatan,
                    key_jam_rawat: history.jam_rawat,
                    key_sumber: history.sumber,
                },
                preserveScroll: true,
                only: ['cpptHistoryFilters', 'cpptHistoryLimit', 'cpptHistories', 'flash', 'errors'],
                onFinish: () => {
                    deletingKey.value = null;
                },
            });
        },
    });
}

function salinDariDialog(history: RiwayatCppt): void {
    selectedHistory.value = null;

    nextTick(() => {
        window.setTimeout(() => emit('salin', history), 80);
    });
}

function editDariDialog(history: RiwayatCppt): void {
    selectedHistory.value = null;

    nextTick(() => {
        window.setTimeout(() => emit('edit', history), 80);
    });
}
</script>

<template>
    <section class="grid gap-3">
        <article
            v-for="history in histories"
            :key="kunci(history)"
            class="rounded-lg border bg-background p-4"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="soft-primary" rounded="md">{{ history.asal_layanan }}</Badge>
                        <span class="inline-flex items-center gap-1 text-sm text-muted-foreground">
                            <CalendarClock class="size-4" />
                            {{ history.tgl_perawatan }} {{ history.jam_rawat }}
                        </span>
                    </div>
                    <p class="text-sm font-medium">{{ history.no_rawat }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span class="inline-flex items-center gap-1 text-sm text-muted-foreground">
                        <Stethoscope class="size-4" />
                        {{ history.nama_pengisi }}
                    </span>
                    <Button type="button" variant="soft-info" size="sm" @click="emit('salin', history)">
                        <ClipboardCopy class="size-4" />
                        Copy
                    </Button>
                    <Button v-if="history.can_edit" type="button" variant="soft-warning" size="sm" @click="emit('edit', history)">
                        <Pencil class="size-4" />
                        Edit
                    </Button>
                    <Button
                        v-if="history.can_delete"
                        type="button"
                        variant="soft-destructive"
                        size="sm"
                        :disabled="deletingKey === kunci(history)"
                        @click="hapus(history)"
                    >
                        <LoaderCircle v-if="deletingKey === kunci(history)" class="size-4 animate-spin" />
                        <Trash2 v-else class="size-4" />
                        Hapus
                    </Button>
                    <Button type="button" variant="outline-primary" size="sm" @click="selectedHistory = history">
                        <Eye class="size-4" />
                        Detail
                    </Button>
                </div>
            </div>

            <Separator class="my-4" />

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Subjek</p>
                    <p class="line-clamp-3 whitespace-pre-wrap text-sm">{{ history.keluhan || '-' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Objek</p>
                    <p class="line-clamp-3 whitespace-pre-wrap text-sm">{{ history.pemeriksaan || '-' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Asesmen</p>
                    <p class="line-clamp-3 whitespace-pre-wrap text-sm">{{ history.penilaian || '-' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Plan</p>
                    <p class="line-clamp-3 whitespace-pre-wrap text-sm">{{ history.rtl || '-' }}</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-xs text-muted-foreground">
                <span class="inline-flex items-center gap-1">
                    <Activity class="size-3.5" />
                    GCS {{ history.gcs || '-' }} - {{ history.kesadaran }}
                </span>
                <span>Tensi {{ history.tensi || '-' }}</span>
                <span>Nadi {{ history.nadi || '-' }}</span>
                <span>RR {{ history.respirasi || '-' }}</span>
                <span>SpO2 {{ history.spo2 || '-' }}</span>
                <span>Alergi {{ history.alergi || '-' }}</span>
            </div>
        </article>

        <div v-if="!histories.length" class="grid min-h-[240px] place-items-center rounded-lg border border-dashed bg-muted/20 p-8 text-center">
            <div class="space-y-2">
                <p class="font-medium">Riwayat CPPT belum tersedia</p>
                <p class="text-sm text-muted-foreground">Belum ada pemeriksaan terdahulu untuk pasien ini.</p>
            </div>
        </div>

        <Dialog :open="selectedHistory !== null" @update:open="(open) => !open && (selectedHistory = null)">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Detail CPPT</DialogTitle>
                    <DialogDescription v-if="selectedHistory">
                        {{ selectedHistory.no_rawat }} - {{ selectedHistory.tgl_perawatan }} {{ selectedHistory.jam_rawat }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedHistory" class="grid gap-5">
                    <div class="grid gap-3 rounded-lg border border-border/70 bg-muted/30 p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="soft-primary" rounded="md">{{ selectedHistory.asal_layanan }}</Badge>
                            <Badge variant="soft-secondary" rounded="md">{{ selectedHistory.nama_pengisi }}</Badge>
                            <Badge v-if="selectedHistory.can_edit" variant="soft-success" rounded="md">Dapat diedit</Badge>
                        </div>
                        <div class="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <span class="inline-flex items-center gap-1 text-muted-foreground">
                                <CalendarClock class="size-4" />
                                {{ selectedHistory.tgl_perawatan }} {{ selectedHistory.jam_rawat }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-muted-foreground">
                                <HeartPulse class="size-4" />
                                Tensi {{ selectedHistory.tensi || '-' }}
                            </span>
                            <span class="text-muted-foreground">GCS {{ selectedHistory.gcs || '-' }} - {{ selectedHistory.kesadaran }}</span>
                            <span class="text-muted-foreground">Alergi {{ selectedHistory.alergi || '-' }}</span>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">Suhu</p>
                            <p class="font-semibold">{{ selectedHistory.suhu_tubuh || '-' }} C</p>
                        </div>
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">Nadi</p>
                            <p class="font-semibold">{{ selectedHistory.nadi || '-' }} x/menit</p>
                        </div>
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">Respirasi</p>
                            <p class="font-semibold">{{ selectedHistory.respirasi || '-' }} x/menit</p>
                        </div>
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">SpO2</p>
                            <p class="font-semibold">{{ selectedHistory.spo2 || '-' }}%</p>
                        </div>
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">Tinggi</p>
                            <p class="font-semibold">{{ selectedHistory.tinggi || '-' }} cm</p>
                        </div>
                        <div class="rounded-md border border-border/70 p-3">
                            <p class="text-xs text-muted-foreground">Berat</p>
                            <p class="font-semibold">{{ selectedHistory.berat || '-' }} kg</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Subjek</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.keluhan || '-' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Objek</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.pemeriksaan || '-' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Asesmen</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.penilaian || '-' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Plan</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.rtl || '-' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Instruksi</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.instruksi || '-' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Evaluasi</p>
                            <p class="whitespace-pre-wrap text-sm">{{ selectedHistory.evaluasi || '-' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t pt-4">
                        <Button type="button" variant="soft-info" @click="salinDariDialog(selectedHistory)">
                            <ClipboardCopy class="size-4" />
                            Copy ke Form
                        </Button>
                        <Button v-if="selectedHistory.can_edit" type="button" variant="soft-warning" @click="editDariDialog(selectedHistory)">
                            <Pencil class="size-4" />
                            Edit CPPT
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </section>
</template>
