<script setup lang="ts">
import { Button } from '@/components/ui/button';

interface Props {
  title: string;
  actionText?: string;
  actionIcon?: any;
  showAction?: boolean;
  loading?: boolean;
}

withDefaults(defineProps<Props>(), {
  showAction: true,
  loading: false
});

const emit = defineEmits(['action']);

const handleAction = () => {
  emit('action');
};
</script>

<template>
  <div class="flex justify-between items-center mb-6 p-4 rounded-lg bg-[#FFFFFF] shadow-md/30 shadow-gray-500">
    <h1 class="text-2xl font-semibold text-[#8B3F93]">{{ title }}</h1>
    <Button 
      v-if="showAction && actionText"
      @click="handleAction"
      :disabled="loading"
      class="bg-[#65558F] text-white rounded-full shadow-md/30 shadow-black hover:bg-[#5a4a7a] disabled:opacity-50"
    >
      <component v-if="actionIcon" :is="actionIcon" class="w-4 h-4 mr-2" />
      {{ actionText }}
    </Button>
  </div>
</template> 