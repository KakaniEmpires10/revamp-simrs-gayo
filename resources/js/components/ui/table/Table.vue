<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import type { TableVariant } from "./context"
import { normalizeTableVariant, provideTableVariant } from "./context"

const props = withDefaults(defineProps<{
  class?: HTMLAttributes["class"]
  variant?: TableVariant
}>(), {
  variant: "boxed",
})

provideTableVariant(props.variant)
</script>

<template>
  <div data-slot="table-container" class="relative w-full overflow-auto">
    <table
      data-slot="table"
      :class="cn(
        'w-full caption-bottom text-sm',
        normalizeTableVariant(props.variant) === 'boxed' && 'border-separate border-spacing-y-2.5',
        props.class,
      )"
    >
      <slot />
    </table>
  </div>
</template>
