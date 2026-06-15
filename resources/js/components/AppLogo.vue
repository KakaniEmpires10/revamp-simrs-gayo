<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
    <div
        class="flex size-9 shrink-0 items-center justify-center overflow-hidden">
        <AppLogoIcon class="size-7 object-contain mr-1" />
    </div>

    <div class="-ml-1 grid min-w-0 flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold tracking-tight">
            RSUD Gayo Lues
        </span>
        <span class="truncate text-xs text-muted-foreground capitalize">
            {{ auth.user.type_user }}
        </span>
    </div>
</template>