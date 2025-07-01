import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from './useToast';
import { useErrorHandler } from './useErrorHandler';

interface CrudOptions {
  resourceName: string;
  baseUrl: string;
  displayNameField: string;
  allowCreate?: boolean;
  allowEdit?: boolean;
  allowDelete?: boolean;
  allowView?: boolean;
}

export function useCrud<T extends Record<string, any>>(options: CrudOptions) {
  const { showSuccess, showError } = useToast();
  const { handleError, handleValidationError, createRetryHandler } = useErrorHandler();

  // State management
  const isLoading = ref(false);
  const isModalOpen = ref(false);
  const modalMode = ref<'add' | 'edit' | 'view'>('add');
  const selectedItem = ref<T | null>(null);
  const isDeleting = ref<number | null>(null);

  // Permissions
  const permissions = computed(() => ({
    allowCreate: options.allowCreate ?? true,
    allowEdit: options.allowEdit ?? true,
    allowDelete: options.allowDelete ?? true,
    allowView: options.allowView ?? true,
  }));

  // Modal management
  const openAddModal = () => {
    if (!permissions.value.allowCreate) return;
    selectedItem.value = null;
    modalMode.value = 'add';
    isModalOpen.value = true;
  };

  const openEditModal = (item: T) => {
    if (!permissions.value.allowEdit) return;
    selectedItem.value = item;
    modalMode.value = 'edit';
    isModalOpen.value = true;
  };

  const openViewModal = (item: T) => {
    if (!permissions.value.allowView) return;
    selectedItem.value = item;
    modalMode.value = 'view';
    isModalOpen.value = true;
  };

  const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
  };

  // Utility functions
  const getDisplayName = (item: any): string => {
    return item?.[options.displayNameField] || 'Item';
  };

  // Enhanced error handling with semantic processing
  const getErrorMessage = (errors: any): string => {
    if (typeof errors === 'string') return errors;
    if (typeof errors === 'object' && errors !== null) {
      const firstError = Object.values(errors).flat()[0];
      return typeof firstError === 'string' ? firstError : 'An error occurred';
    }
    return 'An unexpected error occurred';
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

  // CRUD Operations with enhanced error handling
  const create = async (data: Partial<T>) => {
    if (!permissions.value.allowCreate) return;
    
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
          
          // Use semantic error handling
          const processedError = handleValidationError(errors);
          if (Object.keys(processedError).length === 0) {
            // No validation errors, show generic error
            const errorMessage = getErrorMessage(errors);
            showError('Add Failed', errorMessage);
          }
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    } catch (error) {
      console.error(`${options.resourceName} creation failed:`, error);
      handleError(error, 'CrudCreate');
      isLoading.value = false;
    }
  };

  const update = async (id: number, data: Partial<T>) => {
    if (!permissions.value.allowEdit) return;
    
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
          
          // Use semantic error handling
          const processedError = handleValidationError(errors);
          if (Object.keys(processedError).length === 0) {
            // No validation errors, show generic error
            const errorMessage = getErrorMessage(errors);
            showError('Update Failed', errorMessage);
          }
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    } catch (error) {
      console.error(`${options.resourceName} update failed:`, error);
      handleError(error, 'CrudUpdate');
      isLoading.value = false;
    }
  };

  // Enhanced delete with retry mechanism for network failures
  const deleteItem = createRetryHandler(async (item: T) => {
    if (!permissions.value.allowDelete) return;

    const displayName = getDisplayName(item);
    const confirmMessage = `Are you sure you want to delete "${displayName}"? This action cannot be undone.`;
    
    if (!confirm(confirmMessage)) {
      return;
    }

    const itemId = item.id;
    isDeleting.value = itemId;

    try {
      router.delete(`${options.baseUrl}/${itemId}`, {
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
      handleError(error, 'CrudDelete');
      isDeleting.value = null;
    }
  });

  // Check if specific item is being deleted
  const isItemDeleting = (item: T): boolean => {
    return isDeleting.value === item.id;
  };

  // Main save function that routes to create or update
  const save = async (data: Partial<T>) => {
    if (modalMode.value === 'add') {
      await create(data);
    } else if (modalMode.value === 'edit' && selectedItem.value) {
      await update(selectedItem.value.id, data);
    }
  };

  return {
    // State
    isLoading,
    isModalOpen,
    modalMode,
    selectedItem,
    isDeleting,

    // Computed
    permissions,

    // Modal methods
    openAddModal,
    openEditModal,
    openViewModal,
    closeModal,

    // CRUD methods with enhanced error handling
    create,
    update,
    deleteItem,
    save,

    // Utility methods
    isItemDeleting,
    getDisplayName,
  };
} 