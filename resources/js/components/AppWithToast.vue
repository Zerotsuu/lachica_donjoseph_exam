<script setup lang="ts">
import { ToastContainer } from '@/components/ui/toast';
import { useSessionManagement } from '@/composables/useSessionManagement';
import SessionWarningModal from './SessionWarningModal.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// This component wraps the main app and provides global toast functionality
defineProps<{
  component: any;
  props: any;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props?.auth?.user);

// Always initialize session management but only activate for authenticated users
const { showSessionWarning, dismissWarning, handleSessionExpired } = useSessionManagement();
</script>

<template>
  <div>
    <!-- Main app content -->
    <component :is="component" v-bind="props" />
    
    <!-- Global toast container -->
    <ToastContainer />
    
    <!-- Session warning modal for authenticated users only -->
    <SessionWarningModal 
      v-if="isAuthenticated"
      :show-warning="showSessionWarning"
      @extend-session="dismissWarning"
      @logout="handleSessionExpired"
      @dismiss="dismissWarning"
    />
  </div>
</template> 