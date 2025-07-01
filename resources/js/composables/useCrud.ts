import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from './useToast';

interface CrudOptions {
  resourceName: string;
  baseUrl: string;
  displayNameField?: string;
  allowCreate?: boolean;
  allowEdit?: boolean;
  allowDelete?: boolean;
  allowView?: boolean;
}

export function useCrud<T extends Record<string, any>>(options: CrudOptions) {
  const { showSuccess, showError } = useToast();
  
  // Default permissions
  const permissions = {
    allowCreate: options.allowCreate ?? true,
    allowEdit: options.allowEdit ?? true,
    allowDelete: options.allowDelete ?? true,
    allowView: options.allowView ?? true,
  };
  
  // State
  const isLoading = ref(false);
  const isDeleting = ref<number | null>(null);
  const isModalOpen = ref(false);
  const modalMode = ref<'add' | 'edit' | 'view'>('add');
  const selectedItem = ref<T | null>(null);

  // Modal handlers
  const openAddModal = () => {
    if (!permissions.allowCreate) return;
    modalMode.value = 'add';
    selectedItem.value = null;
    isModalOpen.value = true;
  };

  const openEditModal = (item: T) => {
    if (!permissions.allowEdit) return;
    modalMode.value = 'edit';
    selectedItem.value = { ...item };
    isModalOpen.value = true;
  };

  const openViewModal = (item: T) => {
    if (!permissions.allowView) return;
    modalMode.value = 'view';
    selectedItem.value = { ...item };
    isModalOpen.value = true;
  };

  const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
  };

  // Helper functions (moved up for reuse)
  const getDisplayName = (item: Partial<T>): string => {
    const nameField = options.displayNameField || 'name';
    return (item[nameField] as string) || options.resourceName;
  };

  const getErrorMessage = (errors: Record<string, any>): string => {
    return Object.values(errors).flat().join(' ') || `Failed to process ${options.resourceName.toLowerCase()}.`;
  };

  const prepareFormData = (data: Partial<T>, isUpdate = false): FormData => {
    const formData = data instanceof FormData ? data : new FormData();
    
    if (!(data instanceof FormData)) {
      Object.keys(data).forEach(key => {
        const value = data[key];
        if (value !== undefined && value !== null) {
          // Check if value is a File by checking its constructor name
          if (typeof value === 'object' && value.constructor?.name === 'File') {
            formData.append(key, value as File);
          } else {
            formData.append(key, String(value));
          }
        }
      });
      
      if (isUpdate) {
        formData.append('_method', 'PUT');
      }
    }
    
    return formData;
  };

  // CRUD Operations
  const create = async (data: Partial<T>) => {
    if (!permissions.allowCreate) return;
    
    isLoading.value = true;
    
    try {
      const formData = prepareFormData(data);

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
    if (!permissions.allowEdit || !selectedItem.value) return;
    
    isLoading.value = true;
    
    try {
      const formData = prepareFormData(data, true);

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
    if (!permissions.allowDelete) return;
    
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
    if (modalMode.value === 'add' && permissions.allowCreate) {
      await create(data);
    } else if (modalMode.value === 'edit' && selectedItem.value && permissions.allowEdit) {
      await update(selectedItem.value.id, data);
    }
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
    
    // Permissions
    permissions: computed(() => permissions),
    
    // Modal actions
    openAddModal,
    openEditModal,
    openViewModal,
    closeModal,
    
    // CRUD actions
    save,
    deleteItem,
    
    // Helpers
    isItemDeleting,
    getDisplayName,
  };
} 