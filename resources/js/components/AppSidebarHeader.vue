<script setup lang="ts">
import NavUserHeader from '@/components/NavUserHeader.vue';
import NotifButton from '@/components/NotifButton.vue';
import ToggleTheme from '@/components/ToggleTheme.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
</script>

<template>
    <header
        class="flex justify-between h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">

        <!-- Kiri: trigger + info RS -->
        <div class="flex items-center gap-2 min-w-0 overflow-hidden">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <Separator orientation="vertical" class="h-10! shrink-0" />
            <div class="min-w-0 overflow-hidden">
                <h6 class="font-semibold tracking-tight leading-tight truncate text-sm">
                    RSU Muhammad Ali Kasim
                </h6>
                <span class="text-xs text-muted-foreground hidden sm:block truncate">
                    Sistem Informasi Rumah Sakit
                </span>
            </div>
        </div>

        <!-- Kanan: action buttons -->
        <div class="flex items-center gap-1 shrink-0">

            <div class="hidden lg:inline-flex h-9 items-center rounded-md bg-muted p-1 text-muted-foreground">
                <div
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium bg-background text-success shadow-sm ring-offset-background">
                    Revamp
                </div>
                <div class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm">
                    v2
                </div>
            </div>

            <Separator orientation="vertical" class="mx-1 h-10! hidden lg:block" />

            <ToggleTheme />
            <NotifButton />

            <NavUserHeader />
        </div>
    </header>
</template>