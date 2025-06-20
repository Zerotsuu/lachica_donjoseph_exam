<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next';

interface Props {
  currentPage: number;
  totalPages: number;
  showInfo?: boolean;
  totalItems?: number;
  itemsPerPage?: number;
}

const props = withDefaults(defineProps<Props>(), {
  showInfo: true,
  totalItems: 0,
  itemsPerPage: 10
});

const emit = defineEmits(['page-change']);

const visiblePages = computed(() => {
  const delta = 2; // Show 2 pages before and after current page
  const range = [];
  const rangeWithDots = [];

  for (let i = Math.max(2, props.currentPage - delta); 
       i <= Math.min(props.totalPages - 1, props.currentPage + delta); 
       i++) {
    range.push(i);
  }

  if (props.currentPage - delta > 2) {
    rangeWithDots.push(1, '...');
  } else {
    rangeWithDots.push(1);
  }

  rangeWithDots.push(...range);

  if (props.currentPage + delta < props.totalPages - 1) {
    rangeWithDots.push('...', props.totalPages);
  } else if (props.totalPages > 1) {
    rangeWithDots.push(props.totalPages);
  }

  return rangeWithDots;
});

const paginationInfo = computed(() => {
  const start = (props.currentPage - 1) * props.itemsPerPage + 1;
  const end = Math.min(props.currentPage * props.itemsPerPage, props.totalItems);
  return `Showing ${start} to ${end} of ${props.totalItems} results`;
});

const handlePageChange = (page: number) => {
  if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
    emit('page-change', page);
  }
};
</script>

<template>
  <div v-if="totalPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
    <!-- Pagination Info -->
    <div v-if="showInfo" class="text-sm text-gray-600">
      {{ paginationInfo }}
    </div>

    <!-- Pagination Controls -->
    <div class="flex items-center gap-1">
      <!-- Previous Button -->
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage === 1"
        @click="handlePageChange(currentPage - 1)"
        class="flex items-center gap-1"
      >
        <ChevronLeftIcon class="w-4 h-4" />
        Previous
      </Button>

      <!-- Page Numbers -->
      <template v-for="(page, index) in visiblePages" :key="index">
        <span v-if="page === '...'" class="px-3 py-2 text-gray-500">
          ...
        </span>
        <Button
          v-else
          variant="outline"
          size="sm"
          :class="{
            'bg-[#8B3F93] text-white border-[#8B3F93]': page === currentPage,
            'hover:bg-gray-50': page !== currentPage
          }"
          @click="handlePageChange(page as number)"
        >
          {{ page }}
        </Button>
      </template>

      <!-- Next Button -->
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage === totalPages"
        @click="handlePageChange(currentPage + 1)"
        class="flex items-center gap-1"
      >
        Next
        <ChevronRightIcon class="w-4 h-4" />
      </Button>
    </div>
  </div>
</template> 