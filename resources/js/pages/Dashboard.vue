<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { PencilIcon, TrashIcon, PlusIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import ProductFormDialog from '@/components/products/ProductFormDialog.vue';
import { useCrud } from '@/composables/useCrud';

// Define the Product interface
interface Product {
  id: number;
  name: string;
  price: string;
  stocks: number;
  image?: string;
  image_url?: string;
  description?: string;
  formatted_price?: string;
}

// Get products data from backend
const page = usePage();
const products = computed(() => page.props.products as Product[] || []);

// Use the CRUD composable
const crud = useCrud<Product>({
  resourceName: 'Product',
  baseUrl: '/admin/products',
  displayNameField: 'name'
});

// Table configuration
const columns = [
  { key: 'name', label: 'Product Name' },
  { key: 'price', label: 'Price', formatter: (value: string) => formatPrice(value) },
  { key: 'stocks', label: 'Number of Stocks' }
];

const actions = [
  {
    icon: PencilIcon,
    label: 'Edit Product',
    variant: 'edit' as const,
    onClick: (product: Product) => crud.openEditModal(product)
  },
  {
    icon: TrashIcon,
    label: 'Delete Product',
    variant: 'delete' as const,
    onClick: (product: Product) => crud.deleteItem(product),
    loading: (product: Product) => crud.isItemDeleting(product)
  }
];

// Price formatter
const formatPrice = (price: string | number) => {
  const numPrice = typeof price === 'string' ? parseFloat(price.replace(/[^0-9.-]+/g, '')) : price;
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(numPrice);
};
</script>

<template>
  <AppLayout>
    <Head title="Products Management" />

    <div class="p-6">
      <!-- Page Header -->
      <PageHeader 
        title="Products Management"
        action-text="Add Product"
        :action-icon="PlusIcon"
        :loading="crud.isLoading.value"
        @action="crud.openAddModal"
      />

      <!-- Data Table -->
      <DataTable
        :data="products"
        :columns="columns"
        :actions="actions"
        :loading="crud.isLoading.value"
        empty-title="No products found"
        empty-description="Get started by creating your first product."
        empty-action-text="Add Your First Product"
        @empty-action="crud.openAddModal"
      >
        <!-- Custom cell for stocks with color coding -->
        <template #cell-stocks="{ value }">
          <StatusBadge :status="value.toString()" variant="stock" />
        </template>
      </DataTable>

      <!-- Product Form Modal -->
      <ProductFormDialog
        :is-open="crud.isModalOpen.value"
        :mode="crud.modalMode.value"
        :product="crud.selectedItem.value"
        :is-loading="crud.isLoading.value"
        @save="crud.save"
        @close="crud.closeModal"
      />
    </div>
  </AppLayout>
</template>
