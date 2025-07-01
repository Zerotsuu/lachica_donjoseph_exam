<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PencilIcon, TrashIcon, PlusIcon, UserCheckIcon, KeyIcon } from 'lucide-vue-next';
import UserFormDialog from '@/components/users/UserFormDialog.vue';
import { useStatus } from '@/composables/useStatus';
import { useCrud } from '@/composables/useCrud';
import { useToast } from '@/composables/useToast';

const { getStatusClass } = useStatus();
const { showSuccess, showError } = useToast();

// Define the User interface
interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  role: string;
  created_at?: string;
}

// Get users data from backend via Inertia props
const page = usePage();
const users = computed(() => page.props.users as User[] || []);

// Use consolidated CRUD composable
const crud = useCrud<User>({
  resourceName: 'User',
  baseUrl: '/admin/users',
  displayNameField: 'name',
  allowCreate: true,
  allowEdit: true,
  allowDelete: true,
  allowView: false
});

// Computed modal mode for UserFormDialog (only supports 'add' | 'edit')
const modalMode = computed((): 'add' | 'edit' => {
  const mode = crud.modalMode.value;
  return mode === 'add' || mode === 'edit' ? mode : 'edit';
});

const handleToggleVerification = async (user: User) => {
  const action = user.email_verified_at ? 'unverify' : 'verify';
  
  if (!confirm(`Are you sure you want to ${action} ${user.name}'s email?`)) {
    return;
  }
  
  try {
    router.patch(`/admin/users/${user.id}/toggle-verification`, {}, {
      onSuccess: () => {
        showSuccess('Verification Updated', `${user.name}'s email verification has been updated.`);
      },
      onError: (errors) => {
        console.error('Toggle verification error:', errors);
        const errorMessage = Object.values(errors).flat().join(' ') || 'Failed to update verification.';
        showError('Update Failed', errorMessage);
      }
    });
  } catch (error) {
    console.error('Toggle verification failed:', error);
    showError('Update Failed', 'An unexpected error occurred while updating verification.');
  }
};

const handleResetPassword = async (user: User) => {
  if (!confirm(`Are you sure you want to reset ${user.name}'s password? They will receive a new temporary password.`)) {
    return;
  }
  
  try {
    router.patch(`/admin/users/${user.id}/reset-password`, {}, {
      onSuccess: () => {
        showSuccess('Password Reset', `${user.name}'s password has been reset successfully.`);
      },
      onError: (errors) => {
        console.error('Reset password error:', errors);
        const errorMessage = Object.values(errors).flat().join(' ') || 'Failed to reset password.';
        showError('Reset Failed', errorMessage);
      }
    });
  } catch (error) {
    console.error('Reset password failed:', error);
    showError('Reset Failed', 'An unexpected error occurred while resetting password.');
  }
};

const getUserStatus = (user: User) => {
  return user.email_verified_at ? 'Verified' : 'Unverified';
};
</script>

<template>
  <AppLayout>
    <Head title="Users Management" />

    <div class="p-6">
      <div class="flex justify-between items-center mb-6 p-4 rounded-lg bg-[#FFFFFF] shadow-md/30 shadow-gray-500">
        <h1 class="text-2xl font-semibold text-[#8B3F93]">Users Management</h1>
        <Button 
          @click="crud.openAddModal"
          :disabled="crud.isLoading.value"
          class="bg-[#65558F] text-white rounded-full shadow-md/30 shadow-black hover:bg-[#5a4a7a] disabled:opacity-50"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add User
        </Button>
      </div>

      <!-- Loading State -->
      <div v-if="crud.isLoading.value" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#8B3F93]"></div>
        <p class="mt-2 text-gray-600">Loading users...</p>
      </div>

      <!-- Users Table -->
      <div v-else class="grid space-y-4">
        <!-- Table Header Section -->
        <Table class="bg-[#8B3F93] rounded-lg shadow">
          <TableHeader>
            <TableRow class="grid grid-cols-5 gap-4">
              <TableHead class="text-white px-6 py-4">Full Name</TableHead>
              <TableHead class="text-white px-6 py-4">Email</TableHead>
              <TableHead class="text-white px-6 py-4">Role</TableHead>
              <TableHead class="text-white px-6 py-4">Status</TableHead>
              <TableHead class="text-white px-6 py-4">Action</TableHead>
            </TableRow>
          </TableHeader>
        </Table>

        <!-- Empty State -->
        <div v-if="users.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
          <div class="text-gray-400 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
          <p class="text-gray-500 mb-4">Get started by creating your first user.</p>
          <Button 
            @click="crud.openAddModal"
            class="bg-[#65558F] text-white hover:bg-[#5a4a7a]"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Add Your First User
          </Button>
        </div>

        <!-- Table Body Section -->
        <TableBody v-else class="grid border-gray-100 rounded-lg">
          <TableRow 
            v-for="user in users" 
            :key="user.id" 
            class="grid grid-cols-5 gap-4 border-gray-100 bg-white hover:bg-gray-50 transition-colors"
            :class="{ 'opacity-50': crud.isDeleting.value === user.id }"
          >
            <TableCell class="px-6 py-4 font-medium">{{ user.name }}</TableCell>
            <TableCell class="px-6 py-4">{{ user.email }}</TableCell>
            <TableCell class="px-6 py-4">
              <span class="capitalize px-2 py-1 rounded-full text-xs font-medium"
                    :class="user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'">
                {{ user.role }}
              </span>
            </TableCell>
            <TableCell class="px-6 py-4">
              <span :class="getStatusClass(getUserStatus(user))">{{ getUserStatus(user) }}</span>
            </TableCell>
            <TableCell class="px-6 py-4 space-x-2">
              <button 
                class="text-gray-600 hover:text-[#8B3F93] disabled:opacity-50"
                :disabled="crud.isDeleting.value === user.id"
                @click="crud.openEditModal!(user)"
                title="Edit User"
              >
                <PencilIcon class="w-5 h-5" />
              </button>
              <button 
                class="text-gray-600 hover:text-blue-600 disabled:opacity-50"
                :disabled="crud.isDeleting.value === user.id || crud.isLoading.value"
                @click="handleToggleVerification(user)"
                :title="user.email_verified_at ? 'Unverify Email' : 'Verify Email'"
              >
                <UserCheckIcon class="w-5 h-5" />
              </button>
              <button 
                class="text-gray-600 hover:text-yellow-600 disabled:opacity-50"
                :disabled="crud.isDeleting.value === user.id || crud.isLoading.value"
                @click="handleResetPassword(user)"
                title="Reset Password"
              >
                <KeyIcon class="w-5 h-5" />
              </button>
              <button 
                class="text-gray-600 hover:text-red-600 disabled:opacity-50"
                :disabled="crud.isDeleting.value === user.id"
                @click="crud.deleteItem!(user)"
                title="Delete User"
              >
                <TrashIcon class="w-5 h-5" />
              </button>
              <div v-if="crud.isDeleting.value === user.id" class="inline-block ml-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-red-500"></div>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </div>

      <!-- User Form Modal -->
      <UserFormDialog
        :is-open="crud.isModalOpen.value"
        :mode="modalMode"
        :user="crud.selectedItem.value"
        :is-loading="crud.isLoading.value"
        @save="crud.save!"
        @close="crud.closeModal"
      />
    </div>
  </AppLayout>
</template> 