import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from './useToast';

interface CrudViewOptions {
  resourceName: string;
  baseUrl: string;
  displayNameField?: string;
  allowEdit?: boolean;
  allowDelete?: boolean;
}

export function useCrudView<T extends Record<string, any>>(options: CrudViewOptions) {
  const { showSuccess, showError } = useToast();
  
  // State
  const isLoading = ref(false);
  const isDeleting = ref<number | null>(null);
  const isModalOpen = ref(false);
  const modalMode = ref<'edit' | 'view'>('view');
  const selectedItem = ref<T | null>(null);

  // Modal handlers
  const openEditModal = (item: T) => {
    if (!options.allowEdit) return;
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

  // CRUD Operations (Update only, no create)
  const update = async (id: number, data: Partial<T>) => {
    if (!selectedItem.value || !options.allowEdit) return;
    
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
    if (!options.allowDelete) return;
    
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

  // Save handler (update only)
  const save = async (data: Partial<T>) => {
    if (modalMode.value === 'edit' && selectedItem.value && options.allowEdit) {
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
    openEditModal: options.allowEdit ? openEditModal : undefined,
    openViewModal,
    closeModal,
    
    // CRUD actions
    save: options.allowEdit ? save : undefined,
    deleteItem: options.allowDelete ? deleteItem : undefined,
    
    // Helpers
    isItemDeleting
  };
} 