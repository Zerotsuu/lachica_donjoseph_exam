<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';


interface Product {
  id: number;
  name: string;
  price: number;
  formatted_price: string;
  stocks: number;
  image?: string;
  image_url?: string;
  description?: string;
  in_stock: boolean;
}

interface Props {
  product: Product | null;
  open: boolean;
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'add-to-cart', product: Product, quantity: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const selectedQuantity = ref(1);

// Generate quantity options based on available stock
const quantityOptions = computed(() => {
  if (!props.product) return [];
  const max = Math.min(props.product.stocks, 10); // Limit to 10 or available stock
  return Array.from({ length: max }, (_, i) => i + 1);
});

const handleClose = () => {
  emit('update:open', false);
  selectedQuantity.value = 1; // Reset quantity when closing
};

const handleAddToCart = () => {
  if (props.product) {
    emit('add-to-cart', props.product, selectedQuantity.value);
    handleClose();
  }
};


</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="max-w-4xl w-full mx-4">
      <DialogHeader>
        <DialogTitle class="sr-only">Product Details</DialogTitle>
      </DialogHeader>
      
      <div v-if="product" class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
        <!-- Product Image -->
        <div class="flex justify-center">
          <div class="w-full max-w-md aspect-square bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center">
            <img 
              v-if="product.image_url" 
              :src="product.image_url" 
              :alt="product.name"
              class="w-full h-full object-cover"
            />
            <svg 
              v-else 
              xmlns="http://www.w3.org/2000/svg" 
              class="h-24 w-24 text-gray-300" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
            </svg>
          </div>
        </div>

        <!-- Product Details -->
        <div class="flex flex-col justify-center space-y-6">
          <!-- Product Name -->
          <h2 class="text-2xl font-bold text-[#8B3F93] mb-2">{{ product.name }}</h2>
          
          <!-- Price -->
          <div class="text-4xl font-bold text-gray-900">{{ product.formatted_price }}</div>
          
          <!-- Description -->
          <div v-if="product.description" class="text-gray-600 text-sm leading-relaxed">
            {{ product.description }}
          </div>
          
          <!-- Stock Info -->
          <div class="text-sm text-gray-500">
            {{ product.stocks }} in stock
          </div>

          <!-- Quantity Selector -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700">Quantity</label>
            <select 
              v-model="selectedQuantity"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#8B3F93] focus:border-transparent"
            >
              <option v-for="qty in quantityOptions" :key="qty" :value="qty">
                {{ qty }}
              </option>
            </select>
          </div>

          <!-- Add to Cart Button -->
          <Button 
            @click="handleAddToCart"
            :disabled="!product.in_stock || product.stocks === 0"
            class="w-full bg-[#8B3F93] hover:bg-purple-800 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
          >
            <span v-if="product.in_stock && product.stocks > 0">Add To Cart</span>
            <span v-else>Out of Stock</span>
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Custom styles for the modal */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style> 