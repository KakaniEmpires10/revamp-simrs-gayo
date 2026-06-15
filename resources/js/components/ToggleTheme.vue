<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Terang' },
    { value: 'dark', Icon: Moon, label: 'Gelap' },
    { value: 'system', Icon: Monitor, label: 'Sistem' },
] as const;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                class="relative flex size-9 items-center justify-center rounded-full transition-colors hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="Ubah tampilan">
                <!-- Sun -->
                <Sun :class="[
                    'absolute size-[18px] transition-all duration-300 ease-in-out',
                    appearance === 'light'
                        ? 'rotate-0 scale-100 opacity-100'
                        : 'rotate-90 scale-0 opacity-0',
                ]" />
                <!-- Moon -->
                <Moon :class="[
                    'absolute size-[18px] transition-all duration-300 ease-in-out',
                    appearance === 'dark'
                        ? 'rotate-0 scale-100 opacity-100'
                        : '-rotate-90 scale-0 opacity-0',
                ]" />
                <!-- Monitor -->
                <Monitor :class="[
                    'absolute size-[18px] transition-all duration-300 ease-in-out',
                    appearance === 'system'
                        ? 'rotate-0 scale-100 opacity-100'
                        : 'rotate-45 scale-0 opacity-0',
                ]" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="min-w-40 rounded-lg" :side-offset="8">
            <DropdownMenuLabel class="text-xs text-muted-foreground">
                Tampilan
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem v-for="{ value, Icon, label } in tabs" :key="value" @click="updateAppearance(value)"
                class="flex items-center gap-2 cursor-pointer"
                :class="appearance === value ? 'font-medium text-foreground' : 'text-muted-foreground'">
                <component :is="Icon" class="size-4 shrink-0" />
                <span>{{ label }}</span>
                <!-- Dot aktif -->
                <span v-if="appearance === value" class="ml-auto size-1.5 rounded-full bg-primary" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
