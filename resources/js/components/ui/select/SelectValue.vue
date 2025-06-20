<script setup lang="ts">
import { SelectValue as RekaSelectValue } from 'reka-ui';

interface Props {
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select an option'
});
</script>

<template>
  <RekaSelectValue :placeholder="placeholder" />
</template> 