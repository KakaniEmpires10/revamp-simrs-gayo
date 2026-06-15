<script lang="ts" setup>
import "vue-sonner/style.css"
import '@/components/ui/sonner/sonner.css'
import type { ToasterProps } from "vue-sonner"
import { CircleCheck, Info, Loader2, OctagonX, TriangleAlert, X } from "@lucide/vue"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

const props = defineProps<ToasterProps & {
  /**
   * Visual style of the toast.
   * - "soft"    Ã¢â€ â€™ pastel background, light border (default, everyday use)
   * - "solid"   Ã¢â€ â€™ filled dark background (critical / high-priority alerts)
   * - "outline" Ã¢â€ â€™ white background, colored border (formal / subtle)
   */
  toastStyle?: "soft" | "solid" | "outline"
}>()

type StyleMap = Record<string, string>

const baseToast =
  "flex items-center gap-[11px] w-full rounded-[10px] border px-3.5 py-[11px] text-[13px] shadow-sm relative overflow-hidden " +
  "before:content-[''] before:absolute before:left-0 before:top-0 before:bottom-0 before:w-[3.5px] before:rounded-l-[10px]"

const iconWrap =
  "size-8 rounded-[8px] shrink-0 flex items-center justify-center [&>svg]:size-4"

const softColors: StyleMap = {
  success:
    "bg-green-50 border-green-200 text-green-800 before:bg-green-500 " +
    "dark:bg-green-950/10 dark:border-green-800/10 dark:text-green-300/10",
  info:
    "bg-blue-50 border-blue-200 text-blue-800 before:bg-blue-500 " +
    "dark:bg-blue-950/10 dark:border-blue-800/10 dark:text-blue-300/10",
  warning:
    "bg-amber-50 border-amber-200 text-amber-800 before:bg-amber-500 " +
    "dark:bg-amber-950/10 dark:border-amber-800/10 dark:text-amber-300/10",
  error:
    "bg-red-50 border-red-200 text-red-800 before:bg-red-400 " +
    "dark:bg-red-950/10 dark:border-red-800/10 dark:text-red-300/10",
}

const solidColors: StyleMap = {
  success:
    "bg-green-800 border-green-900 text-green-50 before:bg-green-300 " +
    "dark:bg-green-900 dark:border-green-950",
  info:
    "bg-blue-800 border-blue-900 text-blue-50 before:bg-blue-300 " +
    "dark:bg-blue-900 dark:border-blue-950",
  warning:
    "bg-amber-800 border-amber-900 text-amber-50 before:bg-amber-300 " +
    "dark:bg-amber-900 dark:border-amber-950",
  error:
    "bg-red-800 border-red-900 text-red-50 before:bg-red-300 " +
    "dark:bg-red-900 dark:border-red-950",
}

const outlineColors: StyleMap = {
  success:
    "bg-background border-green-500 text-green-700 before:bg-green-500 " +
    "dark:border-green-600 dark:text-green-400",
  info:
    "bg-background border-blue-500 text-blue-700 before:bg-blue-500 " +
    "dark:border-blue-600 dark:text-blue-400",
  warning:
    "bg-background border-amber-500 text-amber-700 before:bg-amber-500 " +
    "dark:border-amber-600 dark:text-amber-400",
  error:
    "bg-background border-red-500 text-red-700 before:bg-red-500 " +
    "dark:border-red-600 dark:text-red-400",
}

const softIconWrap: StyleMap = {
  success: "bg-green-200 text-green-800 dark:bg-green-900 dark:text-green-300",
  info: "bg-blue-200 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
  warning: "bg-amber-200 text-amber-800 dark:bg-amber-900 dark:text-amber-300",
  error: "bg-red-200 text-red-800 dark:bg-red-900 dark:text-red-300",
}

const solidIconWrap: StyleMap = {
  success: "bg-white/15 text-green-50",
  info: "bg-white/15 text-blue-50",
  warning: "bg-white/15 text-amber-50",
  error: "bg-white/15 text-red-50",
}

const outlineIconWrap: StyleMap = {
  success: "bg-green-50 text-green-700 border border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-800",
  info: "bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950 dark:text-blue-400 dark:border-blue-800",
  warning: "bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950 dark:text-amber-400 dark:border-amber-800",
  error: "bg-red-50 text-red-700 border border-red-200 dark:bg-red-950 dark:text-red-400 dark:border-red-800",
}

const style = props.toastStyle ?? "soft"
const colorMap = style === "solid" ? solidColors : style === "outline" ? outlineColors : softColors
const iconWrapMap = style === "solid" ? solidIconWrap : style === "outline" ? outlineIconWrap : softIconWrap

// shared close / action / cancel classes
const closeBtn =
  "size-[22px] rounded-[6px] border-none bg-transparent cursor-pointer flex items-center justify-center opacity-40 hover:opacity-80 hover:bg-black/7 transition-all shrink-0 text-current [&>svg]:size-[13px]"

const actionBtn =
  "text-[11px] font-semibold px-2.5 py-1 rounded-md border transition-colors " +
  "bg-current/10 border-current/20 hover:bg-current/15"

const cancelBtn =
  "text-[11px] font-medium opacity-60 hover:opacity-90 transition-opacity"
</script>

<template>
  <Sonner :class="cn('toaster group', props.class)" v-bind="props" :toast-options="{
    unstyled: true,
    classes: {
      toast: baseToast,
      title: 'text-[13px] font-medium leading-snug',
      description: 'text-[12px] opacity-80 mt-0.5 leading-[1.5]',
      content: 'flex-1 min-w-0',
      closeButton: closeBtn,
      actionButton: actionBtn,
      cancelButton: cancelBtn,
      // Per-type toast colors
      success: colorMap.success,
      info: colorMap.info,
      warning: colorMap.warning,
      error: colorMap.error,
    },
  }">
    <template #success-icon>
      <div :class="cn(iconWrap, iconWrapMap.success)">
        <CircleCheck />
      </div>
    </template>
    <template #info-icon>
      <div :class="cn(iconWrap, iconWrapMap.info)">
        <Info />
      </div>
    </template>
    <template #warning-icon>
      <div :class="cn(iconWrap, iconWrapMap.warning)">
        <TriangleAlert />
      </div>
    </template>
    <template #error-icon>
      <div :class="cn(iconWrap, iconWrapMap.error)">
        <OctagonX />
      </div>
    </template>
    <template #loading-icon>
      <div :class="cn(iconWrap, iconWrapMap.info)">
        <Loader2 class="animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <X />
    </template>
  </Sonner>
</template>
