<script setup lang="ts">
import { 
  SelectContent as RekaSelectContent, 
  SelectPortal, 
  SelectViewport,
  SelectScrollUpButton,
  SelectScrollDownButton
} from 'reka-ui';
import { ChevronUpIcon, ChevronDownIcon } from 'lucide-vue-next';
</script>

<template>
  <SelectPortal>
    <RekaSelectContent
      class="min-w-[160px] bg-white rounded-lg border border-gray-300 shadow-sm will-change-[opacity,transform] data-[side=top]:animate-slideDownAndFade data-[side=right]:animate-slideLeftAndFade data-[side=bottom]:animate-slideUpAndFade data-[side=left]:animate-slideRightAndFade z-[100]"
      :side-offset="5"
    >
      <SelectScrollUpButton class="flex items-center justify-center h-[25px] bg-white text-gray-600 cursor-default">
        <ChevronUpIcon class="h-3.5 w-3.5" />
      </SelectScrollUpButton>

      <SelectViewport class="p-[5px]">
        <slot />
      </SelectViewport>

      <SelectScrollDownButton class="flex items-center justify-center h-[25px] bg-white text-gray-600 cursor-default">
        <ChevronDownIcon class="h-3.5 w-3.5" />
      </SelectScrollDownButton>
    </RekaSelectContent>
  </SelectPortal>
</template> 