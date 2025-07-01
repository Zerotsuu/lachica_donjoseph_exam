<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { PencilIcon, TrashIcon, XCircleIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import OrderFormDialog from '@/components/orders/OrderFormDialog.vue';
import { useCrud } from '@/composables/useCrud';
import { useToast } from '@/composables/useToast';

// Define the Order interface
interface Order {
  id: number;
  customer_name: string;
  product_name: string;
  quantity: number;
  total_amount: string;
  status: string;
  status_color?: string;
  can_be_cancelled?: boolean;
  created_at?: string;
}

// Get orders data from backend
const page = usePage();
const orders = computed(() => page.props.orders as Order[] || []);

// Toast notifications
const { showSuccess, showError } = useToast();

// Use the view-only CRUD composable (no add functionality)
const crud = useCrud<Order>({
  resourceName: 'Order',
  baseUrl: '/admin/orders',
  displayNameField: 'customer_name',
  allowEdit: true,  // Allow editing order status
  allowDelete: true // Allow deleting orders
});

// Custom cancel order functionality
const handleCancel = async (order: Order) => {
  if (!order.can_be_cancelled) {
    showError('Cannot Cancel', 'This order cannot be cancelled in its current status.');
    return;
  }

  if (!confirm(`Are you sure you want to cancel the order for "${order.customer_name}"?`)) {
    return;
  }

  try {
    router.patch(`/admin/orders/${order.id}/cancel`, {}, {
      onSuccess: () => {
        showSuccess('Order Cancelled', `Order for ${order.customer_name} has been successfully cancelled.`);
      },
      onError: (errors) => {
        console.error('Cancel order error:', errors);
        const errorMessage = Object.values(errors).flat().join(' ') || 'Failed to cancel order.';
        showError('Cancel Failed', errorMessage);
      }
    });
  } catch (error) {
    console.error('Order cancellation failed:', error);
    showError('Cancel Failed', 'An unexpected error occurred while cancelling the order.');
  }
};

// Table configuration
const columns = [
  { key: 'customer_name', label: 'Customer Name' },
  { key: 'product_name', label: 'Product' },
  { key: 'quantity', label: 'Quantity' },
  { key: 'total_amount', label: 'Total Amount' },
  { key: 'status', label: 'Status' }
];

const actions = [
  {
    icon: PencilIcon,
    label: 'Edit Order',
    variant: 'edit' as const,
    onClick: (order: Order) => crud.openEditModal!(order)
  },
  {
    icon: XCircleIcon,
    label: 'Cancel Order',
    variant: 'custom' as const,
    onClick: (order: Order) => handleCancel(order),
    disabled: (order: Order) => !order.can_be_cancelled
  },
  {
    icon: TrashIcon,
    label: 'Delete Order',
    variant: 'delete' as const,
    onClick: (order: Order) => crud.deleteItem!(order),
    loading: (order: Order) => crud.isItemDeleting(order)
  }
];
</script>

<template>
  <AppLayout>
    <Head title="Orders Management" />

    <div class="p-6">
      <!-- Page Header - No Add Button for Orders -->
      <PageHeader 
        title="Orders Management"
        :show-action="false"
      />

      <!-- Data Table -->
      <DataTable
        :data="orders"
        :columns="columns"
        :actions="actions"
        :loading="crud.isLoading.value"
        empty-title="No orders found"
        empty-description="Orders will appear here when customers place them through the store."
        :show-action="false"
      >
        <!-- Custom cell for status with proper styling -->
        <template #cell-status="{ value }">
          <StatusBadge :status="value" variant="order" />
        </template>

        <!-- Custom cell for total amount with currency formatting -->
        <template #cell-total_amount="{ value }">
          <span class="font-medium text-green-600">{{ value }}</span>
        </template>

        <!-- Custom cell for quantity -->
        <template #cell-quantity="{ value }">
          <span class="text-center font-medium">{{ value }}</span>
        </template>
      </DataTable>

      <!-- Order Form Modal (Edit/View Only) -->
      <OrderFormDialog
        :is-open="crud.isModalOpen.value"
        :mode="crud.modalMode.value"
        :order="crud.selectedItem.value"
        :is-loading="crud.isLoading.value"
        @save="crud.save!"
        @close="crud.closeModal"
      />
    </div>
  </AppLayout>
</template> 