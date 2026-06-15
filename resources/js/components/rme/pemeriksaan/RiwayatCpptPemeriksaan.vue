<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarIcon, FunnelX, LoaderCircle, SlidersHorizontal } from '@lucide/vue';
import { ref, watch } from 'vue';
import DaftarRiwayatCppt from '@/components/rme/pemeriksaan/DaftarRiwayatCppt.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { cppt as cpptRoute } from '@/routes/rme/pemeriksaan';
import type { FilterRiwayatCppt, KonteksPasienPemeriksaan, RiwayatCppt } from '@/types';

const props = defineProps<{
    patient: KonteksPasienPemeriksaan;
    filters: FilterRiwayatCppt;
    limit: number | null;
    histories: RiwayatCppt[];
}>();

const emit = defineEmits<{
    salin: [history: RiwayatCppt];
    edit: [history: RiwayatCppt];
}>();

const processing = ref(false);
const scope = ref<FilterRiwayatCppt['scope']>(props.filters.scope);
const tglAwal = ref<string | undefined>(props.filters.tgl_awal ?? undefined);
const tglAkhir = ref<string | undefined>(props.filters.tgl_akhir ?? undefined);
const tanggalAwal = useTanggalCalendar(tglAwal, 'Tanggal awal');
const tanggalAkhir = useTanggalCalendar(tglAkhir, 'Tanggal akhir');

watch(
    () => props.filters,
    (filters) => {
        processing.value = false;
        scope.value = filters.scope;
        tglAwal.value = filters.tgl_awal ?? undefined;
        tglAkhir.value = filters.tgl_akhir ?? undefined;
    },
);

function kodeAsal(): string {
    if (props.patient.asal === 'rawat-inap') {
        return 'ri';
    }

    if (props.patient.asal === 'rujukan-internal') {
        return 'rp';
    }

    if (props.patient.asal === 'igd') {
        return 'igd';
    }

    return 'rj';
}

function terapkanFilter(): void {
    processing.value = true;

    muatRiwayat(3);
}

function muatRiwayat(limit: number | null): void {
    processing.value = true;

    router.get(
        cpptRoute.url({
            query: {
                no_rawat: props.patient.no_rawat,
                fr: kodeAsal(),
                riwayat_scope: scope.value,
                riwayat_limit: limit === null ? 'all' : undefined,
                riwayat_tgl_awal: scope.value === 'rm' ? tglAwal.value || undefined : undefined,
                riwayat_tgl_akhir: scope.value === 'rm' ? tglAkhir.value || undefined : undefined,
            },
        }),
        {},
        {
            only: ['cpptHistoryFilters', 'cpptHistories'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    scope.value = 'kunjungan';
    tglAwal.value = undefined;
    tglAkhir.value = undefined;
    terapkanFilter();
}
</script>

<template>
    <section class="grid gap-4 rounded-lg border bg-background p-4">
        <div class="grid gap-4">
            <div class="space-y-1">
                <h2 class="text-base font-semibold">Riwayat CPPT</h2>
                <p class="text-sm text-muted-foreground">
                    Awalnya hanya 3 catatan terbaru yang ditampilkan. Gunakan filter atau muat lebih banyak jika perlu melihat seluruh riwayat.
                </p>
            </div>

            <form class="grid gap-4 rounded-md border border-border/70 bg-muted/20 p-3 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-end" @submit.prevent="terapkanFilter">
                <div class="grid gap-2">
                    <Label>Lingkup</Label>
                    <div class="inline-flex w-fit rounded-lg bg-muted p-1">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="scope === 'kunjungan' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                            @click="scope = 'kunjungan'"
                        >
                            Kunjungan ini
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="scope === 'rm' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                            @click="scope = 'rm'"
                        >
                            No RM
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="grid gap-2">
                        <Label for="riwayat_tgl_awal">Tanggal awal</Label>
                        <Popover v-model:open="tanggalAwal.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="justify-start bg-background text-left font-normal" :disabled="scope === 'kunjungan'">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalAwal.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalAwal.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                    </label>

                    <label class="grid gap-2">
                        <Label for="riwayat_tgl_akhir">Tanggal akhir</Label>
                        <Popover v-model:open="tanggalAkhir.open">
                            <PopoverTrigger as-child>
                                <Button type="button" variant="outline" class="justify-start bg-background text-left font-normal" :disabled="scope === 'kunjungan'">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    {{ tanggalAkhir.label }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="tanggalAkhir.value" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                    </label>
                </div>

                <div class="flex items-center gap-2 lg:justify-end">
                    <Button type="submit" variant="secondary" :disabled="processing">
                        <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                        <SlidersHorizontal v-else class="size-4" />
                        Terapkan
                    </Button>

                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label="Bersihkan filter"
                                    :disabled="processing"
                                    @click="bersihkanFilter"
                                >
                                    <FunnelX class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Bersihkan filter</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </form>
        </div>

        <DaftarRiwayatCppt :histories="histories" @salin="emit('salin', $event)" @edit="emit('edit', $event)" />

        <div v-if="limit !== null && histories.length >= 3" class="flex justify-center border-t pt-4">
            <Button type="button" variant="outline" :disabled="processing" @click="muatRiwayat(null)">
                <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                Muat lebih banyak
            </Button>
        </div>
    </section>
</template>
