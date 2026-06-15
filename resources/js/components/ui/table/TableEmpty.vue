<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AlertTriangle, Inbox, SearchX, ServerCrash } from "@lucide/vue"
import { computed, useSlots } from "vue"
import { cn } from "@/lib/utils"
import TableCell from "./TableCell.vue"
import TableRow from "./TableRow.vue"

type TableEmptySeverity = "empty" | "info" | "warning" | "destructive"
type TableEmptyIcon = "inbox" | "search" | "warning" | "error"

const props = withDefaults(defineProps<{
  class?: HTMLAttributes["class"]
  colspan?: number
  description?: string
  icon?: TableEmptyIcon
  severity?: TableEmptySeverity
  title?: string
}>(), {
  colspan: 1,
  severity: "empty",
})

const delegatedProps = reactiveOmit(props, "class", "description", "icon", "severity", "title")
const slots = useSlots()

const severityClasses: Record<TableEmptySeverity, { icon: string, ring: string }> = {
  empty: {
    icon: "bg-muted text-muted-foreground ring-border",
    ring: "bg-muted/40",
  },
  info: {
    icon: "bg-info/10 text-info ring-info/20",
    ring: "bg-info/5",
  },
  warning: {
    icon: "bg-warning/10 text-warning ring-warning/20",
    ring: "bg-warning/5",
  },
  destructive: {
    icon: "bg-destructive/10 text-destructive ring-destructive/20",
    ring: "bg-destructive/5",
  },
}

const iconMap = {
  inbox: Inbox,
  search: SearchX,
  warning: AlertTriangle,
  error: ServerCrash,
}

const defaultIconBySeverity: Record<TableEmptySeverity, TableEmptyIcon> = {
  empty: "inbox",
  info: "search",
  warning: "warning",
  destructive: "error",
}

const iconComponent = computed(() => iconMap[props.icon ?? defaultIconBySeverity[props.severity]])
const stateClasses = computed(() => severityClasses[props.severity])
</script>

<template>
  <TableRow>
    <TableCell
      :class="
        cn(
          'p-4 align-middle text-sm text-foreground',
          props.class,
        )
      "
      v-bind="delegatedProps"
    >
      <div class="flex items-center justify-center py-10">
        <div class="mx-auto flex max-w-md flex-col items-center gap-3 text-center">
          <div :class="cn('grid size-12 place-items-center rounded-full ring-1', stateClasses.icon)">
            <component :is="iconComponent" class="size-6" />
          </div>

          <div class="space-y-1">
            <p class="text-base font-semibold text-foreground">
              {{ title ?? "Data tidak ditemukan" }}
            </p>
            <p v-if="description" class="text-sm leading-relaxed text-muted-foreground">
              {{ description }}
            </p>
            <div v-else-if="slots.default" class="text-sm leading-relaxed text-muted-foreground">
              <slot />
            </div>
          </div>

          <div :class="cn('h-1 w-16 rounded-full', stateClasses.ring)" />
        </div>
      </div>
    </TableCell>
  </TableRow>
</template>
