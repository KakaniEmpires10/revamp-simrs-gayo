<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { AppSelectOption } from '@/components/ui/form';

const props = withDefaults(
    defineProps<{
        options: AppSelectOption[];
        placeholder: string;
        searchPlaceholder?: string;
        emptyText?: string;
        allLabel?: string;
    }>(),
    {
        searchPlaceholder: 'Cari data...',
        emptyText: 'Data tidak ditemukan.',
        allLabel: 'Semua',
    },
);

const modelValue = defineModel<string>({ default: '' });
const open = ref(false);

const selectedOption = computed(() => props.options.find((option) => option.value === modelValue.value));

function selectValue(value: string): void {
    modelValue.value = value;
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button variant="outline" class="h-10 w-full justify-between rounded-lg bg-background px-3">
                <span class="truncate text-left">
                    {{ selectedOption?.label ?? placeholder }}
                </span>
                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[--reka-popover-trigger-width] min-w-64 p-0" align="start">
            <Command>
                <CommandInput :placeholder="searchPlaceholder" />
                <CommandList>
                    <CommandEmpty>{{ emptyText }}</CommandEmpty>
                    <CommandGroup>
                        <CommandItem value="__all__" @select="selectValue('')">
                            <Check :class="cn('size-4', modelValue === '' ? 'opacity-100' : 'opacity-0')" />
                            {{ allLabel }}
                        </CommandItem>
                        <CommandItem
                            v-for="option in options"
                            :key="option.value"
                            :value="`${option.value} ${option.label}`"
                            :disabled="option.disabled"
                            @select="selectValue(option.value)"
                        >
                            <Check :class="cn('size-4', option.value === modelValue ? 'opacity-100' : 'opacity-0')" />
                            <span class="truncate">{{ option.label }}</span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
