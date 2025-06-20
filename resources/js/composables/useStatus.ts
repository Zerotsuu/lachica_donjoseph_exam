export const useStatus = () => {
  const getStatusClass = (status: string) => {
    const statusMap: { [key: string]: string } = {
      'Active': 'bg-emerald-100 text-emerald-700',
      'Inactive': 'bg-red-100 text-red-700',
      'Delivered': 'bg-emerald-100 text-emerald-700',
      'Pending': 'bg-amber-100 text-amber-700',
      'For Delivery': 'bg-lime-100 text-lime-700',
      'Cancelled': 'bg-red-100 text-red-700'
    };

    return `${statusMap[status] || 'bg-gray-100 text-gray-700'} px-3 py-1 rounded-full text-sm`;
  };

  return {
    getStatusClass
  };
}; 