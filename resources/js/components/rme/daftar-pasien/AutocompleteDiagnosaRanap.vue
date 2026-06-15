<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { Check, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { reference } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { Badge } from '@/components/ui/badge';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { RegistrationOption } from '@/types';

const diagnosa = defineModel<string>('diagnosa', { required: true });
const kodePenyakit = defineModel<string>('kodePenyakit', { default: '' });

const props = withDefaults(defineProps<{
    placeholder?: string;
}>(), {
    placeholder: 'Ketik diagnosa bebas atau cari ICD, contoh: A00 atau cholera',
});

const open = ref(false);
const loading = ref(false);
const error = ref('');
const options = ref<RegistrationOption[]>([]);
const inputRef = ref<InstanceType<typeof Input> | null>(null);
const mode = computed(() => kodePenyakit.value ? 'referensi' : 'manual');

watch(diagnosa, (value) => {
    if (!value) {
        kodePenyakit.value = '';
        options.value = [];
        error.value = '';
        open.value = false;
    }
});

async function loadDiagnoses(query: string): Promise<void> {
    if (query.trim().length < 2) {
        options.value = [];
        error.value = '';
        open.value = false;

        return;
    }

    loading.value = true;
    error.value = '';
    open.value = true;

    try {
        const response = await fetch(reference.url({ query: { type: 'diagnosis', query } }), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat referensi diagnosa.');
        }

        const payload = await response.json() as { data?: RegistrationOption[] };
        options.value = payload.data ?? [];
        open.value = true;
    } catch {
        error.value = 'Gagal memuat referensi diagnosa.';
        options.value = [];
        open.value = true;
    } finally {
        loading.value = false;
    }
}

const cariDiagnosa = useDebounceFn((query: string) => {
    void loadDiagnoses(query);
}, 300);

function ubahDiagnosa(value: string | number): void {
    diagnosa.value = String(value);
    kodePenyakit.value = '';
    cariDiagnosa(String(value));
}

function pilihDiagnosa(option: RegistrationOption): void {
    kodePenyakit.value = option.value;
    diagnosa.value = option.label;
    open.value = false;
}

function onInteractOutside(event: Event): void {
    const inputEl = (inputRef.value as any)?.$el ?? inputRef.value;

    if (inputEl && (event.target as Node | null) && inputEl.contains(event.target as Node)) {
        event.preventDefault();

        return;
    }

    open.value = false;
}
</script>

<template>
    <div class="grid gap-2">
        <Popover v-model:open="open">
            <PopoverAnchor as-child>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        ref="inputRef"
                        :model-value="diagnosa"
                        class="pl-9"
                        :placeholder="placeholder"
                        autocomplete="off"
                        @focus="open = options.length > 0 || loading || Boolean(error)"
                        @update:model-value="ubahDiagnosa"
                        @keydown.escape="open = false"
                        @keydown.tab="open = false"
                    />
                </div>
            </PopoverAnchor>
            <PopoverContent
                class="w-[--reka-popover-trigger-width] p-0"
                align="start"
                :disable-outside-pointer-events="false"
                @interact-outside="onInteractOutside"
                @open-auto-focus.prevent
            >
                <Command :should-filter="false">
                    <CommandList>
                        <CommandEmpty>
                            {{ loading ? 'Memuat referensi diagnosa...' : (error || 'Tidak ada hasil. Lanjutkan sebagai input manual.') }}
                        </CommandEmpty>
                        <CommandGroup>
                            <CommandItem
                                v-for="option in options"
                                :key="option.value"
                                :value="`${option.value} ${option.label}`"
                                @select="pilihDiagnosa(option)"
                            >
                                <Check :class="cn('size-4 shrink-0', option.value === kodePenyakit ? 'opacity-100' : 'opacity-0')" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ option.label }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ option.description }}</p>
                                </div>
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>

        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <Badge :variant="mode === 'referensi' ? 'soft-success' : 'muted'" size="sm">
                {{ mode === 'referensi' ? 'Referensi ICD' : 'Manual' }}
            </Badge>
            <span>Bisa memilih ICD 10 atau mengisi diagnosa manual.</span>
        </div>
    </div>
</template>
