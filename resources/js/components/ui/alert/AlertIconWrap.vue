<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <div data-slot="alert-icon-wrap" :class="cn(
        'flex shrink-0 items-center justify-center',
        props.class
    )">
        <slot />
    </div>
</template>