<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { useTableVariant } from "./context"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const variant = useTableVariant()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn(
      variant === 'base'
        ? 'hover:bg-muted/50 data-[state=selected]:bg-muted border-b transition-colors'
        : 'group border-0 bg-transparent transition-colors hover:bg-transparent data-[state=selected]:bg-muted [&>td]:border-y [&>td]:border-border/60 [&>td]:bg-card [&>td]:shadow-sm [&>td:first-child]:rounded-l-lg [&>td:first-child]:border-l [&>td:first-child]:pl-5 [&>td:last-child]:rounded-r-lg [&>td:last-child]:border-r [&>td:last-child]:pr-5 hover:[&>td]:border-primary/40',
      props.class,
    )"
  >
    <slot />
  </tr>
</template>
