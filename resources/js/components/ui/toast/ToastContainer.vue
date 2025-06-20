<script setup lang="ts">
import { computed } from 'vue';
import Toast, { type ToastProps } from './Toast.vue';
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();

const activeToasts = computed(() => toasts.value);

const handleToastClose = (id: string) => {
  removeToast(id);
};
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
      <Toast
        v-for="toast in activeToasts"
        :key="toast.id"
        v-bind="toast"
        @close="handleToastClose"
      />
    </div>
  </Teleport>
</template> 