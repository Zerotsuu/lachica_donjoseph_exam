<script setup lang="ts">
import { ToastContainer } from '@/components/ui/toast';



// This component wraps the main app and provides global toast functionality
defineProps<{
  component: any;
  props: any;
}>();

// This component wraps the main app and provides global toast functionality

</script>

<template>
  <div>
    <!-- Main app content -->
    <component :is="component" v-bind="props" />
    
    <!-- Global toast container -->
    <ToastContainer />
    

  </div>
</template> 