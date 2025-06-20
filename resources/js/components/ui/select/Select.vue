<script setup lang="ts">
import { SelectRoot } from 'reka-ui';

interface Props {
  modelValue?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false
});

const emit = defineEmits(['update:modelValue']);

const handleValueChange = (value: string) => {
  emit('update:modelValue', value);
};
</script>

<template>
  <SelectRoot 
    :value="props.modelValue" 
    :disabled="props.disabled"
    @update:value="handleValueChange"
  >
    <slot />
  </SelectRoot>
</template> 