<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import Modal from '@/components/ui/modal/Modal.vue';
import { UserIcon, PackageIcon, HashIcon, CheckCircleIcon, ClockIcon, TruckIcon, XCircleIcon } from 'lucide-vue-next';

interface Order {
  id?: number;
  customer_name: string;
  product_name: string;
  quantity: number;
  status: string;
  total_amount?: string;
}

interface Props {
  isOpen: boolean;
  mode: 'add' | 'edit' | 'view';
  order?: Order | null;
  isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'view',
  order: null
});

const emit = defineEmits(['close', 'save']);

interface OrderFormData {
  name: string;
  product: string;
  quantity: number;
  status: string;
  totalAmount: number;
}

const orderData = ref<OrderFormData>({
  name: '',
  product: '',
  quantity: 0,
  status: '',
  totalAmount: 0
});

const statusOptions = [
  { value: 'Delivered', label: 'Delivered', icon: CheckCircleIcon, color: 'text-green-600', bgColor: 'bg-green-100' },
  { value: 'Pending', label: 'Pending', icon: ClockIcon, color: 'text-yellow-600', bgColor: 'bg-yellow-100' },
  { value: 'For Delivery', label: 'For Delivery', icon: TruckIcon, color: 'text-blue-600', bgColor: 'bg-blue-100' },
  { value: 'Cancelled', label: 'Cancelled', icon: XCircleIcon, color: 'text-red-600', bgColor: 'bg-red-100' }
];

// Validation
const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const validateForm = () => {
  errors.value = {};
  
  // For edit mode, only validate status
  if (props.mode === 'edit') {
    if (!orderData.value.status) {
      errors.value.status = 'Status is required';
    }
    return Object.keys(errors.value).length === 0;
  }
  
  // For add mode, validate all fields
  if (!orderData.value.name.trim()) {
    errors.value.name = 'Customer name is required';
  }
  
  if (!orderData.value.product.trim()) {
    errors.value.product = 'Product name is required';
  }
  
  if (orderData.value.quantity <= 0) {
    errors.value.quantity = 'Quantity must be greater than 0';
  }
  
  if (!orderData.value.status) {
    errors.value.status = 'Status is required';
  }
  
  return Object.keys(errors.value).length === 0;
};


watch(() => props.order, (newOrder) => {
  if (newOrder) {
    orderData.value = {
      name: newOrder.customer_name,
      product: newOrder.product_name,
      quantity: newOrder.quantity,
      status: newOrder.status,
      totalAmount: newOrder.total_amount ? parseFloat(newOrder.total_amount.replace(/[^0-9.-]+/g, '')) : 0
    };
  } else {
    orderData.value = {
      name: '',
      product: '',
      quantity: 0,
      status: '',
      totalAmount: 0
    };
  }
  errors.value = {};
}, { immediate: true });

const handleSave = () => {
  if (!validateForm()) {
    return;
  }
  
  isSubmitting.value = true;
  
  // For edit mode, only send status update
  if (props.mode === 'edit') {
    emit('save', {
      status: orderData.value.status
    });
  } else {
    // For add mode, send all data
    emit('save', {
      ...orderData.value
    });
  }
  isSubmitting.value = false;
};

const handleClose = () => {
  errors.value = {};
  emit('close');
};


</script>

<template>
  <Modal
    :is-open="isOpen"
    :mode="mode"
    :title="props.order?.customer_name || 'New Order'"
    entity="order"
    @save="handleSave"
    @close="handleClose"
  >
    <div class="space-y-6">

      <!-- Form Fields -->
      <div class="grid grid-cols-2 gap-6">
        <!-- Left Column -->
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium mb-2 text-gray-700 flex items-center">
              <UserIcon class="w-4 h-4 mr-2 text-gray-500" />
              Customer Name
              <span v-if="mode === 'edit'" class="ml-2 text-xs text-gray-500">(read-only)</span>
            </label>
            <Input
              v-model="orderData.name"
              type="text"
              :disabled="mode === 'edit' || mode === 'view'"
              placeholder="Customer name"
              :class="{ 'border-red-300': errors.name }"
            />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
          </div>

          
          <div>
            <label class="text-sm font-medium mb-2 text-gray-700 flex items-center">
              Order Status
              <span v-if="mode === 'edit'" class="ml-2 text-xs text-green-600">(editable)</span>
            </label>
            <select 
              v-model="orderData.status" 
              :disabled="mode === 'view'"
              class="w-full rounded-lg px-3 py-2 text-sm bg-white text-gray-900 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-300': errors.status }"
            >
              <option value="" disabled>Select status</option>
              <option 
                v-for="status in statusOptions" 
                :key="status.value" 
                :value="status.value"
              >
                {{ status.label }}
              </option>
            </select>
            <p v-if="errors.status" class="text-red-500 text-xs mt-1">{{ errors.status }}</p>
            <p v-if="mode === 'edit'" class="text-xs text-blue-600 mt-1">
              Only status can be changed after order creation. Stock will be automatically adjusted if cancelled.
            </p>
          </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium mb-2 text-gray-700 flex items-center">
              <PackageIcon class="w-4 h-4 mr-2 text-gray-500" />
              Product Ordered
              <span v-if="mode === 'edit'" class="ml-2 text-xs text-gray-500">(read-only)</span>
            </label>
            <Input
              v-model="orderData.product"
              type="text"
              :disabled="mode === 'edit' || mode === 'view'"
              placeholder="Product name"
              :class="{ 'border-red-300': errors.product }"
            />
            <p v-if="errors.product" class="text-red-500 text-xs mt-1">{{ errors.product }}</p>
          </div>
          <div>
            <label class="text-sm font-medium mb-2 text-gray-700 flex items-center">
              <HashIcon class="w-4 h-4 mr-2 text-gray-500" />
              Quantity
              <span v-if="mode === 'edit'" class="ml-2 text-xs text-gray-500">(read-only)</span>
            </label>
            <Input
              v-model="orderData.quantity"
              type="number"
              :disabled="mode === 'edit' || mode === 'view'"
              placeholder="Quantity"
              :class="{ 'border-red-300': errors.quantity }"
            />
            <p v-if="errors.quantity" class="text-red-500 text-xs mt-1">{{ errors.quantity }}</p>
          </div>

          
        </div>
      </div>


      <!-- Loading State -->
      <div v-if="isSubmitting" class="flex items-center justify-center py-4">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-[#8B3F93]"></div>
        <span class="ml-2 text-sm text-gray-600">Saving order...</span>
      </div>
    </div>
  </Modal>
</template> 