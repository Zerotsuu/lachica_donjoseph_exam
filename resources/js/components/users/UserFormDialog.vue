<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import Modal from '@/components/ui/modal/Modal.vue';
import { UserIcon, LockIcon } from 'lucide-vue-next';

interface User {
  id?: number;
  name: string;
  email: string;
}

interface Props {
  isOpen: boolean;
  mode: 'add' | 'edit';
  user?: User | null;
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'add',
  user: null
});

const emit = defineEmits(['close', 'save']);

interface UserFormData {
  name: string;
  email: string;
  password: string;
  confirmPassword: string;
}

const userData = ref<UserFormData>({
  name: '',
  email: '',
  password: '',
  confirmPassword: ''
});

watch(() => props.user, (newUser) => {
  if (newUser) {
    userData.value = {
      name: newUser.name,
      email: newUser.email,
      password: '',
      confirmPassword: ''
    };
  } else {
    userData.value = {
      name: '',
      email: '',
      password: '',
      confirmPassword: ''
    };
  }
}, { immediate: true });

const handleSave = () => {
  emit('save', {
    name: userData.value.name,
    email: userData.value.email,
    ...(props.mode === 'add' && { password: userData.value.password })
  });
};

const handleClose = () => {
  emit('close');
};
</script>

<template>
  <Modal
    :is-open="isOpen"
    :mode="mode"
    :title="mode === 'add' ? 'Add User' : 'Edit User'"
    entity="user"
    @save="handleSave"
    @close="handleClose"
  >
    <div class="grid grid-cols-2 gap-6">
      <!-- Left Column -->
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium mb-2 block">Full Name</label>
          <div class="relative">
            <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.name"
              type="text"
              class="pl-10"
              placeholder="Enter full name"
            />
          </div>
        </div>

        <div v-if="mode === 'add'">
          <label class="text-sm font-medium mb-2 block">Password</label>
          <div class="relative">
            <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.password"
              type="password"
              class="pl-10"
              placeholder="Enter password"
            />
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium mb-2 block">Email</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">@</span>
            <Input
              v-model="userData.email"
              type="email"
              class="pl-10"
              placeholder="Enter email address"
            />
          </div>
        </div>

        <div v-if="mode === 'add'">
          <label class="text-sm font-medium mb-2 block">Confirm Password</label>
          <div class="relative">
            <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.confirmPassword"
              type="password"
              class="pl-10"
              placeholder="Confirm password"
            />
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template> 