<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { index as daftarPasienIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { index as dataPasienIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import FormDataPasien from '@/components/pendaftaran/data-pasien/FormDataPasien.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import type { AppSelectOption } from '@/components/ui/form/types';
import { dashboard } from '@/routes';
import type { RegistrationPatient } from '@/types';

type Mode = 'create' | 'edit';

type PatientReferences = {
    payments: AppSelectOption[];
    physicalDisabilities: AppSelectOption[];
    ethnicities: AppSelectOption[];
    languages: AppSelectOption[];
    companies: AppSelectOption[];
};

const props = defineProps<{
    mode: Mode;
    patient: RegistrationPatient | null;
    nextMedicalRecordNumber: string | null;
    references: PatientReferences;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda', href: dashboard() },
            { title: 'Pendaftaran', href: daftarPasienIndex() },
            { title: 'Data Pasien', href: dataPasienIndex() },
            { title: 'Tambah Pasien', href: '#' },
        ],
    },
});

const title = props.mode === 'edit' ? 'Edit Data Pasien' : 'Tambah Pasien Baru';
</script>

<template>
    <Head :title="title" />

    <div class="grid gap-5">
        <PageHeader
            :title="title"
            description="Kelola identitas pasien pada tabel legacy pasien."
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link :href="dataPasienIndex()">
                        <ArrowLeft class="size-4" />
                        Kembali
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FormDataPasien
            :mode="mode"
            :patient="patient"
            :next-medical-record-number="nextMedicalRecordNumber"
            :references="references"
        />
    </div>
</template>
