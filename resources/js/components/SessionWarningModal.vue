<template>
  <Dialog :open="showWarning" @update:open="(value) => !value && $emit('dismiss')" :modal="true">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Icon name="clock" class="h-5 w-5 text-amber-500" />
          Session Expiring Soon
        </DialogTitle>
        <DialogDescription>
          Your session will expire in 5 minutes due to inactivity. Would you like to extend your session?
        </DialogDescription>
      </DialogHeader>
      
      <div class="flex gap-3 mt-6">
        <Button @click="$emit('extend-session')" class="flex-1">
          <Icon name="refresh-cw" class="h-4 w-4 mr-2" />
          Extend Session
        </Button>
        <Button @click="$emit('logout')" variant="outline" class="flex-1">
          <Icon name="log-out" class="h-4 w-4 mr-2" />
          Logout Now
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
interface Props {
  showWarning: boolean;
}

interface Emits {
  (e: 'extend-session'): void;
  (e: 'logout'): void;
  (e: 'dismiss'): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script> 