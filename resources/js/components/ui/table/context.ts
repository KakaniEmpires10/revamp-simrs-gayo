import { inject, provide } from "vue"

export type TableVariant = "base" | "boxed" | "card" | "grid"

const tableVariantKey = Symbol("table-variant")

export function normalizeTableVariant(variant?: TableVariant): Exclude<TableVariant, "card" | "grid"> {
  if (variant === "base") {
    return "base"
  }

  return "boxed"
}

export function provideTableVariant(variant: TableVariant): void {
  provide(tableVariantKey, normalizeTableVariant(variant))
}

export function useTableVariant(): Exclude<TableVariant, "card" | "grid"> {
  return inject(tableVariantKey, "boxed")
}
