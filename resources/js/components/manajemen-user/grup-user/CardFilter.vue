<script setup lang="ts">
import { Search } from '@lucide/vue';
import { Input } from '@/components/ui/input';

defineProps<{
    total: number;
    filtered: number;
}>();

const search = defineModel<string>('search', { required: true });
</script>

<template>
    <section
        class="flex flex-col gap-3 rounded-lg border bg-card p-4 md:flex-row md:items-center md:justify-between"
    >
        <div class="flex w-full gap-2 md:max-w-md">
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Cari nama, alias, atau keterangan"
                />
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ filtered }} dari {{ total }} level akses
        </p>
    </section>
</template>
