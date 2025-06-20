<script setup lang="ts">
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface Props {
  isOpen: boolean;
  title: string;
  mode: 'add' | 'edit' | 'view';
  entity: 'product' | 'user' | 'order';
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'save']);

const getTitle = () => {
  const action = props.mode === 'add' ? 'Add' : props.mode === 'edit' ? 'Edit' : 'View';
  return `${action} ${props.entity.charAt(0).toUpperCase() + props.entity.slice(1)}`;
};

const handleSave = () => {
  emit('save');
};

const handleClose = () => {
  emit('close');
};
</script>

<template>
  <Dialog :open="isOpen" :modal="true">
    <DialogContent class="p-0 overflow-hidden" :closeButton="false">
      <!-- Header -->
      <div class="bg-[#8B3F93] p-4 flex justify-between items-center">
        <h2 class="text-white text-lg">{{ title || getTitle() }}</h2>
        <div class="flex gap-2">
          <Button
            @click="handleSave"
            class="bg-white hover:bg-gray-100 text-[#8B3F93] px-6"
          >
            SAVE
          </Button>
          <Button
            @click="handleClose"
            class="bg-white hover:bg-gray-100 text-[#8B3F93] px-6"
          >
            CANCEL
          </Button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <slot></slot>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
.dialog-overlay {
  background-color: rgba(0, 0, 0, 0.5);
}

:deep(.dialog-content) {
  border: none;
}
</style> 