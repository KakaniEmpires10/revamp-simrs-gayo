<script setup lang="ts">
import { computed } from 'vue';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePlatform } from '@/composables/usePlatform';
import type { AppSelectOption } from './types';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        options: AppSelectOption[];
        placeholder?: string;
    }>(),
    {
        placeholder: 'Pilih data',
    },
);

const modelValue = defineModel<string>({ default: '' });
const { isTouch } = usePlatform();

const emptyValue = '__empty__';

const selectValue = computed({
    get: () => modelValue.value || emptyValue,
    set: (value: string) => {
        modelValue.value = value === emptyValue ? '' : value;
    },
});

</script>

<template>
    <NativeSelect v-if="isTouch" v-bind="$attrs" v-model="modelValue">
        <NativeSelectOption value="">
            {{ placeholder }}
        </NativeSelectOption>
        <NativeSelectOption
            v-for="option in options"
            :key="option.value"
            :value="option.value"
            :disabled="option.disabled"
        >
            {{ option.label }}
        </NativeSelectOption>
    </NativeSelect>

    <Select v-else v-model="selectValue">
        <SelectTrigger v-bind="$attrs" class="w-full">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value === '' ? emptyValue : option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
