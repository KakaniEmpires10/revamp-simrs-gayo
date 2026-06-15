<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { parseDate } from '@internationalized/date';
import { CalendarIcon, CreditCard, IdCard, Mars, Save, Search, UserRound, Venus } from '@lucide/vue';
import { computed, ref, toRef, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
    bpjsAutofill,
    store as storeDataPasien,
    update as updateDataPasien,
} from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import AlertError from '@/components/AlertError.vue';
import WilayahAsyncCombobox from '@/components/pendaftaran/data-pasien/WilayahAsyncCombobox.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AppSelect } from '@/components/ui/form';
import type { AppSelectOption } from '@/components/ui/form/types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { feedbackOnly } from '@/composables/useFeedback';
import { useTanggalCalendar } from '@/composables/useTanggalCalendar';
import type { RegistrationPatient } from '@/types';

type Mode = 'create' | 'edit';

type PatientReferences = {
    payments: AppSelectOption[];
    physicalDisabilities: AppSelectOption[];
    ethnicities: AppSelectOption[];
    languages: AppSelectOption[];
    companies: AppSelectOption[];
};

type FormDataPasien = {
    auto_no_rm: boolean;
    no_rkm_medis: string;
    nm_pasien: string;
    jk: string;
    tmp_lahir: string;
    tgl_lahir: string | undefined;
    nm_ibu: string;
    agama: string;
    gol_darah: string;
    stts_nikah: string;
    pnd: string;
    pekerjaan: string;
    no_tlp: string;
    no_ktp: string;
    no_peserta: string;
    alamat: string;
    kd_pj: string;
    email: string;
    kd_kel: string;
    kd_kec: string;
    kd_kab: string;
    kd_prop: string;
    keluarga: string;
    namakeluarga: string;
    pekerjaanpj: string;
    alamatpj: string;
    kelurahanpj: string;
    kecamatanpj: string;
    kabupatenpj: string;
    cacat_fisik: string;
    suku_bangsa: string;
    bahasa_pasien: string;
    perusahaan_pasien: string;
};

const props = defineProps<{
    mode: Mode;
    patient: RegistrationPatient | null;
    nextMedicalRecordNumber: string | null;
    references: PatientReferences;
}>();

