<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { useTableVariant } from "./context"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  variant?: "default" | "grouped"
}>()

const variant = useTableVariant()
</script>

<template>
  <thead
    data-slot="table-header"
    :class="cn(
      props.variant === 'grouped'
        ? 'bg-muted [&_tr]:border-b [&_tr]:border-border/70 [&_tr]:hover:bg-transparent [&_tr:last-child]:border-b-0 [&_th]:h-10 [&_th]:border-r [&_th]:border-border/70 [&_th]:px-3 [&_th]:text-center [&_th]:text-[10px] [&_th]:font-semibold [&_th]:tracking-[0.14em] [&_th]:uppercase [&_th]:last:border-r-0 [&_tr:first-child_th:first-child]:rounded-tl-lg [&_tr:first-child_th:last-child]:rounded-tr-lg [&_tr:last-child_th:first-child]:rounded-bl-lg [&_tr:last-child_th:last-child]:rounded-br-lg'
        : variant === 'base'
          ? '[&_tr]:border-b'
          : 'bg-muted [&_tr]:border-b-0 [&_tr]:hover:bg-transparent [&_th]:h-14 [&_th]:text-[10px] [&_th]:font-semibold [&_th]:tracking-[0.18em] [&_th]:uppercase [&_th:first-child]:rounded-l-lg [&_th:first-child]:pl-5 [&_th:last-child]:rounded-r-lg [&_th:last-child]:pr-5',
      props.class,
    )"
  >
    <slot />
  </thead>
</template>
