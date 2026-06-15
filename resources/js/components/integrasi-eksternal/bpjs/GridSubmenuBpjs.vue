<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { ambilIconBpjs } from './petaIcon';
import type { BpjsSubmenuGroup } from './tipe';

defineProps<{
    groups: BpjsSubmenuGroup[];
}>();
</script>

<template>
    <section v-if="groups.length" class="space-y-6">
        <div v-for="group in groups" :key="group.title" class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold tracking-tight">
                    {{ group.title }}
                </h2>
                <Badge variant="muted">
                    {{ group.items.length }} Menu
                </Badge>
            </div>

            <Separator />

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="item in group.items"
                    :key="item.href"
                    class="group relative overflow-hidden border-primary/10 bg-card transition-colors hover:border-primary/50 hover:bg-primary/[0.03]"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 240 90"
                        class="pointer-events-none absolute -right-12 -bottom-12 h-24 w-64 rotate-[-7deg] text-primary opacity-[0.045] transition-opacity group-hover:opacity-[0.08]"
                        preserveAspectRatio="none"
                    >
                        <path
                            d="M0 62 C28 36 50 82 78 58 C106 34 126 78 154 54 C184 28 208 72 240 46 L240 90 L0 90 Z"
                            fill="currentColor"
                        />
                        <path
                            d="M0 42 C28 16 52 58 80 36 C108 14 128 54 156 32 C184 10 210 48 240 26"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="5"
                            stroke-linecap="round"
                        />
                    </svg>
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-primary/25" />
                    <Link :href="item.href" class="block h-full">
                        <CardHeader class="relative gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary ring-1 ring-primary/15">
                                        <component :is="ambilIconBpjs(item.icon)" class="size-4" />
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <CardTitle class="text-sm leading-5">
                                            {{ item.title }}
                                        </CardTitle>
                                        <Badge :variant="item.badgeVariant" size="sm" rounded="md">
                                            {{ item.badge }}
                                        </Badge>
                                    </div>
                                </div>
                                <ChevronRight class="mt-1 size-4 shrink-0 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent class="relative">
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ item.description }}
                            </p>
                        </CardContent>
                    </Link>
                </Card>
            </div>
        </div>
    </section>

    <section
        v-else
        class="rounded-lg border border-dashed bg-card p-10 text-center text-sm text-muted-foreground"
    >
        Submenu BPJS tidak ditemukan.
    </section>
</template>
