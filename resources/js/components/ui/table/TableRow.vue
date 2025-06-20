<script setup lang="ts">
defineOptions({
  name: 'TableRow'
})
</script>
 
<template>
  <tr class="transition-colors hover:bg-gray-50/50">
    <slot />
  </tr>
</template> 