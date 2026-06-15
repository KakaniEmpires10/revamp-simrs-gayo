<script setup lang="ts">
import { CalendarIcon, Check, ChevronsUpDown, DoorOpen, Search } from '@lucide/vue';
import { computed, reactive, ref, toRef, watch } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { tanggalHariIni } from '@/lib/pasien';
import { cn } from '@/lib/utils';
import type { FilterKunjunganRme, RmeOption } from '@/types';

type TipeFilterRawatInap = 'belum_pulang' | 'tanggal_masuk' | 'tanggal_keluar';

const props = withDefaults(
    defineProps<{
        filters: FilterKunjunganRme;
        processing?: boolean;
        rooms?: RmeOption[];
        searchPlaceholder?: string;
    }>(),
    {
        processing: false,
        rooms: () => [],
        searchPlaceholder: 'Cari no rawat, no RM, nama pasien, dokter...',
    },
);

const emit = defineEmits<{
    terapkan: [filters: FilterKunjunganRme];
    bersihkan: [filters: FilterKunjunganRme];
}>();

const form = reactive<FilterKunjunganRme>({
    tgl_awal: props.filters.tgl_awal,
    tgl_akhir: props.filters.tgl_akhir,
    search: props.filters.search,
    kd_bangsal: props.filters.kd_bangsal ?? null,
    tipe_filter_ranap: props.filters.tipe_filter_ranap ?? 'belum_pulang',
});

const roomOpen = ref(false);
const tanggalAwal = useTanggalCalendar(toRef(form, 'tgl_awal'));
const tanggalAkhir = useTanggalCalendar(toRef(form, 'tgl_akhir'));
const modeTanggalAktif = computed(() => form.tipe_filter_ranap !== 'belum_pulang');
const selectedRoom = computed(() => props.rooms.find((room) => room.value === form.kd_bangsal));
const roomButtonLabel = computed(() => selectedRoom.value?.label ?? 'Semua ruangan');
const tipeFilterRanap = computed<TipeFilterRawatInap>({
    get: () => {
        if (
            form.tipe_filter_ranap === 'tanggal_masuk'
            || form.tipe_filter_ranap === 'tanggal_keluar'
            || form.tipe_filter_ranap === 'belum_pulang'
        ) {
            return form.tipe_filter_ranap;
        }

        return 'belum_pulang';
    },
    set: (value) => {
        form.tipe_filter_ranap = value;
    },
});

watch(
    () => props.filters,
    (filters) => {
        form.tgl_awal = filters.tgl_awal;
        form.tgl_akhir = filters.tgl_akhir;
        form.search = filters.search;
        form.kd_bangsal = filters.kd_bangsal ?? null;
        form.tipe_filter_ranap = filters.tipe_filter_ranap ?? 'belum_pulang';
    },
);

function filterSaatIni(): FilterKunjunganRme {
    return {
        tgl_awal: form.tgl_awal,
        tgl_akhir: form.tgl_akhir,
        search: form.search,
        kd_bangsal: form.kd_bangsal || null,
        tipe_filter_ranap: form.tipe_filter_ranap || 'belum_pulang',
    };
}

function terapkanFilter(): void {
    emit('terapkan', filterSaatIni());
}

function bersihkanFilter(): void {
    const hariIni = tanggalHariIni();

    form.tgl_awal = hariIni;
    form.tgl_akhir = hariIni;
    form.search = '';
    form.kd_bangsal = null;
    form.tipe_filter_ranap = 'belum_pulang';

    emit('bersihkan', filterSaatIni());
}

function pilihRuangan(room: RmeOption | null): void {
    form.kd_bangsal = room?.value ?? null;
    roomOpen.value = false;
}
</script>

<template>
    <form
        class="grid w-full max-w-full min-w-0 gap-4 rounded-lg border bg-card p-4 shadow-sm"
        @submit.prevent="terapkanFilter"
    >
        <div class="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.9fr)_minmax(0,0.9fr)_minmax(0,1.05fr)_minmax(0,1.35fr)_auto] xl:items-end">
            <label class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Tipe Filter</span>
                <Tabs v-model="tipeFilterRanap" class="w-full">
                    <TabsList class="grid h-10 w-full grid-cols-3">
                        <TabsTrigger value="belum_pulang" class="px-2 text-[11px]">Belum Pulang</TabsTrigger>
                        <TabsTrigger value="tanggal_masuk" class="px-2 text-[11px]">Tgl. Masuk</TabsTrigger>
                        <TabsTrigger value="tanggal_keluar" class="px-2 text-[11px]">Tgl. Keluar</TabsTrigger>
                    </TabsList>
                </Tabs>
            </label>

            <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:contents">
                <label class="grid min-w-0 gap-2">
                    <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Tanggal Awal</span>
                    <Popover v-model:open="tanggalAwal.open">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                class="w-full justify-start bg-background text-left font-normal"
                                :disabled="!modeTanggalAktif"
                            >
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalAwal.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalAwal.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                </label>

                <label class="grid min-w-0 gap-2">
                    <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Tanggal Akhir</span>
                    <Popover v-model:open="tanggalAkhir.open">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                class="w-full justify-start bg-background text-left font-normal"
                                :disabled="!modeTanggalAktif"
                            >
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

            <label class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Ruangan</span>
                <Popover v-model:open="roomOpen">
                    <PopoverTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            role="combobox"
                            :aria-expanded="roomOpen"
                            class="w-full justify-between overflow-hidden bg-background"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <DoorOpen class="size-4 shrink-0 text-muted-foreground" />
                                <span class="truncate">{{ roomButtonLabel }}</span>
                            </span>
                            <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-[--reka-popover-trigger-width] min-w-72 p-0" align="start">
                        <Command>
                            <CommandInput placeholder="Cari ruangan..." />
                            <CommandList>
                                <CommandEmpty>Ruangan tidak ditemukan.</CommandEmpty>
                                <CommandGroup>
                                    <CommandItem value="semua ruangan" @select="pilihRuangan(null)">
                                        <Check :class="cn('size-4', !form.kd_bangsal ? 'opacity-100' : 'opacity-0')" />
                                        Semua ruangan
                                    </CommandItem>
                                    <CommandItem
                                        v-for="room in rooms"
                                        :key="room.value"
                                        :value="`${room.value} ${room.label}`"
                                        @select="pilihRuangan(room)"
                                    >
                                        <Check :class="cn('size-4', room.value === form.kd_bangsal ? 'opacity-100' : 'opacity-0')" />
                                        <span class="truncate">{{ room.label }}</span>
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
            </label>

            <label class="grid min-w-0 gap-2">
                <span class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Pencarian</span>
                <div class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="form.search"
                        class="pl-9"
                        :placeholder="searchPlaceholder"
                    />
                </div>
            </label>

            <div class="flex items-end md:col-span-2 xl:col-span-1">
                <TombolAksiFilter
                    :processing="processing"
                    submit
                    @bersihkan="bersihkanFilter"
                />
            </div>
        </div>
    </form>
</template>
