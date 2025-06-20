<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

interface Props {
  title: string;
  description?: string;
  actionText?: string;
  icon?: any;
  showAction?: boolean;
}

withDefaults(defineProps<Props>(), {
  description: 'Get started by adding your first item.',
  actionText: 'Add Item',
  showAction: true
});

const emit = defineEmits(['action']);

const handleAction = () => {
  emit('action');
};
</script>

<template>
  <div class="text-center py-12 bg-white rounded-lg shadow">
    <div class="text-gray-400 mb-4">
      <component v-if="icon" :is="icon" class="mx-auto h-12 w-12" />
      <svg v-else class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ title }}</h3>
    <p v-if="description" class="text-gray-500 mb-4">{{ description }}</p>
    <Button 
      v-if="showAction"
      @click="handleAction"
      class="bg-[#65558F] text-white hover:bg-[#5a4a7a]"
    >
      <PlusIcon class="w-4 h-4 mr-2" />
      {{ actionText }}
    </Button>
  </div>
</template> 