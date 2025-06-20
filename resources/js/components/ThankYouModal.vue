<script setup lang="ts">
import { Dialog, DialogContent } from '@/components/ui/dialog';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
  open: boolean;
}

interface Emits {
  (e: 'update:open', value: boolean): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const handleClose = () => {
  emit('update:open', false);
};
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="max-w-md w-full mx-4 p-0 overflow-hidden bg-white rounded-lg">
      <!-- Close Button -->
      <button 
        @click="handleClose"
        class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Modal Content -->
      <div class="text-center p-8 pb-6">
        <!-- PurpleBug Logo -->
        <div class="mb-8 flex justify-center">
          <AppLogoIcon class="h-12 w-auto" />
        </div>

        <!-- Thank You Illustration -->
        <div class="mb-8 flex justify-center">
          <img 
            src="/images/thankyou.png" 
            alt="Thank you for shopping with us" 
            class="w-40 h-auto"
          />
        </div>

        <!-- Thank you message -->
        <h2 class="text-xl font-semibold text-[#8B3F93] mb-6">
          Thank you for shopping with us.
        </h2>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Additional custom styles if needed */
</style> 