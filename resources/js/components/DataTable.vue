<script setup lang="ts">
import { computed } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PencilIcon, TrashIcon } from 'lucide-vue-next';
import LoadingState from './LoadingState.vue';
import EmptyState from './EmptyState.vue';

interface Column {
  key: string;
  label: string;
  sortable?: boolean;
  width?: string;
  formatter?: (value: any, row: any) => string;
  component?: string; // For custom cell rendering
}

interface Action {
  icon: any;
  label: string;
  onClick: (item: any) => void;
  variant?: 'edit' | 'delete' | 'custom';
  disabled?: (item: any) => boolean;
  loading?: (item: any) => boolean;
}

interface Props {
  data: any[];
  columns: Column[];
  actions?: Action[];
  loading?: boolean;
  emptyTitle?: string;
  emptyDescription?: string;
  emptyActionText?: string;
  onEmptyAction?: () => void;
  rowKey?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  emptyTitle: 'No data found',
  emptyDescription: 'Get started by adding your first item.',
  emptyActionText: 'Add Item',
  rowKey: 'id',
  actions: () => []
});

const emit = defineEmits(['empty-action']);

const handleEmptyAction = () => {
  if (props.onEmptyAction) {
    props.onEmptyAction();
  } else {
    emit('empty-action');
  }
};

const getActionVariantClasses = (variant?: string) => {
  switch (variant) {
    case 'edit':
      return 'text-gray-600 hover:text-[#8B3F93]';
    case 'delete':
      return 'text-gray-600 hover:text-red-600';
    default:
      return 'text-gray-600 hover:text-gray-900';
  }
};

const formatCellValue = (value: any, row: any, column: Column) => {
  if (column.formatter) {
    return column.formatter(value, row);
  }
  return value;
};
</script>

<template>
  <div class="grid space-y-4">
    <!-- Loading State -->
    <LoadingState v-if="loading" />

    <!-- Table -->
    <div v-else-if="data.length > 0">
      <!-- Table Header -->
      <Table class="bg-[#8B3F93] rounded-lg shadow">
        <TableHeader>
          <TableRow :class="`grid grid-cols-${columns.length + (actions.length > 0 ? 1 : 0)} gap-4`">
            <TableHead 
              v-for="column in columns" 
              :key="column.key"
              class="text-white px-6 py-4"
              :style="column.width ? { width: column.width } : {}"
            >
              {{ column.label }}
            </TableHead>
            <TableHead v-if="actions.length > 0" class="text-white px-6 py-4">
              Action
            </TableHead>
          </TableRow>
        </TableHeader>
      </Table>

      <!-- Table Body -->
      <TableBody class="grid border-gray-100 rounded-lg">
        <TableRow 
          v-for="item in data" 
          :key="item[rowKey]" 
          :class="`grid grid-cols-${columns.length + (actions.length > 0 ? 1 : 0)} gap-4 border-gray-100 bg-white hover:bg-gray-50 transition-colors`"
        >
          <TableCell 
            v-for="column in columns" 
            :key="column.key"
            class="px-6 py-4"
            :class="{ 'font-medium': column.key === 'name' }"
          >
            <slot 
              :name="`cell-${column.key}`" 
              :value="item[column.key]" 
              :item="item"
              :column="column"
            >
              {{ formatCellValue(item[column.key], item, column) }}
            </slot>
          </TableCell>
          
          <!-- Actions Column -->
          <TableCell v-if="actions.length > 0" class="px-6 py-4 space-x-2">
            <template v-for="action in actions" :key="action.label">
              <button 
                :class="getActionVariantClasses(action.variant)"
                :disabled="action.disabled?.(item) || action.loading?.(item)"
                :title="action.label"
                @click="action.onClick(item)"
                class="disabled:opacity-50"
              >
                <component :is="action.icon" class="w-5 h-5" />
              </button>
              <div v-if="action.loading?.(item)" class="inline-block ml-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-red-500"></div>
              </div>
            </template>
          </TableCell>
        </TableRow>
      </TableBody>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else
      :title="emptyTitle"
      :description="emptyDescription"
      :action-text="emptyActionText"
      @action="handleEmptyAction"
    />
  </div>
</template> 