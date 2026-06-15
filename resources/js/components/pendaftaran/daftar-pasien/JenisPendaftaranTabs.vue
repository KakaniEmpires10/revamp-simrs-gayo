<script setup lang="ts">
import { Building2, Siren } from '@lucide/vue';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { RegistrationType } from '@/types';

const modelValue = defineModel<RegistrationType>({ required: true });
</script>

<template>
    <Tabs v-model="modelValue" class="w-full sm:w-auto">
        <TabsList class="grid h-9 w-full grid-cols-2 sm:w-auto">
            <TabsTrigger value="rawat_jalan" class="gap-1.5 px-3 text-xs sm:text-sm">
                <Building2 class="size-4" />
                Rawat Jalan
            </TabsTrigger>
            <TabsTrigger value="igd" class="gap-1.5 px-3 text-xs sm:text-sm">
                <Siren class="size-4" />
                IGD
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
