<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import ProductCard from '@/components/ProductCard.vue';
import ProductModal from '@/components/ProductModal.vue';
import CartModal from '@/components/CartModal.vue';
import ThankYouModal from '@/components/ThankYouModal.vue';
import { useToast } from '@/composables/useToast';

// Define interfaces
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

// Get products from backend
const page = usePage();
const allProducts = computed(() => page.props.products as Product[] || []);

// Search and filter functionality
const searchQuery = ref('');
const sortOrder = ref<'asc' | 'desc'>('asc');

// Filtered and sorted products
const filteredProducts = computed(() => {
  let products = allProducts.value;
  
  // Filter by search query
  if (searchQuery.value.trim()) {
    products = products.filter(product => 
      product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      product.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  
  // Sort by price
  products = [...products].sort((a, b) => {
    return sortOrder.value === 'asc' ? a.price - b.price : b.price - a.price;
  });
  
  return products;
});

// Pagination
const itemsPerPage = 12; // Increased to show more products per page
const currentPage = ref(1);
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage));

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredProducts.value.slice(start, end);
});

// Split products for display (8 regular + 4 wide format)
const regularProducts = computed(() => paginatedProducts.value.slice(0, 8));
const wideProducts = computed(() => paginatedProducts.value.slice(8, 12));

const setSortOrder = (order: 'asc' | 'desc') => {
  sortOrder.value = order;
  currentPage.value = 1; // Reset to first page when sorting
};

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const handleSearch = () => {
  currentPage.value = 1; // Reset to first page when searching
};

// Product modal state
const selectedProduct = ref<Product | null>(null);
const isModalOpen = ref(false);

// Get page props and toast
const { showSuccess, showError } = useToast();

// Cart modal state
const isCartModalOpen = ref(false);
const cartItems = ref<CartItem[]>([]);
const isLoadingCart = ref(false);

// Thank you modal state
const isThankYouModalOpen = ref(false);

const handleProductClick = (product: Product) => {
  selectedProduct.value = product;
  isModalOpen.value = true;
};

const handleCartClick = async () => {
  if (page.props.auth?.user) {
    await fetchCart();
  }
  isCartModalOpen.value = true;
};

// Fetch cart data from backend
const fetchCart = async () => {
  if (!page.props.auth?.user) {
    console.log('No authenticated user, skipping cart fetch');
    return;
  }
  
  try {
    console.log('Fetching cart data...');
    isLoadingCart.value = true;
    const response = await fetch('/cart', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    console.log('Cart fetch response status:', response.status);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Cart fetch response data:', data);
    
    // Ensure we're updating the reactive reference properly
    cartItems.value = [...(data.cart_items || [])];
    console.log('Updated cartItems reactive value:', cartItems.value);
    
  } catch (error) {
    console.error('Cart fetch error:', error);
    showError('Failed to load cart');
  } finally {
    isLoadingCart.value = false;
  }
};

const handleAddToCart = async (product: Product, quantity: number) => {
  if (!page.props.auth?.user) {
    showError('Please login to add items to cart');
    return;
  }

  console.log('Adding to cart:', { product_id: product.id, quantity });

  // Use Inertia router for proper CSRF handling
  router.post('/cart/add', {
    product_id: product.id,
    quantity: quantity
  }, {
    preserveScroll: true,
    preserveState: true,
    only: [], // Don't reload any props
    onSuccess: () => {
      console.log('Add to cart success');
      showSuccess('Item added to cart successfully');
      fetchCart(); // Refresh cart data
    },
    onError: (errors) => {
      console.error('Add to cart error:', errors);
      const errorMessage = Object.values(errors)[0] as string || 'Failed to add item to cart';
      showError(errorMessage);
    }
  });
};

const handlePlaceOrder = async (items: CartItem[]) => {
  if (!page.props.auth?.user) {
    showError('Please login to place orders');
    return;
  }

  if (items.length === 0) {
    showError('Cart is empty');
    return;
  }

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const response = await fetch('/cart/place-order', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: 'Server error' }));
      throw new Error(errorData.error || `HTTP ${response.status}`);
    }

    await response.json();
    // Don't show the success toast, we'll show the thank you modal instead
    
    // Refresh cart data to show empty cart
    await fetchCart();
    
    // Close cart modal and show thank you modal
    isCartModalOpen.value = false;
    isThankYouModalOpen.value = true;
    
  } catch (error) {
    console.error('Place order error:', error);
    showError(error instanceof Error ? error.message : 'Failed to place order');
  }
};

