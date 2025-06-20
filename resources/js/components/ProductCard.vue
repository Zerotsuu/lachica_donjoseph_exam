<script setup lang="ts">
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
  product: Product;
  variant?: 'regular' | 'wide';
}

interface Emits {
  (e: 'click', product: Product): void;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'regular'
});

const emit = defineEmits<Emits>();

const handleClick = () => {
  emit('click', props.product);
};
</script>

<template>
  <div 
    @click="handleClick"
    class="bg-[#fafafb] border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
    :class="variant === 'wide' ? 'flex flex-col' : 'flex flex-col items-center'"
  >
    <div 
      class="w-full bg-gray-200 rounded mb-4 overflow-hidden flex items-center justify-center"
      :class="variant === 'wide' ? 'aspect-[2/1]' : 'aspect-square'"
    >
      <img 
        v-if="product.image_url" 
        :src="product.image_url" 
        :alt="product.name"
        class="w-full h-full object-cover"
      />
      <svg 
        v-else 
        xmlns="http://www.w3.org/2000/svg" 
        :class="variant === 'wide' ? 'h-20 w-20' : 'h-16 w-16'"
        class="text-gray-300" 
        fill="none" 
        viewBox="0 0 24 24" 
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
      </svg>
    </div>
    
    <div class="w-full">
      <div 
        class="text-xs text-purple-700 font-medium mb-1 truncate" 
        :class="variant === 'regular' ? 'text-center' : ''"
        :title="product.name"
      >
        {{ product.name }}
      </div>
      
      <div 
        class="text-xs font-bold text-gray-700"
        :class="variant === 'regular' ? 'text-center' : ''"
      >
        {{ product.formatted_price }}
      </div>
      
      <div 
        class="text-xs text-gray-500 mt-1"
        :class="variant === 'regular' ? 'text-center' : ''"
      >
        {{ product.stocks }} in stock
      </div>
      
      <div 
        v-if="variant === 'wide' && product.description" 
        class="text-xs text-gray-400 mt-2 line-clamp-2"
      >
        {{ product.description }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style> 