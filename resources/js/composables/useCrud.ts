import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from './useToast';

interface CrudOptions {
  resourceName: string;
  baseUrl: string;
  displayNameField?: string;
}

export function useCrud<T extends Record<string, any>>(options: CrudOptions) {
  const { showSuccess, showError } = useToast();
  
  // State
  const isLoading = ref(false);
  const isDeleting = ref<number | null>(null);
  const isModalOpen = ref(false);
  const modalMode = ref<'add' | 'edit' | 'view'>('add');
  const selectedItem = ref<T | null>(null);

  // Modal handlers
  const openAddModal = () => {
    modalMode.value = 'add';
    selectedItem.value = null;
    isModalOpen.value = true;
  };

  const openEditModal = (item: T) => {
    modalMode.value = 'edit';
    selectedItem.value = { ...item };
    isModalOpen.value = true;
  };

  const openViewModal = (item: T) => {
    modalMode.value = 'view';
    selectedItem.value = { ...item };
    isModalOpen.value = true;
  };

  const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
  };

  // CRUD Operations
  const create = async (data: Partial<T>) => {
    isLoading.value = true;
    
    try {
      const formData = data instanceof FormData ? data : new FormData();
      
      if (!(data instanceof FormData)) {
        Object.keys(data).forEach(key => {
          if (data[key] !== undefined && data[key] !== null) {
            formData.append(key, data[key] as string);
          }
        });
      }

      router.post(options.baseUrl, formData, {
        onSuccess: () => {
          const displayName = getDisplayName(data);
          showSuccess(
            `${options.resourceName} Added`, 
            `${displayName} has been successfully added.`
          );
          closeModal();
        },
        onError: (errors) => {
          console.error(`Add ${options.resourceName.toLowerCase()} error:`, errors);
          const errorMessage = getErrorMessage(errors);
          showError('Add Failed', errorMessage);
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    } catch (error) {
      console.error(`${options.resourceName} creation failed:`, error);
      showError('Operation Failed', 'An unexpected error occurred. Please try again.');
      isLoading.value = false;
    }
  };

  const update = async (id: number, data: Partial<T>) => {
    if (!selectedItem.value) return;
    
    isLoading.value = true;
    
    try {
      const formData = data instanceof FormData ? data : new FormData();
      
      if (!(data instanceof FormData)) {
        Object.keys(data).forEach(key => {
          if (data[key] !== undefined && data[key] !== null) {
            formData.append(key, data[key] as string);
          }
        });
        formData.append('_method', 'PUT');
      }

      router.post(`${options.baseUrl}/${id}`, formData, {
        onSuccess: () => {
          const displayName = getDisplayName(data);
          showSuccess(
            `${options.resourceName} Updated`, 
            `${displayName} has been successfully updated.`
          );
          closeModal();
        },
        onError: (errors) => {
          console.error(`Update ${options.resourceName.toLowerCase()} error:`, errors);
          const errorMessage = getErrorMessage(errors);
          showError('Update Failed', errorMessage);
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    } catch (error) {
      console.error(`${options.resourceName} update failed:`, error);
      showError('Operation Failed', 'An unexpected error occurred. Please try again.');
      isLoading.value = false;
    }
  };

  const deleteItem = async (item: T) => {
    const displayName = getDisplayName(item);
    
    if (!confirm(`Are you sure you want to delete "${displayName}"? This action cannot be undone.`)) {
      return;
    }

    isDeleting.value = item.id;
    
    try {
      router.delete(`${options.baseUrl}/${item.id}`, {
        onSuccess: () => {
          showSuccess(
            `${options.resourceName} Deleted`, 
            `${displayName} has been successfully deleted.`
          );
        },
        onError: (errors) => {
          console.error(`Delete ${options.resourceName.toLowerCase()} error:`, errors);
          const errorMessage = getErrorMessage(errors);
          showError('Delete Failed', errorMessage);
        },
        onFinish: () => {
          isDeleting.value = null;
        }
      });
    } catch (error) {
      console.error(`${options.resourceName} deletion failed:`, error);
      showError('Delete Failed', 'An unexpected error occurred while deleting the item.');
      isDeleting.value = null;
    }
  };

  // Save handler (create or update)
  const save = async (data: Partial<T>) => {
    if (modalMode.value === 'add') {
      await create(data);
    } else if (modalMode.value === 'edit' && selectedItem.value) {
      await update(selectedItem.value.id, data);
    }
  };

  // Helper functions
  const getDisplayName = (item: Partial<T>): string => {
    const nameField = options.displayNameField || 'name';
    return (item[nameField] as string) || options.resourceName;
  };

  const getErrorMessage = (errors: Record<string, any>): string => {
    return Object.values(errors).flat().join(' ') || `Failed to process ${options.resourceName.toLowerCase()}.`;
  };

  const isItemDeleting = (item: T): boolean => {
    return isDeleting.value === item.id;
  };

  return {
    // State
    isLoading: computed(() => isLoading.value),
    isDeleting: computed(() => isDeleting.value),
    isModalOpen: computed(() => isModalOpen.value),
    modalMode: computed(() => modalMode.value),
    selectedItem: computed(() => selectedItem.value),
    
    // Modal actions
    openAddModal,
    openEditModal,
    openViewModal,
    closeModal,
    
    // CRUD actions
    save,
    deleteItem,
    
    // Helpers
    isItemDeleting
  };
} 