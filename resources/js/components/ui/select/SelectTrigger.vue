<script setup lang="ts">
import { SelectTrigger as RekaSelectTrigger } from 'reka-ui';
import { ChevronDownIcon } from 'lucide-vue-next';

interface Props {
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false
});
</script>

<template>
  <RekaSelectTrigger
    :disabled="disabled"
    class="inline-flex min-w-[160px] items-center justify-between rounded-lg px-[15px] text-xs leading-none h-[35px] gap-[5px] bg-white text-gray-900 hover:bg-gray-50 border border-gray-300 shadow-sm focus:shadow-[0_0_0_2px] focus:shadow-blue-500 data-[placeholder]:text-gray-500 outline-none disabled:cursor-not-allowed disabled:opacity-50"
  >
    <slot />
    <ChevronDownIcon class="h-3.5 w-3.5" />
  </RekaSelectTrigger>
</template> 