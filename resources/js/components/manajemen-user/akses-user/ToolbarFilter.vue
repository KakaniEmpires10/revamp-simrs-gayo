<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed } from 'vue';
import TombolAksiFilter from '@/components/shared/TombolAksiFilter.vue';
import { AppSelect } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import type { GrupAuth } from '@/types';

const props = withDefaults(
    defineProps<{
        groups: Pick<GrupAuth, 'id' | 'name' | 'alias'>[];
        jenisAkun: {
            value: string;
            label: string;
        }[];
        loading?: boolean;
    }>(),
    {
        loading: false,
    },
);

defineEmits<{
    apply: [];
    clear: [];
}>();

const search = defineModel<string>('search', { required: true });
const aliasGroup = defineModel<string>('aliasGroup', { required: true });
const status = defineModel<string>('status', { required: true });

const groupOptions = computed(() =>
    props.groups.map((group) => ({
        value: group.alias,
        label: group.name,
    })),
);
</script>

<template>
    <form
        class="flex flex-col gap-4 rounded-lg border bg-card p-4 shadow-sm md:flex-row md:items-end"
        @submit.prevent="$emit('apply')"
    >
        <div class="min-w-0 flex-1">
            <div class="group relative">
                <Search
                    class="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground transition-colors group-focus-within:text-primary"
                />
                <Input
                    v-model="search"
                    class="h-9 rounded-lg bg-background pl-11"
                    placeholder="Cari ID user atau nama..."
                />
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 md:flex md:items-end">
            <label class="flex min-w-44 flex-col gap-2">
                <span
                    class="shrink-0 text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Level
                </span>
                <AppSelect
                    v-model="aliasGroup"
                    :options="groupOptions"
                    placeholder="Semua Data"
                />
            </label>

            <label class="flex min-w-36 flex-col gap-2">
                <span
                    class="shrink-0 text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    Jenis
                </span>
                <AppSelect
                    v-model="status"
                    :options="jenisAkun"
                    placeholder="Semua Data"
                />
            </label>

            <TombolAksiFilter
                :processing="loading"
                submit
                @bersihkan="$emit('clear')"
            />
        </div>
    </form>
</template>
