import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Alert } from "./Alert.vue"
export { default as AlertDescription } from "./AlertDescription.vue"
export { default as AlertTitle } from "./AlertTitle.vue"

export const alertVariants = cva(
  [
    "relative w-full border text-sm",
    "grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 items-start",
  ].join(" "),
  {
    variants: {
      variant: {
        /* ── Soft ── */
        "soft-success": [
          "bg-green-50 border-green-200 text-green-800",
          "dark:bg-green-950 dark:border-green-800 dark:text-green-300",
          "[&_[data-slot=alert-icon-wrap]]:bg-green-200 [&_[data-slot=alert-icon-wrap]]:text-green-800",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-green-900 dark:[&_[data-slot=alert-icon-wrap]]:text-green-300",
        ].join(" "),
        "soft-info": [
          "bg-blue-50 border-blue-200 text-blue-800",
          "dark:bg-blue-950 dark:border-blue-800 dark:text-blue-300",
          "[&_[data-slot=alert-icon-wrap]]:bg-blue-200 [&_[data-slot=alert-icon-wrap]]:text-blue-800",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-blue-900 dark:[&_[data-slot=alert-icon-wrap]]:text-blue-300",
        ].join(" "),
        "soft-warning": [
          "bg-amber-50 border-amber-200 text-amber-800",
          "dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300",
          "[&_[data-slot=alert-icon-wrap]]:bg-amber-200 [&_[data-slot=alert-icon-wrap]]:text-amber-800",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-amber-900 dark:[&_[data-slot=alert-icon-wrap]]:text-amber-300",
        ].join(" "),
        "soft-destructive": [
          "bg-red-50 border-red-200 text-red-800",
          "dark:bg-red-950 dark:border-red-800 dark:text-red-300",
          "[&_[data-slot=alert-icon-wrap]]:bg-red-200 [&_[data-slot=alert-icon-wrap]]:text-red-800",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-red-900 dark:[&_[data-slot=alert-icon-wrap]]:text-red-300",
        ].join(" "),

        /* ── Solid ── */
        "solid-success": [
          "bg-green-700 border-green-800 text-green-50",
          "[&_[data-slot=alert-icon-wrap]]:bg-white/15 [&_[data-slot=alert-icon-wrap]]:text-green-50",
          "*:data-[slot=alert-description]:text-green-100",
        ].join(" "),
        "solid-info": [
          "bg-blue-700 border-blue-800 text-blue-50",
          "[&_[data-slot=alert-icon-wrap]]:bg-white/15 [&_[data-slot=alert-icon-wrap]]:text-blue-50",
          "*:data-[slot=alert-description]:text-blue-100",
        ].join(" "),
        "solid-warning": [
          "bg-amber-700 border-amber-800 text-amber-50",
          "[&_[data-slot=alert-icon-wrap]]:bg-white/15 [&_[data-slot=alert-icon-wrap]]:text-amber-50",
          "*:data-[slot=alert-description]:text-amber-100",
        ].join(" "),
        "solid-destructive": [
          "bg-red-700 border-red-800 text-red-50",
          "[&_[data-slot=alert-icon-wrap]]:bg-white/15 [&_[data-slot=alert-icon-wrap]]:text-red-50",
          "*:data-[slot=alert-description]:text-red-100",
        ].join(" "),

        /* ── Outline ── */
        "outline-success": [
          "bg-background border-green-500 text-green-700",
          "dark:border-green-600 dark:text-green-400",
          "[&_[data-slot=alert-icon-wrap]]:bg-green-50 [&_[data-slot=alert-icon-wrap]]:text-green-700 [&_[data-slot=alert-icon-wrap]]:border [&_[data-slot=alert-icon-wrap]]:border-green-200",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-green-950 dark:[&_[data-slot=alert-icon-wrap]]:text-green-400 dark:[&_[data-slot=alert-icon-wrap]]:border-green-800",
        ].join(" "),
        "outline-info": [
          "bg-background border-blue-500 text-blue-700",
          "dark:border-blue-600 dark:text-blue-400",
          "[&_[data-slot=alert-icon-wrap]]:bg-blue-50 [&_[data-slot=alert-icon-wrap]]:text-blue-700 [&_[data-slot=alert-icon-wrap]]:border [&_[data-slot=alert-icon-wrap]]:border-blue-200",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-blue-950 dark:[&_[data-slot=alert-icon-wrap]]:text-blue-400 dark:[&_[data-slot=alert-icon-wrap]]:border-blue-800",
        ].join(" "),
        "outline-warning": [
          "bg-background border-amber-500 text-amber-700",
          "dark:border-amber-600 dark:text-amber-400",
          "[&_[data-slot=alert-icon-wrap]]:bg-amber-50 [&_[data-slot=alert-icon-wrap]]:text-amber-700 [&_[data-slot=alert-icon-wrap]]:border [&_[data-slot=alert-icon-wrap]]:border-amber-200",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-amber-950 dark:[&_[data-slot=alert-icon-wrap]]:text-amber-400 dark:[&_[data-slot=alert-icon-wrap]]:border-amber-800",
        ].join(" "),
        "outline-destructive": [
          "bg-background border-red-500 text-red-700",
          "dark:border-red-600 dark:text-red-400",
          "[&_[data-slot=alert-icon-wrap]]:bg-red-50 [&_[data-slot=alert-icon-wrap]]:text-red-700 [&_[data-slot=alert-icon-wrap]]:border [&_[data-slot=alert-icon-wrap]]:border-red-200",
          "dark:[&_[data-slot=alert-icon-wrap]]:bg-red-950 dark:[&_[data-slot=alert-icon-wrap]]:text-red-400 dark:[&_[data-slot=alert-icon-wrap]]:border-red-800",
        ].join(" "),
      },
      size: {
        sm: "px-3 py-2.5 text-xs [&_[data-slot=alert-icon-wrap]]:size-8 [&_[data-slot=alert-icon-wrap]]:rounded-full [&_[data-slot=alert-icon-wrap]_svg]:size-3.5",
        default: "px-3.5 py-3 text-sm [&_[data-slot=alert-icon-wrap]]:size-9 [&_[data-slot=alert-icon-wrap]]:rounded-full [&_[data-slot=alert-icon-wrap]_svg]:size-4",
        full: "px-4 py-3.5 text-base [&_[data-slot=alert-icon-wrap]]:size-10 [&_[data-slot=alert-icon-wrap]]:rounded-full [&_[data-slot=alert-icon-wrap]_svg]:size-5",
      },
      rounded: {
        none: "rounded-none",
        sm: "rounded-sm",
        md: "rounded-md",
        default: "rounded-lg",
        lg: "rounded-lg",
        xl: "rounded-xl",
      },
    },
    defaultVariants: {
      variant: "soft-info",
      size: "default",
      rounded: "default",
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>

export { default as AlertIconWrap } from "./AlertIconWrap.vue"