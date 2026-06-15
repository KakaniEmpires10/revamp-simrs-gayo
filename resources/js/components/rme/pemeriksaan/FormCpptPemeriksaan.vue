<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarIcon, Check, ChevronsUpDown, LoaderCircle, Save, Wand2, X } from '@lucide/vue';
import { computed, nextTick, ref, toRef, watch } from 'vue';
import GcsPopover from '@/components/rme/pemeriksaan/GcsPopover.vue';
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
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input';
import { Textarea } from '@/components/ui/textarea';
import { bolehInputAngkaTtv, bolehInputDesimalTtv, bolehInputTensiTtv, sanitasiAngkaTtv, sanitasiDesimalTtv, sanitasiTensiTtv } from '@/composables/useInputTtv';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import { nilaiNormalPemeriksaan } from '@/lib/nilaiNormalPemeriksaan';
import { cn } from '@/lib/utils';
import { store as storeCppt, update as updateCppt } from '@/routes/rme/pemeriksaan/cppt';
import type { DefaultFormCppt, KonteksPasienPemeriksaan, RiwayatCppt, RmeOption } from '@/types';

const props = defineProps<{
    patient: KonteksPasienPemeriksaan;
    defaults: DefaultFormCppt;
    canChooseOfficer: boolean;
    officerOptions: RmeOption[];
    salinRiwayat?: RiwayatCppt | null;
    editRiwayat?: RiwayatCppt | null;
}>();

const allergyTags = ref<string[]>([]);
const officerOpen = ref(false);
const sedangIsiNormal = ref(false);
const editMode = ref(false);
const formElement = ref<HTMLFormElement | null>(null);
const form = useForm({
    key_no_rawat: '',
    key_tgl_perawatan: '',
    key_jam_rawat: '',
    key_sumber: '',
    no_rawat: props.defaults.no_rawat,
    asal: props.patient.asal,
    tgl_perawatan: props.defaults.tgl_perawatan,
    jam_rawat: props.defaults.jam_rawat,
    nip: props.defaults.nip,
    suhu_tubuh: '',
    tensi: '',
    nadi: '',
    respirasi: '',
    tinggi: '',
    berat: '',
    spo2: '',
    gcs: '',
    kesadaran: props.defaults.kesadaran,
    keluhan: '',
    pemeriksaan: '',
    alergi: '',
    imun_ke: '',
    lingkar_perut: '',
    rtl: '',
    penilaian: '',
    instruksi: '',
    evaluasi: '',
});

const tanggalPerawatan = useTanggalCalendar(toRef(form, 'tgl_perawatan'));
const alergiString = computed(() => allergyTags.value.map((item) => item.trim()).filter(Boolean).join(', '));
const alergiTerlaluPanjang = computed(() => alergiString.value.length > 50);
const selectedOfficerLabel = computed(() => {
    if (!props.canChooseOfficer) {
        return `${props.defaults.nama_pengisi} - ${props.defaults.jenis_pengisi}`;
    }

    return props.officerOptions.find((option) => option.value === form.nip)?.label ?? 'Pilih pengisi';
});

watch(allergyTags, () => {
    form.alergi = alergiString.value;
});

watch(
    () => props.salinRiwayat,
    (history) => {
        if (history) {
            isiDariRiwayat(history, false);
        }
    },
);

watch(
    () => props.editRiwayat,
    (history) => {
        if (history) {
            isiDariRiwayat(history, true);
        }
    },
);

function pilihPengisi(option: RmeOption): void {
    form.nip = option.value;
    officerOpen.value = false;
}

function isiNilaiNormal(): void {
    sedangIsiNormal.value = true;
    const nilai = nilaiNormalPemeriksaan(props.patient.tgl_lahir, props.patient.jk, form.tgl_perawatan);

    form.suhu_tubuh = nilai.suhu_tubuh;
    form.tensi = nilai.tensi;
    form.nadi = nilai.nadi;
    form.respirasi = nilai.respirasi;
    form.spo2 = nilai.spo2;
    form.tinggi = nilai.tinggi;
    form.berat = nilai.berat;
    form.gcs = nilai.gcs;
    form.kesadaran = nilai.kesadaran;

    window.setTimeout(() => {
        sedangIsiNormal.value = false;
    }, 450);
}

