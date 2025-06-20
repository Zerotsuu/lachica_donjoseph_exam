<script setup lang="ts">
import { 
  SelectItem as RekaSelectItem,
  SelectItemIndicator,
  SelectItemText
} from 'reka-ui';
import { CheckIcon } from 'lucide-vue-next';

interface Props {
  value: string;
}

const props = defineProps<Props>();
</script>

<template>
  <RekaSelectItem
    :value="value"
    class="text-xs leading-none text-gray-900 rounded-[3px] flex items-center h-[25px] pr-[35px] pl-[25px] relative select-none data-[disabled]:text-gray-400 data-[disabled]:pointer-events-none data-[highlighted]:outline-none data-[highlighted]:bg-blue-500 data-[highlighted]:text-white"
  >
    <SelectItemIndicator class="absolute left-0 w-[25px] inline-flex items-center justify-center">
      <CheckIcon class="h-3.5 w-3.5" />
    </SelectItemIndicator>
    <SelectItemText>
      <slot />
    </SelectItemText>
  </RekaSelectItem>
</template> 