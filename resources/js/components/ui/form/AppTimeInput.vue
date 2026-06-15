<script setup lang="ts">
import { Input } from '@/components/ui/input';

defineOptions({
    inheritAttrs: false,
});

const modelValue = defineModel<string>({ default: '' });
</script>

<template>
    <Input v-bind="$attrs" v-model="modelValue" type="time" />
</template>
