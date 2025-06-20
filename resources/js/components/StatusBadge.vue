<script setup lang="ts">
interface Props {
  status: string;
  variant?: 'order' | 'user' | 'stock' | 'custom';
  customColor?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'custom'
});

const getStatusClasses = (status: string, variant: string) => {
  const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';
  
  if (variant === 'order') {
    switch (status.toLowerCase()) {
      case 'delivered':
        return `${baseClasses} bg-green-100 text-green-800`;
      case 'pending':
        return `${baseClasses} bg-yellow-100 text-yellow-800`;
      case 'for delivery':
        return `${baseClasses} bg-blue-100 text-blue-800`;
      case 'cancelled':
        return `${baseClasses} bg-red-100 text-red-800`;
      default:
        return `${baseClasses} bg-gray-100 text-gray-800`;
    }
  }
  
  if (variant === 'user') {
    switch (status.toLowerCase()) {
      case 'verified':
        return `${baseClasses} bg-green-100 text-green-800`;
      case 'unverified':
        return `${baseClasses} bg-red-100 text-red-800`;
      default:
        return `${baseClasses} bg-gray-100 text-gray-800`;
    }
  }
  
  if (variant === 'stock') {
    const stockNumber = parseInt(status);
    if (stockNumber < 10) {
      return `${baseClasses} bg-red-100 text-red-800`;
    } else if (stockNumber < 50) {
      return `${baseClasses} bg-yellow-100 text-yellow-800`;
    } else {
      return `${baseClasses} bg-green-100 text-green-800`;
    }
  }
  
  // Custom variant
  return `${baseClasses} ${props.customColor || 'bg-gray-100 text-gray-800'}`;
};
</script>

<template>
  <span :class="getStatusClasses(status, variant)">
    {{ status }}
  </span>
</template> 