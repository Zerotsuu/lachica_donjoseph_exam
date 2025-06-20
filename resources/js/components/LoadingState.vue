<script setup lang="ts">
interface Props {
  message?: string;
  size?: 'sm' | 'md' | 'lg';
}

withDefaults(defineProps<Props>(), {
  message: 'Loading...',
  size: 'md'
});

const sizeClasses = {
  sm: 'h-6 w-6',
  md: 'h-8 w-8', 
  lg: 'h-12 w-12'
};
</script>

<template>
  <div class="text-center py-8">
    <div 
      class="inline-block animate-spin rounded-full border-b-2 border-[#8B3F93]"
      :class="sizeClasses[size]"
    ></div>
    <p class="mt-2 text-gray-600">{{ message }}</p>
  </div>
</template> 