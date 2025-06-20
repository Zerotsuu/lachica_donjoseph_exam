<script setup lang="ts">
import { ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { SearchIcon, SortAscIcon, SortDescIcon } from 'lucide-vue-next';

interface Props {
  searchPlaceholder?: string;
  showSort?: boolean;
  sortOptions?: Array<{ value: string; label: string }>;
}

const props = withDefaults(defineProps<Props>(), {
  searchPlaceholder: 'Search...',
  showSort: true,
  sortOptions: () => [
    { value: 'asc', label: 'Price: Low to High' },
    { value: 'desc', label: 'Price: High to Low' }
  ]
});

const emit = defineEmits(['search', 'sort']);

const searchQuery = ref('');
const sortOrder = ref('asc');

const handleSearch = () => {
  emit('search', searchQuery.value);
};

const handleSort = (order: string) => {
  sortOrder.value = order;
  emit('sort', order);
};

const currentSortOption = computed(() => {
  return props.sortOptions.find(option => option.value === sortOrder.value);
});
</script>

<template>
  <div class="flex flex-col sm:flex-row gap-4 mb-6">
    <!-- Search Input -->
    <div class="flex-1 relative">
      <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
      <Input
        v-model="searchQuery"
        :placeholder="searchPlaceholder"
        class="pl-10"
        @keyup.enter="handleSearch"
      />
    </div>

    <!-- Sort Options -->
    <div v-if="showSort" class="flex gap-2">
      <Button
        v-for="option in sortOptions"
        :key="option.value"
        variant="outline"
        size="sm"
        :class="{ 'bg-[#8B3F93] text-white': sortOrder === option.value }"
        @click="handleSort(option.value)"
      >
        <component 
          :is="option.value === 'asc' ? SortAscIcon : SortDescIcon" 
          class="w-4 h-4 mr-2" 
        />
        {{ option.label }}
      </Button>
    </div>

    <!-- Search Button -->
    <Button @click="handleSearch" class="bg-[#65558F] text-white hover:bg-[#5a4a7a]">
      <SearchIcon class="w-4 h-4 mr-2" />
      Search
    </Button>
  </div>
</template> 