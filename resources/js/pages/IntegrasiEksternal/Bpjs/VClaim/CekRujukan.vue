<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { referralCheck } from '@/actions/App/Modules/Bpjs/Http/Controllers/VClaimController';
import KartuPesertaRujukan from '@/components/integrasi-eksternal/bpjs/vclaim/KartuPesertaRujukan.vue';
import TabelCekRujukan from '@/components/integrasi-eksternal/bpjs/vclaim/TabelCekRujukan.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

type ReferralRow = Record<string, string | number | Record<string, string | number | null | undefined> | null | undefined>;
type Participant = Record<string, string | number | Record<string, string | number | null | undefined> | null | undefined>;

const props = defineProps<{
    result: {
        metadata: {
            code: string;
            message: string;
        };
        rows: ReferralRow[];
        peserta: Participant;
    } | null;
    filters: {
        identifier_type: string;
        identifier: string;
        source: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
            { title: 'Cek Rujukan', href: referralCheck() },
        ],
    },
});

const identifierType = ref(props.filters.identifier_type);
const identifier = ref(props.filters.identifier);
const source = ref(props.filters.source);
const loading = ref(false);

watch(
    () => props.filters,
    (filters) => {
        identifierType.value = filters.identifier_type;
        identifier.value = filters.identifier;
        source.value = filters.source;
    },
);

function applyFilters(): void {
    identifier.value = identifier.value.replace(/\D/g, '');

    router.get(
        referralCheck.url(),
        {
            identifier_type: identifierType.value,
            identifier: identifier.value,
            source: source.value,
        },
        {
            only: ['result', 'filters'],
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                loading.value = true;
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function bersihkanFilter(): void {
    identifierType.value = 'card';
    identifier.value = '';
    source.value = 'pcare';
    applyFilters();
}

function setIdentifierType(value: string | number): void {
    identifierType.value = String(value);
    identifier.value = '';
}
</script>

<template>
    <Head title="Cek Rujukan" />

    <div class="contents">
        <PageHeader
            title="Cek Rujukan"
            description="Cari rujukan peserta berdasarkan nomor kartu dari faskes tingkat pertama atau rujukan rumah sakit."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="bpjsIndex()">
                        <ArrowLeft class="size-4" />
                        Kembali
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3">
            <Card>
                <CardContent class="grid gap-3 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] md:items-end">
                    <div class="grid gap-2">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-medium">Identitas Peserta</span>
                            <Tabs :model-value="identifierType" @update:model-value="setIdentifierType">
                                <TabsList>
                                    <TabsTrigger value="card">No Kartu</TabsTrigger>
                                    <TabsTrigger value="nik">No KTP</TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </div>
                        <Input
                            v-model="identifier"
                            inputmode="numeric"
                            :maxlength="identifierType === 'nik' ? 16 : 13"
                            :placeholder="identifierType === 'nik' ? '16 digit NIK / No KTP' : '13 digit nomor kartu BPJS'"
                            @input="identifier = identifier.replace(/\D/g, '')"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <label class="grid gap-2 text-sm font-medium">
                        Sumber Rujukan
                        <AppSelect
                            v-model="source"
                            :options="[
                                { label: 'Faskes Tingkat Pertama', value: 'pcare' },
                                { label: 'Rumah Sakit', value: 'rs' },
                            ]"
                        />
                    </label>
                    <TombolAksiFilter
                        :processing="loading"
                        :disabled="identifier.length !== (identifierType === 'nik' ? 16 : 13)"
                        @terapkan="applyFilters"
                        @bersihkan="bersihkanFilter"
                    />
                </CardContent>
            </Card>

            <KartuPesertaRujukan
                v-if="result?.peserta && Object.keys(result.peserta).length"
                :peserta="result.peserta"
            />

            <TabelCekRujukan :result="result" :loading="loading" />
        </div>
    </div>
</template>
