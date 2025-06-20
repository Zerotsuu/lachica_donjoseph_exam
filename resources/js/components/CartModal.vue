<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface CartItem {
  id: number;
  product: {
    id: number;
    name: string;
    price: number;
    formatted_price: string;
    image?: string;
    image_url?: string;
  };
  quantity: number;
  total: number;
  formatted_total: string;
}

interface Props {
  open: boolean;
  cartItems?: CartItem[];
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'place-order', items: CartItem[]): void;
  (e: 'update-quantity', itemId: number, quantity: number): void;
  (e: 'remove-item', itemId: number): void;
}

const props = withDefaults(defineProps<Props>(), {
  cartItems: () => []
});

const emit = defineEmits<Emits>();
const page = usePage();

// Check if user is authenticated
const isAuthenticated = computed(() => !!page.props.auth?.user);

// Calculate total
const cartTotal = computed(() => {
  const total = props.cartItems.reduce((sum, item) => sum + item.total, 0);
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(total);
});

const handleClose = () => {
  emit('update:open', false);
};

const handlePlaceOrder = () => {
  if (props.cartItems.length > 0) {
    emit('place-order', props.cartItems);
  }
};

const handleQuantityChange = (itemId: number, newQuantity: number) => {
  if (newQuantity > 0) {
    emit('update-quantity', itemId, newQuantity);
  }
};

const handleRemoveItem = (itemId: number) => {
  emit('remove-item', itemId);
};

// Generate quantity options (1-10)
const quantityOptions = Array.from({ length: 10 }, (_, i) => i + 1);
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
      <DialogHeader class="flex-shrink-0">
        <DialogTitle class="flex items-center gap-3 text-xl font-semibold text-gray-900">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#8B3F93]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.1 5a1 1 0 001 1h9.2a1 1 0 001-1L17 13H7z" />
          </svg>
          Cart
          <Button 
            v-if="isAuthenticated"
            @click="handlePlaceOrder"
            :disabled="cartItems.length === 0"
            class="ml-auto bg-[#8B3F93] hover:bg-purple-800 text-white text-sm px-4 py-2 rounded-lg"
          >
            PLACE ORDER
          </Button>
        </DialogTitle>
      </DialogHeader>
      
      <!-- Authentication Check -->
      <div v-if="!isAuthenticated" class="flex flex-col items-center justify-center py-12 px-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Login Required</h3>
        <p class="text-gray-500 mb-4">You need to be logged in to view your cart and place orders.</p>
        <div class="flex gap-3">
          <Button 
            @click="$inertia.visit(route('login'))"
            class="bg-[#8B3F93] hover:bg-purple-800 text-white px-6 py-2 rounded-lg"
          >
            Login
          </Button>
          <Button 
            @click="$inertia.visit(route('register'))"
            variant="outline"
            class="border-[#8B3F93] text-[#8B3F93] hover:bg-[#8B3F93] hover:text-white px-6 py-2 rounded-lg"
          >
            Sign Up
          </Button>
        </div>
      </div>

      <!-- Cart Content for Authenticated Users -->
      <div v-else class="flex flex-col flex-1 overflow-hidden">
        <!-- Empty Cart -->
        <div v-if="cartItems.length === 0" class="flex flex-col items-center justify-center py-12 px-6 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.1 5a1 1 0 001 1h9.2a1 1 0 001-1L17 13H7z" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
          <p class="text-gray-500">Add some products to your cart to get started.</p>
        </div>

        <!-- Cart Items -->
        <div v-else class="flex-1 overflow-y-auto px-6 py-4">
          <div class="space-y-4">
            <div v-for="item in cartItems" :key="item.id" class="bg-gray-50 rounded-lg p-4">
              <div class="flex gap-4">
                <!-- Product Image -->
                <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                  <img 
                    v-if="item.product.image_url" 
                    :src="item.product.image_url" 
                    :alt="item.product.name"
                    class="w-full h-full object-cover"
                  />
                  <svg 
                    v-else 
                    xmlns="http://www.w3.org/2000/svg" 
                    class="h-8 w-8 text-gray-300" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
                  </svg>
                </div>

                <!-- Product Details -->
                <div class="flex-1 min-w-0">
                  <h4 class="text-[#8B3F93] font-medium text-sm mb-1 truncate">{{ item.product.name }}</h4>
                  <p class="text-lg font-bold text-gray-900 mb-2">{{ item.formatted_total }}</p>
                  
                  <!-- Quantity Controls -->
                  <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600">Quantity</label>
                    <select 
                      :value="item.quantity"
                      @change="handleQuantityChange(item.id, parseInt(($event.target as HTMLSelectElement).value))"
                      class="px-2 py-1 border border-gray-300 rounded text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#8B3F93] focus:border-transparent"
                    >
                      <option v-for="qty in quantityOptions" :key="qty" :value="qty">
                        {{ qty }}
                      </option>
                    </select>
                    
                    <!-- Remove Button -->
                    <button 
                      @click="handleRemoveItem(item.id)"
                      class="text-red-500 hover:text-red-700 text-sm font-medium ml-auto"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cart Total -->
        <div v-if="cartItems.length > 0" class="flex-shrink-0 bg-[#8B3F93] text-white p-6 mt-4">
          <div class="flex items-center justify-between">
            <span class="text-lg font-semibold">TOTAL:</span>
            <span class="text-2xl font-bold">{{ cartTotal }}</span>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Custom scrollbar for cart items */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #8B3F93;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #7a3082;
}
</style> 