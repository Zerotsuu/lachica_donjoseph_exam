<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Input } from '@/components/ui/input';
import Modal from '@/components/ui/modal/Modal.vue';
import { UserIcon, LockIcon, AlertCircleIcon } from 'lucide-vue-next';
import { useErrorHandler } from '@/composables/useErrorHandler';

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

// Error handling with semantic processing
const { 
  getFieldError, 
  hasFieldError, 
  setFieldError, 
  clearFieldError, 
  clearErrors,
  handleValidationError 
} = useErrorHandler();

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

// Define resetForm before watchers that use it
const resetForm = () => {
  userData.value = {
    name: '',
    email: '',
    password: '',
    confirmPassword: ''
  };
  clearErrors();
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

// Watch for backend errors and process them semantically
watch(() => props.errors, (newErrors) => {
  if (newErrors && Object.keys(newErrors).length > 0) {
    handleValidationError(newErrors);
  }
}, { immediate: true, deep: true });

// Form validation with semantic error handling
const validateForm = (): boolean => {
  clearErrors();

  // Name validation
  if (!userData.value.name.trim()) {
    setFieldError('name', 'Full name is required');
  } else if (userData.value.name.trim().length < 2) {
    setFieldError('name', 'Full name must be at least 2 characters');
  }

  // Email validation
  if (!userData.value.email.trim()) {
    setFieldError('email', 'Email is required');
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userData.value.email)) {
    setFieldError('email', 'Please enter a valid email address');
  }

  // Password validation (only for add mode)
  if (props.mode === 'add') {
    if (!userData.value.password) {
      setFieldError('password', 'Password is required');
    } else if (userData.value.password.length < 8) {
      setFieldError('password', 'Password must be at least 8 characters');
    }

    if (!userData.value.confirmPassword) {
      setFieldError('confirmPassword', 'Please confirm your password');
    } else if (userData.value.password !== userData.value.confirmPassword) {
      setFieldError('confirmPassword', 'Passwords do not match');
    }
  }

  return !hasFieldError('name') && !hasFieldError('email') && 
         (!hasFieldError('password') || props.mode === 'edit') && 
         (!hasFieldError('confirmPassword') || props.mode === 'edit');
};

// Computed validation states using semantic error handler
const isNameValid = computed(() => !hasFieldError('name'));
const isEmailValid = computed(() => !hasFieldError('email'));
const isPasswordValid = computed(() => !hasFieldError('password'));
const isConfirmPasswordValid = computed(() => !hasFieldError('confirmPassword'));

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
              @input="clearFieldError('name')"
            />
            <AlertCircleIcon 
              v-if="!isNameValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="getFieldError('name')" class="text-red-500 text-xs mt-1">
            {{ getFieldError('name') }}
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
              @input="clearFieldError('password')"
            />
            <AlertCircleIcon 
              v-if="!isPasswordValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="getFieldError('password')" class="text-red-500 text-xs mt-1">
            {{ getFieldError('password') }}
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
              @input="clearFieldError('email')"
            />
            <AlertCircleIcon 
              v-if="!isEmailValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="getFieldError('email')" class="text-red-500 text-xs mt-1">
            {{ getFieldError('email') }}
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
              @input="clearFieldError('confirmPassword')"
            />
            <AlertCircleIcon 
              v-if="!isConfirmPasswordValid" 
              class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 w-5 h-5" 
            />
          </div>
          <p v-if="getFieldError('confirmPassword')" class="text-red-500 text-xs mt-1">
            {{ getFieldError('confirmPassword') }}
          </p>
        </div>
      </div>
    </div>

    <!-- General Error Message -->
    <div v-if="getFieldError('general')" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
      <p class="text-red-600 text-sm">{{ getFieldError('general') }}</p>
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