const handleUpdateQuantity = async (itemId: number, quantity: number) => {
  if (!page.props.auth?.user) return;

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const response = await fetch(`/cart/items/${itemId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ quantity })
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: 'Server error' }));
      throw new Error(errorData.error || `HTTP ${response.status}`);
    }

    const data = await response.json();
    showSuccess(data.message || 'Cart updated successfully');
    await fetchCart(); // Refresh cart data
    
  } catch (error) {
    console.error('Update cart error:', error);
    showError(error instanceof Error ? error.message : 'Failed to update cart');
  }
};

const handleRemoveItem = async (itemId: number) => {
  if (!page.props.auth?.user) return;

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const response = await fetch(`/cart/items/${itemId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: 'Server error' }));
      throw new Error(errorData.error || `HTTP ${response.status}`);
    }

    const data = await response.json();
    showSuccess(data.message || 'Item removed from cart');
    await fetchCart(); // Refresh cart data
    
  } catch (error) {
    console.error('Remove item error:', error);
    showError(error instanceof Error ? error.message : 'Failed to remove item');
  }
};

// Load cart on component mount
onMounted(() => {
  if (page.props.auth?.user) {
    fetchCart();
  }
});
</script>

<template>

    <Head title="Landing Page">
        <!-- <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" /> -->
    </Head>
    <!-- NAV BAR -->
    <div class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-8 py-3">
        <!-- LOGO -->
            <img src="/images/logo.svg" alt="PurpleBug Logo" class="h-8 w-auto" />

        <!-- NAVIGATION ITEMS -->
        <nav class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <img src="/images/genericavatar.svg" alt="Avatar" class="w-8 h-8 rounded-full object-cover" />
                <div class="leading-tight">
                    <div class="font-semibold text-gray-700 text-sm">
                        Hi, {{ $page.props.auth.user ? $page.props.auth.user.name : 'Guest' }}!
                    </div>
                    <div class="text-xs text-gray-400 capitalize">
                        {{ $page.props.auth.user ? $page.props.auth.user.role || 'User' : 'Welcome to our store' }}
                    </div>
                </div>
            </div>
            <!-- CART ICON -->
            <button @click="handleCartClick" class="relative p-1 hover:bg-gray-100 rounded-lg transition">
                <img src="/images/shoppingcart.svg" alt="Cart" class="w-8 h-8 text-purple-700" />
                <span v-if="cartItems.length > 0" class="absolute -top-1 -right-1 bg-[#8B3F93] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                    {{ cartItems.reduce((total, item) => total + item.quantity, 0) }}
                </span>
            </button>
            <!-- DASHBOARD - Only for Admin users -->
            <Link v-if="$page.props.auth.user && $page.props.auth.user.role === 'admin'" :href="route('dashboard')"
                class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-[#8B3F93] text-white font-bold shadow-md hover:bg-purple-800 transition">
                <img src="/images/lock.svg" alt="Dashboard" class="w-5 h-5" />
                Dashboard
            </Link>
            <!-- LOGOUT for regular users, LOGIN/SIGNUP for guests -->
            <template v-else-if="$page.props.auth.user">
                <Link :href="route('logout')" method="post" as="button"
                    class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold shadow-md transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    LOGOUT
                </Link>
            </template>
            <!-- LOGIN/SIGNUP for guests -->
            <template v-else>
                <Link :href="route('login')"
                    class="inline-flex items-center gap-2 px-6 py-2 rounded-lg purplebug text-white font-bold shadow-md hover:bg-purple-800 transition">
                    <img src="/images/lock.svg" alt="Login" class="w-5 h-5" />
                    LOGIN
                </Link>
                <!-- REGISTER -->
                <Link :href="route('register')"
                    class="inline-flex items-center gap-2 px-6 py-2 rounded-lg purplebug text-white font-bold shadow-md hover:bg-purple-800 transition">
                    <img src="/images/pentool.svg" alt="Sign Up" class="w-5 h-5" />
                    SIGN UP
                </Link>
            </template>
        </nav>
    </div>
    <div class="w-full bg-white min-h-screen flex flex-col">
        <!-- Main Content Container -->
        <div class="flex-1 w-full max-w-7xl mx-auto mb-8 p-6 bg-white">
            <!-- Search and Sort Row -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-12 mb-6">
                <!-- Search -->
                <div class="flex items-center gap-2 w-full max-w-xs">
                    <input 
                        v-model="searchQuery" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Search products..." 
                        class="w-full rounded-lg border border-gray-300 bg-[#fafafb] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-200" 
                    />
                    <button @click="handleSearch" class="p-2 rounded-full bg-white border border-gray-300 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" /></svg>
                    </button>
                </div>
                <!-- Sort Buttons -->
                <div class="flex gap-2">
                    <button 
                        @click="setSortOrder('asc')"
                        :class="sortOrder === 'asc' ? 'bg-[#8B3F93] text-white' : 'bg-gray-100 text-gray-500 hover:bg-[#8B3F93] hover:text-white'"
                        class="px-4 py-1.5 rounded-lg text-xs font-semibold shadow border border-gray-200 transition"
                    >
                        Price ascending
                    </button>
                    <button 
                        @click="setSortOrder('desc')"
                        :class="sortOrder === 'desc' ? 'bg-[#8B3F93] text-white' : 'bg-gray-100 text-gray-500 hover:bg-[#8B3F93] hover:text-white'"
                        class="px-4 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 transition"
                    >
                        Price descending
                    </button>
                </div>
            </div>
            <!-- Product Cards Grid -->
            <div v-if="paginatedProducts.length > 0" class="space-y-8">
                <!-- Regular Product Cards Grid (8 products in 4x2 grid) -->
                <div v-if="regularProducts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <ProductCard 
                        v-for="product in regularProducts" 
                        :key="product.id" 
                        :product="product" 
                        variant="regular"
                        @click="handleProductClick"
                    />
                </div>
                
                <!-- Wide Product Cards Row (4 products in 2x2 grid) -->
                <div v-if="wideProducts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <ProductCard 
                        v-for="product in wideProducts" 
                        :key="'wide'+product.id" 
                        :product="product" 
                        variant="wide"
                        @click="handleProductClick"
                    />
                </div>
            </div>
            <!-- No Products Message -->
            <div v-if="paginatedProducts.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                <p class="text-gray-500">{{ searchQuery ? 'Try adjusting your search terms.' : 'No products available at the moment.' }}</p>
            </div>
            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-8">
                <button 
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="text-xs flex items-center gap-1 px-2 py-1 transition"
                    :class="currentPage === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-600 hover:text-purple-700'"
                >
                    <span>&larr;</span> Previous
                </button>
                
                <!-- Page numbers - Simple approach: show all pages up to 10, then show ellipsis -->
                <template v-if="totalPages <= 10">
                    <button 
                        v-for="page in totalPages" 
                        :key="page"
                        @click="goToPage(page)"
                        :class="page === currentPage ? 'bg-[#8B3F93] text-white' : 'text-gray-700 hover:bg-gray-200'"
                        class="w-6 h-6 rounded text-xs font-bold transition"
                    >
                        {{ page }}
                    </button>
                </template>
                
                <!-- For more than 10 pages, show smart pagination -->
                <template v-else>
                    <!-- Always show first page -->
                    <button 
                        @click="goToPage(1)"
                        :class="1 === currentPage ? 'bg-[#8B3F93] text-white' : 'text-gray-700 hover:bg-gray-200'"
                        class="w-6 h-6 rounded text-xs font-bold transition"
                    >
                        1
                    </button>
                    
                    <!-- Show ellipsis if current page is far from start -->
                    <span v-if="currentPage > 4" class="text-xs text-gray-400">...</span>
                    
                    <!-- Show pages around current page -->
                    <template v-for="page in totalPages" :key="page">
                        <button 
                            v-if="page > 1 && page < totalPages && Math.abs(page - currentPage) <= 2"
                            @click="goToPage(page)"
                            :class="page === currentPage ? 'bg-[#8B3F93] text-white' : 'text-gray-700 hover:bg-gray-200'"
                            class="w-6 h-6 rounded text-xs font-bold transition"
                        >
                            {{ page }}
                        </button>
                    </template>
                    
                    <!-- Show ellipsis if current page is far from end -->
                    <span v-if="currentPage < totalPages - 3" class="text-xs text-gray-400">...</span>
                    
                    <!-- Always show last page -->
                    <button 
                        @click="goToPage(totalPages)"
                        :class="totalPages === currentPage ? 'bg-[#8B3F93] text-white' : 'text-gray-700 hover:bg-gray-200'"
                        class="w-6 h-6 rounded text-xs font-bold transition"
                    >
                        {{ totalPages }}
                    </button>
                </template>
                
                <button 
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="text-xs flex items-center gap-1 px-2 py-1 transition"
                    :class="currentPage === totalPages ? 'text-gray-400 cursor-not-allowed' : 'text-gray-600 hover:text-purple-700'"
                >
                    Next <span>&rarr;</span>
                </button>
            </div>
            
            <!-- Results info -->
            <div v-if="filteredProducts.length > 0" class="text-center mt-4">
                <p class="text-xs text-gray-500">
                    Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredProducts.length) }} 
                    of {{ filteredProducts.length }} products
                    <span v-if="searchQuery"> for "{{ searchQuery }}"</span>
                </p>
            </div>
        </div>
        <!-- Footer -->
        <footer class="w-full border-t border-gray-300 bg-white py-8 flex flex-col items-center mt-auto">
            
                <img src="/images/logo.svg" alt="PurpleBug Logo" class="h-8 w-auto mb-2" />
            
            <div class="text-xs text-gray-400">Copyright 2025 PurpleBug Inc.</div>
        </footer>
        
        <!-- Product Modal -->
        <ProductModal 
            :product="selectedProduct"
            :open="isModalOpen"
            @update:open="isModalOpen = $event"
            @add-to-cart="handleAddToCart"
        />
        
        <!-- Cart Modal -->
        <CartModal 
            :open="isCartModalOpen"
            :cart-items="cartItems"
            @update:open="isCartModalOpen = $event"
            @place-order="handlePlaceOrder"
            @update-quantity="handleUpdateQuantity"
            @remove-item="handleRemoveItem"
        />
        
        <!-- Thank You Modal -->
        <ThankYouModal 
            :open="isThankYouModalOpen"
            @update:open="isThankYouModalOpen = $event"
        />
    </div>
</template>
