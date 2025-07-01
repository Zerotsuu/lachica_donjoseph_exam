<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Input } from '@/components/ui/input';
import Modal from '@/components/ui/modal/Modal.vue';
import { UserIcon, LockIcon, AlertCircleIcon } from 'lucide-vue-next';

interface User {
  id?: number;
  name: string;
  email: string;
}

interface Props {
  isOpen: boolean;
  mode: 'add' | 'edit';
  user?: User | null;
  isLoading?: boolean;
  errors?: Record<string, string[]>;
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'add',
  user: null,
  isLoading: false,
  errors: () => ({})
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

const validationErrors = ref<Record<string, string>>({});

// Define resetForm before watchers that use it
const resetForm = () => {
  userData.value = {
    name: '',
    email: '',
    password: '',
    confirmPassword: ''
  };
  validationErrors.value = {};
};

// Watch for user changes and populate form
watch(() => props.user, (newUser) => {
  if (newUser) {
    userData.value = {
      name: newUser.name,
      email: newUser.email,
      password: '',
      confirmPassword: ''
    };
  } else {
    resetForm();
  }
}, { immediate: true });

// Watch for modal close to reset form
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    resetForm();
  }
});

// Watch for backend errors
watch(() => props.errors, (newErrors) => {
  validationErrors.value = {};
  if (newErrors) {
    Object.keys(newErrors).forEach(key => {
      if (newErrors[key] && newErrors[key].length > 0) {
        validationErrors.value[key] = newErrors[key][0];
      }
    });
  }
}, { immediate: true, deep: true });

// Form validation
const validateForm = (): boolean => {
  const errors: Record<string, string> = {};

  // Name validation
  if (!userData.value.name.trim()) {
    errors.name = 'Full name is required';
  } else if (userData.value.name.trim().length < 2) {
    errors.name = 'Full name must be at least 2 characters';
  }

  // Email validation
  if (!userData.value.email.trim()) {
    errors.email = 'Email is required';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userData.value.email)) {
    errors.email = 'Please enter a valid email address';
  }

  // Password validation (only for add mode)
  if (props.mode === 'add') {
    if (!userData.value.password) {
      errors.password = 'Password is required';
    } else if (userData.value.password.length < 8) {
      errors.password = 'Password must be at least 8 characters';
    }

    if (!userData.value.confirmPassword) {
      errors.confirmPassword = 'Please confirm your password';
    } else if (userData.value.password !== userData.value.confirmPassword) {
      errors.confirmPassword = 'Passwords do not match';
    }
  }

  validationErrors.value = errors;
  return Object.keys(errors).length === 0;
};

// Computed validation states
const isNameValid = computed(() => !validationErrors.value.name);
const isEmailValid = computed(() => !validationErrors.value.email);
const isPasswordValid = computed(() => !validationErrors.value.password);
const isConfirmPasswordValid = computed(() => !validationErrors.value.confirmPassword);

const handleSave = () => {
  if (!validateForm()) {
    return;
  }

  const formData: any = {
    name: userData.value.name.trim(),
    email: userData.value.email.trim(),
  };

  if (props.mode === 'add') {
    formData.password = userData.value.password;
  }

  emit('save', formData);
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
    :is-loading="isLoading"
    @save="handleSave"
    @close="handleClose"
  >
    <div class="grid grid-cols-2 gap-6">
      <!-- Left Column -->
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium mb-2 block">Full Name *</label>
          <div class="relative">
            <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.name"
              type="text"
              class="pl-10"
              :class="{ 'border-red-500': !isNameValid }"
              placeholder="Enter full name"
              :disabled="isLoading"
            />
            <AlertCircleIcon 
              v-if="!isNameValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="validationErrors.name" class="text-red-500 text-xs mt-1">
            {{ validationErrors.name }}
          </p>
        </div>

        <div v-if="mode === 'add'">
          <label class="text-sm font-medium mb-2 block">Password *</label>
          <div class="relative">
            <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.password"
              type="password"
              class="pl-10"
              :class="{ 'border-red-500': !isPasswordValid }"
              placeholder="Enter password (min 8 characters)"
              :disabled="isLoading"
            />
            <AlertCircleIcon 
              v-if="!isPasswordValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="validationErrors.password" class="text-red-500 text-xs mt-1">
            {{ validationErrors.password }}
          </p>
        </div>
      </div>

      <!-- Right Column -->
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium mb-2 block">Email *</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">@</span>
            <Input
              v-model="userData.email"
              type="email"
              class="pl-10"
              :class="{ 'border-red-500': !isEmailValid }"
              placeholder="Enter email address"
              :disabled="isLoading"
            />
            <AlertCircleIcon 
              v-if="!isEmailValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="validationErrors.email" class="text-red-500 text-xs mt-1">
            {{ validationErrors.email }}
          </p>
        </div>

        <div v-if="mode === 'add'">
          <label class="text-sm font-medium mb-2 block">Confirm Password *</label>
          <div class="relative">
            <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5" />
            <Input
              v-model="userData.confirmPassword"
              type="password"
              class="pl-10"
              :class="{ 'border-red-500': !isConfirmPasswordValid }"
              placeholder="Confirm password"
              :disabled="isLoading"
            />
            <AlertCircleIcon 
              v-if="!isConfirmPasswordValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="validationErrors.confirmPassword" class="text-red-500 text-xs mt-1">
            {{ validationErrors.confirmPassword }}
          </p>
        </div>
      </div>
    </div>

    <!-- General Error Message -->
    <div v-if="validationErrors.general" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
      <p class="text-red-600 text-sm">{{ validationErrors.general }}</p>
    </div>

    <!-- Password Requirements (for add mode) -->
    <div v-if="mode === 'add'" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
      <h4 class="text-sm font-medium text-blue-800 mb-2">Password Requirements:</h4>
      <ul class="text-xs text-blue-700 space-y-1">
        <li>• At least 8 characters long</li>
        <li>• Should contain a mix of letters and numbers</li>
        <li>• Both passwords must match</li>
      </ul>
    </div>
  </Modal>
</template> 