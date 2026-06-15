<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { PatientSearchMode } from '@/types';

const modelValue = defineModel<PatientSearchMode>({ required: true });
</script>

<template>
    <Tabs v-model="modelValue">
        <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="nik">NIK</TabsTrigger>
            <TabsTrigger value="no_peserta">No Peserta</TabsTrigger>
            <TabsTrigger value="no_rm">No RM</TabsTrigger>
        </TabsList>
    </Tabs>
</template>
