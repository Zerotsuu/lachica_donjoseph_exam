import { ref, computed, readonly } from 'vue'
import axios from 'axios'
import { initializeCsrfProtection } from '@/plugins/sanctum'

// Types
interface User {
  id: number
  name: string
  email: string
  role: string
  email_verified_at: string | null
}

interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  error_code?: string
}

interface Order {
  id: number
  user_id: number
  total_amount: number
  status: string
  created_at: string
  updated_at: string
  items?: OrderItem[]
}

interface OrderItem {
  id: number
  order_id: number
  product_id: number
  quantity: number
  price: number
  product?: {
    id: number
    name: string
    image: string
  }
}

// Global state
const user = ref<User | null>(null)
const isLoading = ref(false)

// API client with session-based authentication
const apiClient = axios.create({
  baseURL: '/api',
  withCredentials: true, // Important for session-based auth
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

/**
 * Composable for Laravel Sanctum SPA authentication
 * Uses session-based authentication as recommended by Laravel docs
 */
export function useSanctumApi() {
  // Computed properties
  const isAuthenticated = computed(() => user.value !== null)

  // Initialize authentication state
  const initializeAuth = async (): Promise<void> => {
    // For SPAs, we just need to check if user is authenticated via session
    try {
      await getCurrentUser()
    } catch {
      console.log('User not authenticated')
      user.value = null
    }
  }

  // Authentication methods
  const login = async (
    email: string, 
    password: string, 
    options: { 
      rememberMe?: boolean 
    } = {}
  ): Promise<ApiResponse> => {
    isLoading.value = true
    try {
      // Initialize CSRF protection before login
      await initializeCsrfProtection()
      
      // Login via standard Laravel route (not API)
      await axios.post('/login', { 
        email, 
        password,
        remember: options.rememberMe,
      })
      
      // For successful login, Laravel redirects or returns success
      // Get the current user after login
      await getCurrentUser()
      
      return {
        success: true,
        message: 'Login successful'
      }
    } catch (error: any) {
      console.error('Login error:', error)
      return error.response?.data || { 
        success: false, 
        message: 'Login failed',
        error_code: 'NETWORK_ERROR'
      }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    isLoading.value = true
    try {
      // Logout via standard Laravel route
      await axios.post('/logout')
      
      // Clear user state
      user.value = null
      
      // Redirect to home page
      window.location.href = '/'
    } catch (error) {
      console.error('Logout error:', error)
      // Clear user state even if logout request fails
      user.value = null
      window.location.href = '/'
    } finally {
      isLoading.value = false
    }
  }

  const getCurrentUser = async (): Promise<ApiResponse<User>> => {
    try {
      // Use the standard /api/user route provided by Laravel
      const response = await apiClient.get('/user')
      user.value = response.data
      return {
        success: true,
        message: 'User retrieved successfully',
        data: response.data
      }
    } catch (error: any) {
      user.value = null
      return error.response?.data || { 
        success: false, 
        message: 'Failed to get user',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  // API methods for protected resources
  const getOrders = async (): Promise<ApiResponse<Order[]>> => {
    try {
      const response = await apiClient.get('/admin/orders')
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to fetch orders',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const createOrder = async (orderData: any): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.post('/admin/orders', orderData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to create order',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const updateOrder = async (id: number, orderData: any): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.put(`/admin/orders/${id}`, orderData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to update order',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const deleteOrder = async (id: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/orders/${id}`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to delete order',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const cancelOrder = async (id: number): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.patch(`/admin/orders/${id}/cancel`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to cancel order',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const getUsers = async (): Promise<ApiResponse<User[]>> => {
    try {
      const response = await apiClient.get('/admin/users')
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to fetch users',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const createUser = async (userData: any): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.post('/admin/users', userData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to create user',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const updateUser = async (id: number, userData: any): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.put(`/admin/users/${id}`, userData)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to update user',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const deleteUser = async (id: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/users/${id}`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to delete user',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const toggleUserVerification = async (id: number): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.patch(`/admin/users/${id}/toggle-verification`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to toggle user verification',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  const resetUserPassword = async (id: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.patch(`/admin/users/${id}/reset-password`)
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to reset user password',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  return {
    // State
    user: readonly(user),
    isLoading: readonly(isLoading),
    
    // Computed
    isAuthenticated,
    
    // Auth methods
    login,
    logout,
    getCurrentUser,
    initializeAuth,
    
    // API methods
    getOrders,
    createOrder,
    updateOrder,
    deleteOrder,
    cancelOrder,
    getUsers,
    createUser,
    updateUser,
    deleteUser,
    toggleUserVerification,
    resetUserPassword,
  }
} 