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
  <td
    data-slot="table-cell"
    :class="
      cn(
        variant === 'base'
          ? 'p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]'
          : 'p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
        props.class,
      )
    "
  >
    <slot />
  </td>
</template>
