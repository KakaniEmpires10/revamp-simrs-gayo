<script setup lang="ts">
import type { AppAsyncComboboxOption } from './types';
import type { HTMLAttributes } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { Search } from '@lucide/vue';

const props = withDefaults(defineProps<{
    modelValue: string;
    search?: string;
    open?: boolean;
    options: AppAsyncComboboxOption[];
    displayValue?: string;
    placeholder: string;
    searchPlaceholder: string;
    disabled?: boolean;
    loading?: boolean;
    error?: string;
    minSearchLength?: number;
    debounceMs?: number;
    loadingTitle?: string;
    loadingDescription?: string;
    promptTitle?: string;
    promptDescription?: string;
    errorTitle?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    contentClass?: HTMLAttributes['class'];
}>(), {
    search: undefined,
    open: undefined,
    displayValue: '',
    disabled: false,
    loading: false,
    error: '',
    minSearchLength: 3,
    debounceMs: 350,
    loadingTitle: 'Memuat data',
    loadingDescription: 'Mengambil referensi dari server...',
    promptTitle: 'Masukkan kata kunci',
    promptDescription: '',
    errorTitle: 'Data gagal dimuat',
    emptyTitle: 'Data tidak ditemukan',
    emptyDescription: 'Coba gunakan kata kunci lain.',
    contentClass: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:search': [value: string];
    'update:open': [value: boolean];
    search: [keyword: string];
    select: [option: AppAsyncComboboxOption];
}>();

const internalOpen = ref(false);
const internalSearch = ref('');

const isOpen = computed({
    get() {
        return props.open ?? internalOpen.value;
    },
    set(value: boolean) {
        internalOpen.value = value;
        emit('update:open', value);
    },
});

const searchTerm = computed({
    get() {
        return props.search ?? internalSearch.value;
    },
    set(value: string) {
        internalSearch.value = value;
        emit('update:search', value);
    },
});

const normalizedSearch = computed(() => searchTerm.value.trim());
const isTooShort = computed(() => normalizedSearch.value.length < props.minSearchLength);
const buttonText = computed(() => props.displayValue || props.placeholder);

const debouncedSearch = useDebounceFn((keyword: string) => {
    if (!isOpen.value) {
        return;
    }

    emit('search', keyword);
}, props.debounceMs, { maxWait: 1200 });

watch(searchTerm, (keyword) => {
    if (!isOpen.value) {
        return;
    }

    void debouncedSearch(keyword);
});

watch(isOpen, (open) => {
    if (open) {
        searchTerm.value = '';
    }
});

function selectOption(option: AppAsyncComboboxOption): void {
    if (option.disabled) {
        return;
    }

    emit('update:modelValue', option.value);
    emit('select', option);

    isOpen.value = false;
}
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button type="button" variant="outline" role="combobox" :aria-expanded="isOpen"
                class="w-full justify-between overflow-hidden"
                :disabled="disabled"
                >
                <span class="truncate text-left min-w-0">
                    {{ buttonText }}
                </span>

                <Spinner v-if="loading" class="size-4 shrink-0" />
                <ChevronsUpDown v-else class="size-4 shrink-0 text-muted-foreground" />
            </Button>
        </PopoverTrigger>

        <PopoverContent
            :class="cn('w-[--reka-popover-trigger-width] min-w-80 p-0', contentClass)"
            align="start"
        >
            <div class="border-b p-2">
                <Input
                    v-model="searchTerm"
                    :placeholder="searchPlaceholder"
                    autocomplete="off"
                    autofocus
                    class="relative h-9 pl-7"
                />
                <Search class="absolute left-4 top-4.5 size-4 text-muted-foreground" />
            </div>

            <div class="max-h-72 overflow-y-auto p-1">
                <div v-if="loading" class="px-3 py-6 text-center text-sm text-muted-foreground">
                    <Spinner class="mx-auto mb-2" />
                    <p class="font-medium text-foreground">
                        {{ loadingTitle }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed">
                        {{ loadingDescription }}
                    </p>
                </div>

                <div v-else-if="isTooShort" class="px-3 py-6 text-center text-sm text-muted-foreground">
                    <p class="font-medium text-foreground">
                        {{ promptTitle }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed">
                        {{ promptDescription || `Ketik minimal ${minSearchLength} karakter untuk mencari data.` }}
                    </p>
                </div>

                <div v-else-if="error" class="px-3 py-6 text-center text-sm text-destructive">
                    <p class="font-medium">
                        {{ errorTitle }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed">
                        {{ error }}
                    </p>
                </div>

                <div v-else-if="options.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">
                    <p class="font-medium text-foreground">
                        {{ emptyTitle }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed">
                        {{ emptyDescription }}
                    </p>
                </div>

                <template v-else>
                    <button
                        v-for="option in options"
                        :key="option.value"
                        type="button"
                        class="hover:bg-accent hover:text-accent-foreground flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="option.disabled"
                        @click="selectOption(option)"
                    >
                        <Check
                            :class="cn(
                                'size-4 shrink-0',
                                option.value === modelValue ? 'opacity-100' : 'opacity-0',
                            )"
                        />

                        <span class="min-w-0">
                            <span class="block truncate font-medium">
                                {{ option.label }}
                            </span>
                            <span v-if="option.description" class="block truncate text-xs text-muted-foreground">
                                {{ option.description }}
                            </span>
                        </span>
                    </button>
                </template>
            </div>
        </PopoverContent>
    </Popover>
</template>
