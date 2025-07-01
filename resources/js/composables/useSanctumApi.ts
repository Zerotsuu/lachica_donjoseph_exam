import { ref, computed } from 'vue'
import axios from 'axios'

// Types
interface User {
  id: number
  name: string
  email: string
  role: string
  email_verified_at?: string
  created_at: string
}

interface Order {
  id: number
  customer_name: string
  product_name: string
  quantity: number
  total_amount: string
  status: string
  status_color: string
  can_be_cancelled: boolean
  created_at: string
}

interface ApiResponse<T = any> {
  success: boolean
  message?: string
  data?: T
}

// Create axios instance for API calls
const apiClient = axios.create({
  baseURL: '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Token management
const token = ref<string | null>(localStorage.getItem('sanctum_token'))
const user = ref<User | null>(null)

export function useSanctumApi() {
  // Auth state
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Auth methods
  const login = async (email: string, password: string): Promise<ApiResponse> => {
    try {
      const response = await apiClient.post('/auth/login', { email, password })
      
      if (response.data.success) {
        token.value = response.data.data.token
        user.value = response.data.data.user
        localStorage.setItem('sanctum_token', token.value!)
      }
      
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Login failed' }
    }
  }

  const logout = async (): Promise<void> => {
    try {
      if (token.value) {
        await apiClient.post('/auth/logout')
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('sanctum_token')
    }
  }

  const getCurrentUser = async (): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.get('/auth/me')
      if (response.data.success) {
        user.value = response.data.data
      }
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to get user' }
    }
  }

  // Orders CRUD
  const getOrders = async (): Promise<ApiResponse<Order[]>> => {
    try {
      const response = await apiClient.get('/admin/orders')
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to fetch orders' }
    }
  }

  const createOrder = async (orderData: {
    customer_name: string
    product_id: number
    quantity: number
    status: string
  }): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.post('/admin/orders', orderData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to create order' }
    }
  }

  const updateOrder = async (orderId: number, updateData: { status: string }): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.put(`/admin/orders/${orderId}`, updateData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to update order' }
    }
  }

  const deleteOrder = async (orderId: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/orders/${orderId}`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to delete order' }
    }
  }

  const cancelOrder = async (orderId: number): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.patch(`/admin/orders/${orderId}/cancel`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to cancel order' }
    }
  }

  // Users CRUD
  const getUsers = async (): Promise<ApiResponse<User[]>> => {
    try {
      const response = await apiClient.get('/admin/users')
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to fetch users' }
    }
  }

  const createUser = async (userData: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role?: string
  }): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.post('/admin/users', userData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to create user' }
    }
  }

  const updateUser = async (userId: number, userData: {
    name: string
    email: string
    password?: string
    password_confirmation?: string
    role?: string
  }): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.put(`/admin/users/${userId}`, userData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to update user' }
    }
  }

  const deleteUser = async (userId: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/users/${userId}`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to delete user' }
    }
  }

  const toggleUserVerification = async (userId: number): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.patch(`/admin/users/${userId}/toggle-verification`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to toggle verification' }
    }
  }

  const resetUserPassword = async (userId: number): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.patch(`/admin/users/${userId}/reset-password`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { success: false, message: 'Failed to reset password' }
    }
  }

  // Initialize auth state on load
  const initializeAuth = async (): Promise<void> => {
    if (token.value) {
      const result = await getCurrentUser()
      if (!result.success) {
        // Token is invalid, clear auth state
        logout()
      }
    }
  }

  // Set up axios interceptors
  apiClient.interceptors.request.use(
    (config) => {
      if (token.value) {
        config.headers.Authorization = `Bearer ${token.value}`
      }
      return config
    },
    (error) => Promise.reject(error)
  )

  apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Token expired or invalid, clear auth state
        logout()
      }
      return Promise.reject(error)
    }
  )

  return {
    // State
    isAuthenticated,
    user: computed(() => user.value),
    token: computed(() => token.value),

    // Auth methods
    login,
    logout,
    getCurrentUser,
    initializeAuth,

    // Orders CRUD
    getOrders,
    createOrder,
    updateOrder,
    deleteOrder,
    cancelOrder,

    // Users CRUD
    getUsers,
    createUser,
    updateUser,
    deleteUser,
    toggleUserVerification,
    resetUserPassword,
  }
} 