const bloodOptions = ['-', 'A', 'B', 'O', 'AB'].map((value) => ({ value, label: value }));
const religionOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha'].map((value) => ({ value, label: value }));
const maritalOptions = ['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA'].map((value) => ({ value, label: value }));
const educationOptions = ['-', 'TS', 'TK', 'SD', 'SMP', 'SMA', 'SLTA/SEDERAJAT', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']
    .map((value) => ({ value, label: value }));
const familyOptions = ['AYAH', 'IBU', 'ISTRI', 'SUAMI', 'SAUDARA', 'ANAK'].map((value) => ({ value, label: value }));

const defaultPayment = computed(() => props.patient?.kd_pj
    ?? props.references.payments.find((payment) => payment.label.toUpperCase().includes('BPJS'))?.value
    ?? props.references.payments[0]?.value
    ?? '');
const bpjsLoading = ref<'nik' | 'card' | null>(null);
const tanggalLahirPlaceholder = computed(() => parseDate(form.tgl_lahir || '2000-01-01'));

const tampilAlamatDetail = ref(Boolean(props.patient?.kd_kel || props.patient?.kd_kec || props.patient?.kd_kab));
const tampilPenanggungJawab = ref(Boolean(props.patient?.namakeluarga && props.patient.namakeluarga !== '-'));
const tampilAsuransiTambahan = ref(Boolean(props.patient?.no_peserta || props.patient?.email));
const tampilPenunjang = ref(Boolean(props.patient?.cacat_fisik || props.patient?.suku_bangsa || props.patient?.bahasa_pasien || props.patient?.perusahaan_pasien));
const alamatPenanggungJawabMengikutiPasien = ref(false);

const form = useForm<FormDataPasien>({
    auto_no_rm: props.mode === 'create',
    no_rkm_medis: props.patient?.no_rkm_medis ?? props.nextMedicalRecordNumber ?? '',
    nm_pasien: props.patient?.nm_pasien ?? '',
    jk: props.patient?.jk === 'P' ? 'P' : 'L',
    tmp_lahir: props.patient?.tmp_lahir ?? '',
    tgl_lahir: props.patient?.tgl_lahir ?? undefined,
    nm_ibu: props.patient?.nm_ibu ?? '',
    agama: props.patient?.agama ?? 'Islam',
    gol_darah: props.patient?.gol_darah ?? '-',
    stts_nikah: props.patient?.stts_nikah ?? 'BELUM MENIKAH',
    pnd: props.patient?.pnd ?? '-',
    pekerjaan: props.patient?.pekerjaan ?? '',
    no_tlp: props.patient?.no_tlp ?? '',
    no_ktp: props.patient?.no_ktp ?? '',
    no_peserta: props.patient?.no_peserta ?? '',
    alamat: props.patient?.alamat ?? '',
    kd_pj: defaultPayment.value,
    email: props.patient?.email ?? '',
    kd_kel: String(props.patient?.kd_kel ?? ''),
    kd_kec: String(props.patient?.kd_kec ?? ''),
    kd_kab: String(props.patient?.kd_kab ?? '695'),
    kd_prop: String(props.patient?.kd_prop ?? '1'),
    keluarga: props.patient?.keluarga ?? 'AYAH',
    namakeluarga: props.patient?.namakeluarga ?? '',
    pekerjaanpj: props.patient?.pekerjaanpj ?? '',
    alamatpj: props.patient?.alamatpj ?? '',
    kelurahanpj: props.patient?.kelurahanpj ?? '',
    kecamatanpj: props.patient?.kecamatanpj ?? '',
    kabupatenpj: props.patient?.kabupatenpj ?? '',
    cacat_fisik: String(props.patient?.cacat_fisik ?? props.references.physicalDisabilities[0]?.value ?? ''),
    suku_bangsa: String(props.patient?.suku_bangsa ?? props.references.ethnicities[0]?.value ?? ''),
    bahasa_pasien: String(props.patient?.bahasa_pasien ?? props.references.languages[0]?.value ?? ''),
    perusahaan_pasien: props.patient?.perusahaan_pasien ?? props.references.companies[0]?.value ?? '',
});

const tanggalLahir = useTanggalCalendar(toRef(form, 'tgl_lahir'), 'Pilih tanggal lahir');

function error(field: keyof FormDataPasien): string[] {
    const message = form.errors[field];

    return message ? [message] : [];
}

function sinkronkanAlamatPenanggungJawab(): void {
    if (alamatPenanggungJawabMengikutiPasien.value) {
        form.alamatpj = form.alamat;
        form.kelurahanpj = form.kd_kel;
        form.kecamatanpj = form.kd_kec;
        form.kabupatenpj = form.kd_kab;
    }
}

function submit(): void {
    sinkronkanAlamatPenanggungJawab();

    const options = {
        preserveScroll: true,
        only: feedbackOnly([]),
    };

    if (props.mode === 'edit' && props.patient) {
        form.patch(updateDataPasien.url(props.patient.no_rkm_medis), options);

        return;
    }

    form.post(storeDataPasien.url(), options);
}

watch([
    () => form.alamat,
    () => form.kd_kel,
    () => form.kd_kec,
    () => form.kd_kab,
    alamatPenanggungJawabMengikutiPasien,
], sinkronkanAlamatPenanggungJawab, { immediate: true });

async function autofillBpjs(type: 'nik' | 'card'): Promise<void> {
    const identifier = type === 'nik' ? form.no_ktp : form.no_peserta;
    const label = type === 'nik' ? 'NIK' : 'No BPJS';

    if (!identifier) {
        toast.warning(`Isi ${label} terlebih dahulu.`);

        return;
    }

    bpjsLoading.value = type;

    try {
        const response = await fetch(bpjsAutofill.url({ query: { type, identifier } }), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json() as {
            metadata: { code: string; message: string };
            patient: Partial<RegistrationPatient>;
        };

        if (payload.metadata.code !== '200') {
            toast.warning(payload.metadata.message || 'Data BPJS tidak ditemukan.');

            return;
        }

        form.nm_pasien = payload.patient.nm_pasien || form.nm_pasien;
        form.no_ktp = payload.patient.no_ktp || form.no_ktp;
        form.no_peserta = payload.patient.no_peserta || form.no_peserta;
        form.tgl_lahir = payload.patient.tgl_lahir || form.tgl_lahir;
        form.jk = payload.patient.jk === 'P' || payload.patient.jk === 'L' ? payload.patient.jk : form.jk;

        toast.success('Data BPJS berhasil dimuat ke form pasien.');
    } catch {
        toast.error('Autofill BPJS gagal. Periksa koneksi integrasi lalu coba ulangi.');
    } finally {
        bpjsLoading.value = null;
    }
}
</script>

<template>
    <form class="grid gap-5" @submit.prevent="submit">
        <AlertError
            v-if="Object.keys(form.errors).length"
            title="Form belum valid"
            :errors="['Periksa kembali isian yang ditandai.']"
        />
        <Card class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <UserRound class="size-4" />
                    Identitas Dasar
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">No RM</span>
                    <div class="flex items-center gap-2">
                        <Input
                            v-model="form.no_rkm_medis"
                            placeholder="Masukkan nomor rekam medis"
                            :disabled="form.auto_no_rm || mode === 'edit'"
                        />
                        <div v-if="mode === 'create'" class="flex items-center gap-2 rounded-md border px-3 py-2">
                            <Switch v-model="form.auto_no_rm" />
                            <span class="whitespace-nowrap text-xs text-muted-foreground">Auto</span>
                        </div>
                    </div>
                    <p v-if="error('no_rkm_medis').length" class="text-sm text-destructive">{{ error('no_rkm_medis')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">Nama Pasien</span>
                    <Input v-model="form.nm_pasien" placeholder="Masukkan nama pasien" autocomplete="name" />
                    <p v-if="error('nm_pasien').length" class="text-sm text-destructive">{{ error('nm_pasien')[0] }}</p>
                </label>

                <div class="grid gap-2">
                    <span class="text-sm font-medium">Jenis Kelamin</span>
                    <Tabs v-model="form.jk" class="w-full">
                        <TabsList class="grid h-11 w-full grid-cols-2">
                            <TabsTrigger
                                value="L"
                                class="gap-2 text-sm data-[state=active]:bg-linear-to-r data-[state=active]:from-blue-500 data-[state=active]:to-sky-500 data-[state=active]:text-white dark:data-[state=active]:from-blue-600 dark:data-[state=active]:to-cyan-500 dark:data-[state=active]:text-white"
                            >
                                <Mars class="size-4" />
                                Laki-laki
                            </TabsTrigger>
                            <TabsTrigger
                                value="P"
                                class="gap-2 text-sm data-[state=active]:bg-linear-to-r data-[state=active]:from-pink-500 data-[state=active]:to-rose-500 data-[state=active]:text-white dark:data-[state=active]:from-pink-600 dark:data-[state=active]:to-fuchsia-500 dark:data-[state=active]:text-white"
                            >
                                <Venus class="size-4" />
                                Perempuan
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                    <p v-if="error('jk').length" class="text-sm text-destructive">{{ error('jk')[0] }}</p>
                </div>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">Tempat Lahir</span>
                    <Input v-model="form.tmp_lahir" placeholder="Masukkan tempat lahir" />
                    <p v-if="error('tmp_lahir').length" class="text-sm text-destructive">{{ error('tmp_lahir')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">Tanggal Lahir</span>
                    <Popover v-model:open="tanggalLahir.open">
                        <PopoverTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-start bg-background text-left font-normal">
                                <CalendarIcon class="size-4 text-muted-foreground" />
                                {{ tanggalLahir.label }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar
                                v-model="tanggalLahir.value"
                                layout="month-and-year"
                                :placeholder="tanggalLahirPlaceholder"
                                :default-placeholder="tanggalLahirPlaceholder"
                            />
                        </PopoverContent>
                    </Popover>
                    <p v-if="error('tgl_lahir').length" class="text-sm text-destructive">{{ error('tgl_lahir')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">Nama Ibu</span>
                    <Input v-model="form.nm_ibu" placeholder="Masukkan nama ibu kandung" />
                    <p v-if="error('nm_ibu').length" class="text-sm text-destructive">{{ error('nm_ibu')[0] }}</p>
                </label>
            </CardContent>
        </Card>

        <Card class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <IdCard class="size-4" />
                    Kontak & Cara Bayar
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">NIK</span>
                    <div class="flex gap-2">
                        <Input v-model="form.no_ktp" inputmode="numeric" placeholder="Masukkan NIK" />
                        <Button type="button" variant="secondary" :disabled="bpjsLoading !== null" @click="autofillBpjs('nik')">
                            <Spinner v-if="bpjsLoading === 'nik'" />
                            <Search v-else class="size-4" />
                            BPJS
                        </Button>
                    </div>
                    <p v-if="error('no_ktp').length" class="text-sm text-destructive">{{ error('no_ktp')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">No BPJS</span>
                    <div class="flex gap-2">
                        <Input v-model="form.no_peserta" inputmode="numeric" placeholder="Masukkan nomor BPJS" />
                        <Button type="button" variant="secondary" :disabled="bpjsLoading !== null" @click="autofillBpjs('card')">
                            <Spinner v-if="bpjsLoading === 'card'" />
                            <Search v-else class="size-4" />
                            BPJS
                        </Button>
                    </div>
                    <p v-if="error('no_peserta').length" class="text-sm text-destructive">{{ error('no_peserta')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">Cara Bayar</span>
                    <AppSelect v-model="form.kd_pj" :options="references.payments" placeholder="Pilih cara bayar" />
                    <p v-if="error('kd_pj').length" class="text-sm text-destructive">{{ error('kd_pj')[0] }}</p>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-medium">No HP</span>
                    <Input v-model="form.no_tlp" inputmode="tel" placeholder="Masukkan nomor telepon" />
                    <p v-if="error('no_tlp').length" class="text-sm text-destructive">{{ error('no_tlp')[0] }}</p>
                </label>

                <label class="grid gap-2 md:col-span-2">
                    <span class="text-sm font-medium">Alamat</span>
                    <Textarea v-model="form.alamat" class="min-h-20" placeholder="Masukkan alamat lengkap pasien" />
                    <p v-if="error('alamat').length" class="text-sm text-destructive">{{ error('alamat')[0] }}</p>
                </label>
            </CardContent>
        </Card>

        <div v-if="mode === 'create'" class="grid gap-3 rounded-lg border border-border bg-card p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <Label class="flex items-center justify-between gap-3 rounded-md border p-3">
                    <span>Detail alamat</span>
                    <Switch v-model="tampilAlamatDetail" />
                </Label>
                <Label class="flex items-center justify-between gap-3 rounded-md border p-3">
                    <span>Penanggung jawab</span>
                    <Switch v-model="tampilPenanggungJawab" />
                </Label>
                <Label class="flex items-center justify-between gap-3 rounded-md border p-3">
                    <span>Asuransi tambahan</span>
                    <Switch v-model="tampilAsuransiTambahan" />
                </Label>
                <Label class="flex items-center justify-between gap-3 rounded-md border p-3">
                    <span>Data penunjang</span>
                    <Switch v-model="tampilPenunjang" />
                </Label>
            </div>
        </div>

        <Card v-if="tampilAlamatDetail" class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="text-base">Detail Alamat</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kelurahan</span>
                    <WilayahAsyncCombobox
                        v-model="form.kd_kel"
                        jenis="kelurahan"
                        placeholder="Pilih kelurahan"
                        search-placeholder="Cari kelurahan"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kecamatan</span>
                    <WilayahAsyncCombobox
                        v-model="form.kd_kec"
                        jenis="kecamatan"
                        placeholder="Pilih kecamatan"
                        search-placeholder="Cari kecamatan"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kabupaten</span>
                    <WilayahAsyncCombobox
                        v-model="form.kd_kab"
                        jenis="kabupaten"
                        placeholder="Pilih kabupaten"
                        search-placeholder="Cari kabupaten"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Propinsi</span>
                    <WilayahAsyncCombobox
                        v-model="form.kd_prop"
                        jenis="propinsi"
                        placeholder="Pilih propinsi"
                        search-placeholder="Cari propinsi"
                    />
                </label>
            </CardContent>
        </Card>

        <Card v-if="tampilPenanggungJawab" class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="text-base">Penanggung Jawab</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex items-start justify-between gap-4 md:col-span-2 xl:col-span-3 rounded-md border border-dashed p-3">
                    <div class="grid gap-1">
                        <Label class="text-sm font-medium">Alamat mengikuti pasien</Label>
                        <p class="text-xs text-muted-foreground">
                            Aktifkan bila alamat penanggung jawab sama dengan alamat pasien. Nilai alamat PJ akan mengikuti alamat utama pasien secara otomatis.
                        </p>
                    </div>
                    <Switch v-model="alamatPenanggungJawabMengikutiPasien" />
                </div>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Hubungan</span>
                    <AppSelect v-model="form.keluarga" :options="familyOptions" placeholder="Pilih hubungan" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Nama</span>
                    <Input v-model="form.namakeluarga" placeholder="Masukkan nama penanggung jawab" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Pekerjaan</span>
                    <Input v-model="form.pekerjaanpj" placeholder="Masukkan pekerjaan penanggung jawab" />
                </label>
                <label class="grid gap-2 md:col-span-2 xl:col-span-3">
                    <span class="text-sm font-medium">Alamat PJ</span>
                    <Textarea
                        v-model="form.alamatpj"
                        class="min-h-20"
                        placeholder="Masukkan alamat penanggung jawab"
                        :disabled="alamatPenanggungJawabMengikutiPasien"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kelurahan PJ</span>
                    <WilayahAsyncCombobox
                        v-model="form.kelurahanpj"
                        jenis="kelurahan"
                        placeholder="Pilih kelurahan PJ"
                        search-placeholder="Cari kelurahan PJ"
                        :disabled="alamatPenanggungJawabMengikutiPasien"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kecamatan PJ</span>
                    <WilayahAsyncCombobox
                        v-model="form.kecamatanpj"
                        jenis="kecamatan"
                        placeholder="Pilih kecamatan PJ"
                        search-placeholder="Cari kecamatan PJ"
                        :disabled="alamatPenanggungJawabMengikutiPasien"
                    />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Kabupaten PJ</span>
                    <WilayahAsyncCombobox
                        v-model="form.kabupatenpj"
                        jenis="kabupaten"
                        placeholder="Pilih kabupaten PJ"
                        search-placeholder="Cari kabupaten PJ"
                        :disabled="alamatPenanggungJawabMengikutiPasien"
                    />
                </label>
            </CardContent>
        </Card>

        <Card v-if="tampilAsuransiTambahan" class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <CreditCard class="size-4" />
                    Asuransi Tambahan
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Email</span>
                    <Input v-model="form.email" type="email" placeholder="Masukkan alamat email" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Pekerjaan</span>
                    <Input v-model="form.pekerjaan" placeholder="Masukkan pekerjaan pasien" />
                </label>
            </CardContent>
        </Card>

        <Card v-if="tampilPenunjang" class="border-border/70 shadow-sm">
            <CardHeader>
                <CardTitle class="text-base">Data Penunjang</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Golongan Darah</span>
                    <AppSelect v-model="form.gol_darah" :options="bloodOptions" placeholder="Pilih golongan darah" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Agama</span>
                    <AppSelect v-model="form.agama" :options="religionOptions" placeholder="Pilih agama" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Status Nikah</span>
                    <AppSelect v-model="form.stts_nikah" :options="maritalOptions" placeholder="Pilih status" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Pendidikan</span>
                    <AppSelect v-model="form.pnd" :options="educationOptions" placeholder="Pilih pendidikan" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Cacat Fisik</span>
                    <AppSelect v-model="form.cacat_fisik" :options="references.physicalDisabilities" placeholder="Pilih data" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Suku Bangsa</span>
                    <AppSelect v-model="form.suku_bangsa" :options="references.ethnicities" placeholder="Pilih data" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Bahasa</span>
                    <AppSelect v-model="form.bahasa_pasien" :options="references.languages" placeholder="Pilih bahasa" />
                </label>
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Instansi Pasien</span>
                    <AppSelect v-model="form.perusahaan_pasien" :options="references.companies" placeholder="Pilih instansi" />
                </label>
            </CardContent>
        </Card>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                <Save v-else class="size-4" />
                {{ form.processing ? 'Menyimpan...' : 'Simpan Data Pasien' }}
            </Button>
        </div>
    </form>
</template>
