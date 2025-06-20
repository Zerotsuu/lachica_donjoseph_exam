<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { ImageIcon, LoaderIcon } from 'lucide-vue-next';
import Modal from '@/components/ui/modal/Modal.vue';

interface Product {
  id?: number;
  name: string;
  price: string;
  stocks: number;
  image?: string;
  image_url?: string;
  description?: string;
  formatted_price?: string;
}

interface Props {
  isOpen: boolean;
  mode: 'add' | 'edit' | 'view';
  product?: Product | null;
  isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'add',
  product: null,
  isLoading: false
});

const emit = defineEmits(['close', 'save']);

interface ProductFormData {
  name: string;
  price: string;
  stocks: number;
  image: string;
  description: string;
  imageFile?: File;
}

const productData = ref<ProductFormData>({
  name: '',
  price: '',
  stocks: 0,
  image: '',
  description: ''
});

// Initialize refs before they are used in watchers
const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

// Watch for changes in props.product and update productData
watch(() => props.product, (newProduct) => {
  if (newProduct) {
    productData.value = {
      name: newProduct.name,
      price: newProduct.price.toString().replace('₱', '').replace(',', ''),
      stocks: newProduct.stocks,
      image: newProduct.image || '',
      description: newProduct.description || '',
      imageFile: undefined
    };
    // Reset image preview when editing existing product
    if (newProduct.image_url) {
      imagePreview.value = newProduct.image_url;
    } else {
      imagePreview.value = null;
    }
  } else {
    // Reset form when adding new product
    productData.value = {
      name: '',
      price: '',
      stocks: 0,
      image: '',
      description: '',
      imageFile: undefined
    };
    imagePreview.value = null;
  }
}, { immediate: true });

// Watch for modal close to reset form
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    // Reset form when modal closes
    productData.value = {
      name: '',
      price: '',
      stocks: 0,
      image: '',
      description: '',
      imageFile: undefined
    };
    imagePreview.value = null;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
});

const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Please select a valid image file.');
      return;
    }
    
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB.');
      return;
    }
    
    productData.value.imageFile = file;
    imagePreview.value = URL.createObjectURL(file);
  }
};

const validateForm = () => {
  if (!productData.value.name.trim()) {
    alert('Product name is required.');
    return false;
  }
  
  if (!productData.value.price || parseFloat(productData.value.price) <= 0) {
    alert('Please enter a valid price.');
    return false;
  }
  
  if (productData.value.stocks < 0) {
    alert('Stock quantity cannot be negative.');
    return false;
  }
  
  return true;
};

const handleSave = () => {
  if (!validateForm()) {
    return;
  }
  
  emit('save', {
    ...productData.value,
    price: productData.value.price.toString(),
    stocks: Number(productData.value.stocks),
    imageFile: productData.value.imageFile
  });
};

const handleClose = () => {
  emit('close');
};

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const removeImage = () => {
  imagePreview.value = null;
  productData.value.imageFile = undefined;
  productData.value.image = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

// Price input is now handled directly with v-model for better performance
</script>

<template>
  <Modal
    :is-open="isOpen"
    :mode="mode"
    :title="mode === 'add' ? 'Add Product' : 'Edit Product'"
    entity="product"
    :is-loading="isLoading"
    @save="handleSave"
    @close="handleClose"
  >
    <div class="grid grid-cols-2 gap-6">
      <!-- Left Column - Image Upload -->
      <div>
        <label class="text-sm font-medium mb-2 block">Product Image</label>
        <div 
          class="border-2 border-dashed border-gray-300 rounded-lg aspect-[4/3] cursor-pointer hover:border-[#8B3F93] transition-colors overflow-hidden relative"
          @click="triggerFileInput"
        >
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept="image/*"
            @change="handleImageUpload"
          />
          
          <!-- No Image State -->
          <div v-if="!imagePreview" class="h-full flex flex-col items-center justify-center">
            <ImageIcon class="w-12 h-12 text-gray-400 mb-2" />
            <p class="text-sm text-gray-500 text-center px-4">
              Click to upload image<br>
              <span class="text-xs">(Max 5MB, JPG/PNG)</span>
            </p>
          </div>
          
          <!-- Image Preview -->
          <div v-else class="relative h-full">
            <img 
              :src="imagePreview" 
              alt="Product preview" 
              class="w-full h-full object-cover"
            />
            <button
              @click.stop="removeImage"
              class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors"
              title="Remove image"
            >
              ×
            </button>
          </div>
        </div>
      </div>

      <!-- Right Column - Form Fields -->
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium mb-2 block">
            Product Name <span class="text-red-500">*</span>
          </label>
          <Input
            v-model="productData.name"
            type="text"
            placeholder="Enter product name"
            :disabled="isLoading"
            required
          />
        </div>

        <div>
          <label class="text-sm font-medium mb-2 block">
            Price <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
            <Input
              v-model="productData.price"
              type="number"
              step="0.01"
              min="0"
              class="pl-7"
              placeholder="0.00"
              :disabled="isLoading"
              required
            />
          </div>
        </div>

        <div>
          <label class="text-sm font-medium mb-2 block">
            Number of Stocks <span class="text-red-500">*</span>
          </label>
          <Input
            v-model.number="productData.stocks"
            type="number"
            placeholder="Enter stock quantity"
            min="0"
            :disabled="isLoading"
            required
          />
        </div>

        <div>
          <label class="text-sm font-medium mb-2 block">Description</label>
          <textarea
            v-model="productData.description"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8B3F93] focus:border-transparent resize-none"
            rows="3"
            placeholder="Enter product description (optional)"
            :disabled="isLoading"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
      <div class="flex items-center space-x-2">
        <LoaderIcon class="w-5 h-5 animate-spin text-[#8B3F93]" />
        <span class="text-sm text-gray-600">
          {{ mode === 'add' ? 'Adding product...' : 'Updating product...' }}
        </span>
      </div>
    </div>
  </Modal>
</template>

<style scoped>
.dialog-overlay {
  background-color: rgba(0, 0, 0, 0.5);
}
</style> 