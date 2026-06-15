<script setup lang="ts">
import { Clock3, ExternalLink, History } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

defineProps<{
    urls: {
        lastThree: string;
        all: string;
    };
}>();

function bukaRiwayat(url: string): void {
    window.open(url, '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button type="button" class="fixed right-5 bottom-5 z-40 shadow-lg">
                <History class="size-4" />
                Riwayat
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuItem @click="bukaRiwayat(urls.lastThree)">
                <Clock3 class="size-4" />
                3 Riwayat Terakhir
            </DropdownMenuItem>
            <DropdownMenuItem @click="bukaRiwayat(urls.all)">
                <ExternalLink class="size-4" />
                Seluruh Riwayat
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
