<script setup lang="ts">
defineOptions({
  name: 'Table'
})
</script>

<template>
  <div class="w-full overflow-hidden rounded-lg border border-gray-200">
    <table class="w-full caption-bottom text-sm">
      <slot />
    </table>
  </div>
</template> 