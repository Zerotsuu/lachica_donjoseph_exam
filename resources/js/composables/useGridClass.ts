// src/composables/useGridClass.ts
import { computed } from 'vue'

/**
 * Generates a Tailwind grid-cols class based on number of columns and optional actions.
 *
 * @param columns - The array representing column data.
 * @param actions - Optional array representing action buttons.
 * @returns A computed class string like 'grid grid-cols-5 gap-4'.
 */
export function useGridClass(columns: unknown[], actions?: unknown[]) {
  return computed(() => {
    const count = columns.length + (actions && actions.length > 0 ? 1 : 0)

    const colMap: Record<number, string> = {
      1: 'grid-cols-1',
      2: 'grid-cols-2',
      3: 'grid-cols-3',
      4: 'grid-cols-4',
      5: 'grid-cols-5',
      6: 'grid-cols-6',
      7: 'grid-cols-7',
      8: 'grid-cols-8',
      9: 'grid-cols-9',
      10: 'grid-cols-10',
      11: 'grid-cols-11',
      12: 'grid-cols-12',
    }

    return `grid ${colMap[count] || 'grid-cols-1'} gap-4`
  })
}
