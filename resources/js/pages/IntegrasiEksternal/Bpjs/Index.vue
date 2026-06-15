<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import GridSubmenuBpjs from '@/components/integrasi-eksternal/bpjs/GridSubmenuBpjs.vue';
import PanelStatusIntegrasi from '@/components/integrasi-eksternal/bpjs/PanelStatusIntegrasi.vue';
import type { BpjsSubmenuGroup } from '@/components/integrasi-eksternal/bpjs/tipe';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrasi Eksternal', href: bpjsIndex() },
            { title: 'BPJS', href: bpjsIndex() },
        ],
    },
});

const props = defineProps<{
    antrolConfigured: boolean;
    vclaimConfigured: boolean;
    menuGroups: BpjsSubmenuGroup[];
}>();

const search = ref('');

const filteredMenuGroups = computed<BpjsSubmenuGroup[]>(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) {
        return props.menuGroups;
    }

    return props.menuGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) =>
                [
                    group.title,
                    item.title,
                    item.description,
                    item.badge,
                ].some((value) => value.toLowerCase().includes(keyword)),
            ),
        }))
        .filter((group) => group.items.length > 0);
});
</script>

<template>
    <div class="contents">
        <PageHeader
            title="BPJS"
            description="Pusat konfigurasi integrasi VClaim, JKN Online, dan Bridge BPJS untuk modul pendaftaran dan modul klinis."
        >
            <template #actions>
                <div class="relative w-full sm:w-80">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        class="h-10 w-full rounded-lg bg-card pl-9 shadow-sm"
                        placeholder="Cari submenu BPJS"
                    />
                </div>
            </template>
        </PageHeader>

        <section>
            <PanelStatusIntegrasi
                :antrol-configured="antrolConfigured"
                :vclaim-configured="vclaimConfigured"
            />
        </section>

        <GridSubmenuBpjs :groups="filteredMenuGroups" />
    </div>
</template>
