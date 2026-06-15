<script setup lang="ts">
import { AppSelect } from '@/components/ui/form';
import type { AppSelectOption } from '@/components/ui/form';

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        options: AppSelectOption[];
        placeholder?: string;
    }>(),
    {
        placeholder: 'Pilih data',
    },
);

const modelValue = defineModel<string>({ required: true });
</script>

<template>
    <AppSelect
        v-bind="$attrs"
        v-model="modelValue"
        :options="options"
        :placeholder="placeholder"
    />
</template>
