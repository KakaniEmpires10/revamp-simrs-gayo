import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center border font-medium w-fit whitespace-nowrap shrink-0 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/80",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/80",
        destructive:
         "border-transparent bg-destructive text-destructive-foreground [a&]:hover:bg-destructive/80 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        success:
          "border-transparent bg-success text-success-foreground [a&]:hover:bg-success/80",
        warning:
          "border-transparent bg-warning text-warning-foreground [a&]:hover:bg-warning/80",
        info:
          "border-transparent bg-info text-info-foreground [a&]:hover:bg-info/80",
        indigo:
          "border-transparent bg-indigo text-indigo-foreground [a&]:hover:bg-indigo/80",
        muted:
          "border-transparent bg-muted text-muted-foreground [a&]:hover:bg-muted/80",
        outline:
          "bg-transparent border-input text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        "outline-primary":
          "bg-transparent border-primary text-primary [a&]:hover:bg-primary/10",
        "outline-secondary":
          "bg-transparent border-secondary text-secondary [a&]:hover:bg-secondary/10",
        "outline-destructive":
          "bg-transparent border-destructive text-destructive [a&]:hover:bg-destructive/10",
        "outline-success":
          "bg-transparent border-success text-success [a&]:hover:bg-success/10",
        "outline-warning":
          "bg-transparent border-warning text-warning [a&]:hover:bg-warning/10",
        "outline-info":
          "bg-transparent border-info text-info [a&]:hover:bg-info/10",
        "outline-indigo":
          "bg-transparent border-indigo text-indigo [a&]:hover:bg-indigo/10",
        "soft-primary":
          "border-transparent bg-primary/10 text-primary [a&]:hover:bg-primary/20",
        "soft-secondary":
          "border-border/70 bg-muted text-muted-foreground shadow-xs [a&]:hover:bg-muted/80 dark:border-border/60 dark:bg-muted/80 dark:text-muted-foreground",
        "soft-destructive":
          "border-transparent bg-destructive/10 text-destructive [a&]:hover:bg-destructive/20",
        "soft-success":
          "border-transparent bg-success/10 text-success [a&]:hover:bg-success/20",
        "soft-warning":
          "border-transparent bg-warning/10 text-warning [a&]:hover:bg-warning/20",
        "soft-info":
          "border-transparent bg-info/10 text-info [a&]:hover:bg-info/20",
        "soft-indigo":
          "border-transparent bg-indigo/10 text-indigo [a&]:hover:bg-indigo/20",
        "text-primary":
          "bg-transparent border-transparent text-primary [a&]:hover:bg-primary/5",
        "text-secondary":
          "bg-transparent border-transparent text-secondary [a&]:hover:bg-secondary/5",
        "text-destructive":
          "bg-transparent border-transparent text-destructive [a&]:hover:bg-destructive/5",
        "text-success":
          "bg-transparent border-transparent text-success [a&]:hover:bg-success/5",
        "text-warning":
          "bg-transparent border-transparent text-warning [a&]:hover:bg-warning/5",
        "text-info":
          "bg-transparent border-transparent text-info [a&]:hover:bg-info/5",
        "text-indigo":
          "bg-transparent border-transparent text-indigo [a&]:hover:bg-indigo/5",
      },
      size: {
        "xs": "px-1.5 py-0 text-[10px] [&>svg]:size-2.5",
        "sm": "px-2 py-0.5 text-[11px] [&>svg]:size-3",
        "default": "px-2.5 py-0.5 text-xs [&>svg]:size-3",
        "lg": "px-3 py-1 text-sm [&>svg]:size-3.5",
      },
      rounded: {
        "default": "rounded-full",
        "pill": "rounded-full",
        "none": "rounded-none",
        "sm": "rounded-sm",
        "md": "rounded-md",
        "lg": "rounded-lg",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
      rounded: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