function isiDariRiwayat(history: RiwayatCppt, untukEdit: boolean): void {
    editMode.value = untukEdit;
    form.key_no_rawat = untukEdit ? history.no_rawat : '';
    form.key_tgl_perawatan = untukEdit ? history.tgl_perawatan : '';
    form.key_jam_rawat = untukEdit ? history.jam_rawat : '';
    form.key_sumber = untukEdit ? history.sumber : '';
    form.tgl_perawatan = untukEdit ? history.tgl_perawatan : form.tgl_perawatan;
    form.jam_rawat = untukEdit ? history.jam_rawat.slice(0, 5) : form.jam_rawat;
    form.nip = untukEdit ? history.nip : props.defaults.nip;
    form.suhu_tubuh = history.suhu_tubuh ?? '';
    form.tensi = history.tensi ?? '';
    form.nadi = history.nadi ?? '';
    form.respirasi = history.respirasi ?? '';
    form.tinggi = history.tinggi ?? '';
    form.berat = history.berat ?? '';
    form.spo2 = history.spo2 ?? '';
    form.gcs = history.gcs ?? '';
    form.kesadaran = history.kesadaran;
    form.keluhan = history.keluhan ?? '';
    form.pemeriksaan = history.pemeriksaan ?? '';
    form.rtl = history.rtl ?? '';
    form.penilaian = history.penilaian ?? '';
    form.instruksi = history.instruksi ?? '';
    form.evaluasi = history.evaluasi ?? '';
    form.alergi = history.alergi ?? '';
    allergyTags.value = history.alergi ? history.alergi.split(',').map((item) => item.trim()).filter(Boolean) : [];
    form.clearErrors();

    nextTick(() => {
        formElement.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function batalEdit(): void {
    editMode.value = false;
    form.key_no_rawat = '';
    form.key_tgl_perawatan = '';
    form.key_jam_rawat = '';
    form.key_sumber = '';
}

function simpan(): void {
    form.alergi = alergiString.value;

    if (alergiTerlaluPanjang.value) {
        form.setError('alergi', 'Alergi maksimal 50 karakter karena kolom database hanya varchar(50). Persingkat daftar alergi.');

        return;
    }

    const options = {
        preserveScroll: true,
        only: ['cpptHistoryFilters', 'cpptHistoryLimit', 'cpptHistories', 'flash', 'errors'],
        onSuccess: () => {
            batalEdit();
        },
    };

    if (editMode.value) {
        form.patch(updateCppt.url(), options);

        return;
    }

    form.post(storeCppt.url(), options);
}
</script>

<template>
    <form ref="formElement" class="grid scroll-mt-24 gap-5" @submit.prevent="simpan">
        <section class="grid gap-4 rounded-lg border bg-background p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-semibold">Catatan CPPT</h2>
                    <p class="text-sm text-muted-foreground">SOAP, tanda vital, GCS, dan rencana tindak lanjut pemeriksaan.</p>
                </div>
                <Button type="button" variant="secondary" size="sm" class="w-fit" :disabled="sedangIsiNormal" @click="isiNilaiNormal">
                    <LoaderCircle v-if="sedangIsiNormal" class="size-4 animate-spin" />
                    <Wand2 v-else class="size-4" />
                    {{ sedangIsiNormal ? 'Mengisi...' : 'Isi Nilai Normal' }}
                </Button>
            </div>

            <div v-if="editMode" class="flex flex-col gap-2 rounded-md border border-warning/30 bg-warning/10 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="text-warning">
                    Mode edit CPPT aktif untuk {{ form.key_tgl_perawatan }} {{ form.key_jam_rawat }}. Simpan akan memperbarui catatan tersebut.
                </p>
                <Button type="button" variant="outline" size="sm" class="w-fit" @click="batalEdit">Batal edit</Button>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <label class="grid gap-2">
                    <Label>Tanggal</Label>
                    <Popover v-model:open="tanggalPerawatan.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalPerawatan.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="tanggalPerawatan.value" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <p v-if="form.errors.tgl_perawatan" class="text-xs text-destructive">{{ form.errors.tgl_perawatan }}</p>
                </label>

                <label class="grid gap-2">
                    <Label for="jam_rawat">Jam</Label>
                    <Input id="jam_rawat" v-model="form.jam_rawat" type="time" />
                    <p v-if="form.errors.jam_rawat" class="text-xs text-destructive">{{ form.errors.jam_rawat }}</p>
                </label>

                <label class="grid gap-2 md:col-span-2">
                    <Label>Pengisi</Label>
                    <Popover v-if="canChooseOfficer" v-model:open="officerOpen">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                role="combobox"
                                :aria-expanded="officerOpen"
                                class="h-10 w-full justify-between overflow-hidden bg-background px-3"
                            >
                                <span class="truncate text-left">{{ selectedOfficerLabel }}</span>
                                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[--reka-popover-trigger-width] min-w-80 p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Cari dokter atau petugas..." />
                                <CommandList>
                                    <CommandEmpty>Pengisi tidak ditemukan.</CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem
                                            v-for="option in officerOptions"
                                            :key="option.value"
                                            :value="`${option.value} ${option.label} ${option.description || ''}`"
                                            @select="pilihPengisi(option)"
                                        >
                                            <Check
                                                :class="cn(
                                                    'size-4',
                                                    option.value === form.nip ? 'opacity-100' : 'opacity-0',
                                                )"
                                            />
                                            <div class="min-w-0">
                                                <p class="truncate font-medium">{{ option.label }}</p>
                                                <p class="truncate text-xs text-muted-foreground">{{ option.description || option.value }}</p>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <Input v-else :model-value="selectedOfficerLabel" disabled />
                    <p v-if="form.errors.nip" class="text-xs text-destructive">{{ form.errors.nip }}</p>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2">
                    <Label>GCS</Label>
                    <GcsPopover v-model:gcs="form.gcs" v-model:kesadaran="form.kesadaran" />
                    <p v-if="form.errors.gcs" class="text-xs text-destructive">{{ form.errors.gcs }}</p>
                </label>

                <label class="grid gap-2">
                    <Label for="kesadaran">Kesadaran</Label>
                    <Input id="kesadaran" v-model="form.kesadaran" readonly />
                    <p v-if="form.errors.kesadaran" class="text-xs text-destructive">{{ form.errors.kesadaran }}</p>
                </label>

                <label class="grid gap-2">
                    <Label>Alergi</Label>
                    <TagsInput v-model="allergyTags" class="min-h-10 px-3 py-1.5" :aria-invalid="alergiTerlaluPanjang || !!form.errors.alergi">
                        <TagsInputItem v-for="item in allergyTags" :key="item" :value="item" class="h-6 bg-muted px-2 text-xs text-foreground">
                            <TagsInputItemText />
                            <TagsInputItemDelete>
                                <X class="size-3" />
                            </TagsInputItemDelete>
                        </TagsInputItem>
                    <TagsInputInput class="min-h-6 px-0 placeholder:text-muted-foreground" placeholder="Ketik alergi, Enter" />
                    </TagsInput>
                    <p v-if="alergiTerlaluPanjang" class="text-xs text-destructive">
                        {{ alergiString.length }}/50 karakter
                    </p>
                    <p v-if="form.errors.alergi" class="text-xs text-destructive">{{ form.errors.alergi }}</p>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-7">
                <label class="grid gap-2">
                    <Label for="suhu_tubuh">Suhu</Label>
                    <Input id="suhu_tubuh" :model-value="form.suhu_tubuh" inputmode="decimal" placeholder="36.5" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.suhu_tubuh = sanitasiDesimalTtv($event)" />
                    <p v-if="form.errors.suhu_tubuh" class="text-xs text-destructive">{{ form.errors.suhu_tubuh }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="tensi">Tensi</Label>
                    <Input id="tensi" :model-value="form.tensi" inputmode="numeric" placeholder="120/80" @beforeinput="bolehInputTensiTtv" @update:model-value="form.tensi = sanitasiTensiTtv($event)" />
                    <p v-if="form.errors.tensi" class="text-xs text-destructive">{{ form.errors.tensi }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="nadi">Nadi</Label>
                    <Input id="nadi" :model-value="form.nadi" inputmode="numeric" placeholder="80" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.nadi = sanitasiAngkaTtv($event)" />
                    <p v-if="form.errors.nadi" class="text-xs text-destructive">{{ form.errors.nadi }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="respirasi">Respirasi</Label>
                    <Input id="respirasi" :model-value="form.respirasi" inputmode="numeric" placeholder="20" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.respirasi = sanitasiAngkaTtv($event)" />
                    <p v-if="form.errors.respirasi" class="text-xs text-destructive">{{ form.errors.respirasi }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="spo2">SpO2</Label>
                    <Input id="spo2" :model-value="form.spo2" inputmode="numeric" placeholder="98" @beforeinput="bolehInputAngkaTtv" @update:model-value="form.spo2 = sanitasiAngkaTtv($event)" />
                    <p v-if="form.errors.spo2" class="text-xs text-destructive">{{ form.errors.spo2 }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="tinggi">Tinggi</Label>
                    <Input id="tinggi" :model-value="form.tinggi" inputmode="decimal" placeholder="165" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.tinggi = sanitasiDesimalTtv($event)" />
                    <p v-if="form.errors.tinggi" class="text-xs text-destructive">{{ form.errors.tinggi }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="berat">Berat</Label>
                    <Input id="berat" :model-value="form.berat" inputmode="decimal" placeholder="60" @beforeinput="bolehInputDesimalTtv" @update:model-value="form.berat = sanitasiDesimalTtv($event)" />
                    <p v-if="form.errors.berat" class="text-xs text-destructive">{{ form.errors.berat }}</p>
                </label>
            </div>
        </section>

        <section class="grid gap-4 rounded-lg border bg-background p-4">
            <div class="grid gap-4 lg:grid-cols-2">
                <label class="grid gap-2">
                    <Label for="keluhan">Subjek</Label>
                    <Textarea id="keluhan" v-model="form.keluhan" rows="5" placeholder="Keluhan utama dan anamnesis pasien" />
                    <p v-if="form.errors.keluhan" class="text-xs text-destructive">{{ form.errors.keluhan }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="pemeriksaan">Objek</Label>
                    <Textarea id="pemeriksaan" v-model="form.pemeriksaan" rows="5" placeholder="Hasil pemeriksaan fisik dan penunjang" />
                    <p v-if="form.errors.pemeriksaan" class="text-xs text-destructive">{{ form.errors.pemeriksaan }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="penilaian">Asesmen</Label>
                    <Textarea id="penilaian" v-model="form.penilaian" rows="5" placeholder="Diagnosis kerja atau penilaian klinis" />
                    <p v-if="form.errors.penilaian" class="text-xs text-destructive">{{ form.errors.penilaian }}</p>
                </label>
                <label class="grid gap-2">
                    <Label for="rtl">Plan</Label>
                    <Textarea id="rtl" v-model="form.rtl" rows="5" placeholder="Rencana terapi, kontrol, atau tindak lanjut" />
                </label>
                <label class="grid gap-2">
                    <Label for="instruksi">Instruksi</Label>
                    <Textarea id="instruksi" v-model="form.instruksi" rows="4" placeholder="Instruksi untuk petugas/pasien" />
                </label>
                <label class="grid gap-2">
                    <Label for="evaluasi">Evaluasi</Label>
                    <Textarea id="evaluasi" v-model="form.evaluasi" rows="4" placeholder="Evaluasi respons pasien" />
                </label>
            </div>
        </section>

        <div class="flex justify-end mb-4">
            <Button type="submit" :disabled="form.processing || alergiTerlaluPanjang">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                {{ editMode ? 'Simpan Perubahan CPPT' : 'Simpan CPPT' }}
            </Button>
        </div>
    </form>
</template>
