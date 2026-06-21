<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, ClipboardPenLine, FileCheck2, FlaskConical, Pill, ScanLine, Stethoscope } from '@lucide/vue';
import { computed } from 'vue';
import type { PemeriksaanMenu } from '@/types';

const props = defineProps<{
    menus: PemeriksaanMenu[];
    activeMenu: string;
    orientation?: 'horizontal' | 'vertical';
}>();

const icons = {
    ChevronRight,
    ClipboardPenLine,
    FileCheck2,
    FlaskConical,
    Pill,
    ScanLine,
    Stethoscope,
};

const menuList = computed(() => props.menus);
const activeParent = computed(() => {
    return menuList.value.find((menu) => menu.key === props.activeMenu || menu.children?.some((child) => child.key === props.activeMenu)) ?? menuList.value[0];
});

function isActive(menu: PemeriksaanMenu): boolean {
    return menu.key === props.activeMenu || Boolean(menu.children?.some((child) => child.key === props.activeMenu));
}

function menuIcon(menu: PemeriksaanMenu) {
    return icons[menu.icon as keyof typeof icons] ?? ClipboardPenLine;
}
</script>

<template>
    <nav v-if="orientation === 'vertical'" class="w-full">
        <div class="grid gap-1">
            <template v-for="menu in menuList" :key="menu.key">
                <details v-if="menu.children?.length" class="group" :open="isActive(menu)">
                    <summary
                        class="flex cursor-pointer list-none items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive(menu) ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    >
                        <component :is="menuIcon(menu)" class="size-4" />
                        <span class="min-w-0 flex-1 truncate">{{ menu.label }}</span>
                        <ChevronRight class="size-3.5 transition-transform group-open:rotate-90" />
                    </summary>

                    <div class="mt-1 grid gap-1 border-l border-border/70 pl-3">
                        <Link
                            v-for="child in menu.children"
                            :key="child.key"
                            :href="child.href"
                            preserve-scroll
                            class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="child.key === activeMenu ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                        >
                            <component :is="menuIcon(child)" class="size-3.5" />
                            <span class="truncate">{{ child.label }}</span>
                        </Link>
                    </div>
                </details>

                <Link
                    v-else
                    :href="menu.href"
                    preserve-scroll
                    class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="menu.key === activeMenu ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                >
                    <component :is="menuIcon(menu)" class="size-4" />
                    <span class="truncate">{{ menu.label }}</span>
                </Link>
            </template>
        </div>
    </nav>

    <nav v-else class="w-full overflow-x-auto">
        <div
            class="flex min-w-max items-center gap-2 border-b"
        >
            <Link
                v-for="menu in menuList"
                :key="menu.key"
                :href="menu.href"
                preserve-scroll
                class="inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors"
                :class="isActive(menu) ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
            >
                <component :is="menuIcon(menu)" class="size-4" />
                <span>{{ menu.label }}</span>
            </Link>
        </div>

        <div v-if="activeParent?.children?.length" class="flex min-w-max items-center gap-2 py-2">
            <Link
                v-for="child in activeParent.children"
                :key="child.key"
                :href="child.href"
                preserve-scroll
                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                :class="child.key === activeMenu ? 'bg-primary/10 text-primary' : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'"
            >
                <component :is="menuIcon(child)" class="size-3.5" />
                {{ child.label }}
            </Link>
        </div>
    </nav>
</template>
