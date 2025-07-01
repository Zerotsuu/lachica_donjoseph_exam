<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PencilIcon, TrashIcon } from 'lucide-vue-next';
import ProductFormDialog from '@/components/products/ProductFormDialog.vue';
import { useCrud } from '@/composables/useCrud';

// Define Product interface
interface Product {
  id: number;
  name: string;
  price: string;
  stocks: number;
  image?: string;
}

// Get products from backend (when implemented)
const page = usePage();
const products = computed(() => page.props.products as Product[] || [
  // Fallback sample data
  {
    id: 1,
    name: 'Product 1',
    price: '₱100.00',
    stocks: 50,
    image: ''
  },
  {
    id: 2,
    name: 'Product 2',
    price: '₱150.00',
    stocks: 30,
    image: ''
  }
]);

// Use consolidated CRUD composable
const crud = useCrud<Product>({
  resourceName: 'Product',
  baseUrl: '/admin/products',
  displayNameField: 'name',
  allowCreate: true,
  allowEdit: true,
  allowDelete: true,
  allowView: false
});
</script>

<template>
  <AppLayout>
    <Head title="Products Management" />

    <div class="p-6">
      <div class="flex justify-between items-center mb-6 p-4 rounded-lg bg-[#FFFFFF] shadow-md/30 shadow-gray-500">
        <h1 class="text-2xl font-semibold text-[#8B3F93]">Products Management</h1>
        <Button 
          @click="crud.openAddModal"
          class="bg-[#65558F] text-white rounded-full shadow-md/30 shadow-black"
        >
          Add Product
        </Button>
      </div>

      <div class="grid space-y-4">
        <!-- Table Header Section -->
        <Table class="bg-[#8B3F93] rounded-lg shadow">
          <TableHeader>
            <TableRow class="grid grid-cols-5 gap-4">
              <TableHead class="text-white px-6 py-4">Product Name</TableHead>
              <TableHead class="text-white px-6 py-4">Price</TableHead>
              <TableHead class="text-white px-6 py-4">Image</TableHead>
              <TableHead class="text-white px-6 py-4">Number of Stocks</TableHead>
              <TableHead class="text-white px-6 py-4">Action</TableHead>
            </TableRow>
          </TableHeader>
        </Table>

        <!-- Table Body Section -->
        <TableBody class="grid border-gray-100 rounded-lg">
          <TableRow 
            v-for="product in products" 
            :key="product.id" 
            class="grid grid-cols-5 gap-4 border-gray-100"
          >
            <TableCell class="px-6 py-4">{{ product.name }}</TableCell>
            <TableCell class="px-6 py-4">{{ product.price }}</TableCell>
            <TableCell class="px-6 py-4">
              <img 
                v-if="product.image" 
                :src="product.image" 
                alt="Product" 
                class="w-16 h-16 object-cover rounded"
              />
              <div v-else class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center">
                <span class="text-gray-400 text-sm">No image</span>
              </div>
            </TableCell>
            <TableCell class="px-6 py-4">{{ product.stocks }}</TableCell>
            <TableCell class="px-6 py-4 space-x-2">
              <button 
                class="text-gray-600 hover:text-gray-900"
                @click="crud.openEditModal!(product)"
              >
                <PencilIcon class="w-5 h-5" />
              </button>
              <button 
                class="text-gray-600 hover:text-red-600"
                @click="crud.deleteItem!(product)"
              >
                <TrashIcon class="w-5 h-5" />
              </button>
            </TableCell>
          </TableRow>
        </TableBody>
      </div>

      <!-- Product Form Modal -->
      <ProductFormDialog
        :is-open="crud.isModalOpen.value"
        :mode="crud.modalMode.value"
        :product="crud.selectedItem.value"
        @save="crud.save!"
        @close="crud.closeModal"
      />
    </div>
  </AppLayout>
</template> 