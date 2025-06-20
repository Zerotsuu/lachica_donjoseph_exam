<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { CheckCircle, X, XCircle } from 'lucide-vue-next';

export interface ToastProps {
  id: string;
  type: 'success' | 'error';
  title: string;
  message?: string;
  duration?: number;
  closable?: boolean;
}

interface Props extends ToastProps {}

const props = withDefaults(defineProps<Props>(), {
  duration: 5000,
  closable: true,
});

const emit = defineEmits<{
  close: [id: string];
}>();

const isVisible = ref(false);
const timeoutId = ref<NodeJS.Timeout | null>(null);

const toastClasses = computed(() => {
  return {
    'bg-emerald-50 border-emerald-200 text-emerald-800': props.type === 'success',
    'bg-red-50 border-red-200 text-red-800': props.type === 'error',
  };
});

const iconClasses = computed(() => {
  return {
    'text-emerald-600': props.type === 'success',
    'text-red-600': props.type === 'error',
  };
});

const handleClose = () => {
  isVisible.value = false;
  setTimeout(() => {
    emit('close', props.id);
  }, 300); // Wait for exit animation
};

onMounted(() => {
  // Show toast with slight delay for enter animation
  setTimeout(() => {
    isVisible.value = true;
  }, 50);

  // Auto close after duration
  if (props.duration > 0) {
    timeoutId.value = setTimeout(() => {
      handleClose();
    }, props.duration);
  }
});

// Clear timeout if component unmounts
onUnmounted(() => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
  }
});
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 translate-x-full"
    enter-to-class="opacity-100 translate-x-0"
    leave-active-class="transition-all duration-300 ease-in"
    leave-from-class="opacity-100 translate-x-0"
    leave-to-class="opacity-0 translate-x-full"
  >
    <div
      v-if="isVisible"
      :class="[
        'relative max-w-sm w-full rounded-lg border shadow-lg p-4 mb-4',
        toastClasses
      ]"
      role="alert"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <CheckCircle 
            v-if="type === 'success'" 
            :class="['h-5 w-5', iconClasses]" 
          />
          <XCircle 
            v-else 
            :class="['h-5 w-5', iconClasses]" 
          />
        </div>
        
        <div class="ml-3 flex-1">
          <h4 class="text-sm font-semibold">
            {{ title }}
          </h4>
          <p v-if="message" class="text-sm mt-1 opacity-90">
            {{ message }}
          </p>
        </div>
        
        <div v-if="closable" class="ml-4 flex-shrink-0">
          <button
            @click="handleClose"
            :class="[
              'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 hover:opacity-75',
              iconClasses
            ]"
          >
            <span class="sr-only">Close</span>
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template> 