import { ref, computed, watch, readonly } from 'vue'
import axios from 'axios'

// Types
interface User {
  id: number
  name: string
  email: string
  role: string
  email_verified_at?: string
  created_at: string
  last_activity?: string
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
  error_code?: string
}

interface TokenData {
  token: string
  expires_at?: string
  expires_in?: number
  created_at: string
  device_name?: string
  abilities?: string[]
}

interface Device {
  id: number
  name: string
  last_used_at?: string
  created_at: string
  expires_at?: string
  is_current: boolean
  abilities: string[]
}

// Create axios instance for API calls
const apiClient = axios.create({
  baseURL: '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Enhanced token management with persistence
const tokenData = ref<TokenData | null>((() => {
  const storedToken = localStorage.getItem('sanctum_token')
  const storedExpiry = localStorage.getItem('sanctum_token_expires_at')
  const storedCreated = localStorage.getItem('sanctum_token_created_at')
  const storedDevice = localStorage.getItem('sanctum_device_name')
  const storedAbilities = localStorage.getItem('sanctum_abilities')
  
  if (storedToken) {
    return {
      token: storedToken,
      expires_at: storedExpiry || undefined,
      created_at: storedCreated || new Date().toISOString(),
      device_name: storedDevice || undefined,
      abilities: storedAbilities ? JSON.parse(storedAbilities) : [],
    }
  }
  return null
})())

const user = ref<User | null>(null)
const devices = ref<Device[]>([])
const isLoading = ref(false)

// Auto-refresh timer
let refreshTimer: ReturnType<typeof setInterval> | null = null

export function useSanctumApi() {
  // Enhanced auth state with better expiration checking
  const isAuthenticated = computed(() => {
    if (!tokenData.value?.token || !user.value) return false
    
    // Check if token is expired
    if (tokenData.value.expires_at) {
      const expiryDate = new Date(tokenData.value.expires_at)
      if (expiryDate <= new Date()) {
        logout() // Auto-logout on expiry
        return false
      }
    }
    
    return true
  })

  const tokenExpiresAt = computed(() => {
    return tokenData.value?.expires_at ? new Date(tokenData.value.expires_at) : null
  })

  const tokenExpiresIn = computed(() => {
    if (!tokenExpiresAt.value) return null
    const now = new Date()
    const expiry = tokenExpiresAt.value
    const diffMs = expiry.getTime() - now.getTime()
    return Math.max(0, Math.floor(diffMs / 1000 / 60)) // minutes
  })

  const shouldRefreshToken = computed(() => {
    if (!tokenExpiresIn.value) return false
    return tokenExpiresIn.value <= 120 // Refresh if expires within 2 hours
  })

  const currentDevice = computed(() => {
    return tokenData.value?.device_name || 'Unknown Device'
  })

  const tokenAbilities = computed(() => {
    return tokenData.value?.abilities || []
  })

  // Enhanced auth methods
  const login = async (
    email: string, 
    password: string, 
    options: { 
      deviceName?: string, 
      rememberMe?: boolean 
    } = {}
  ): Promise<ApiResponse> => {
    isLoading.value = true
    try {
      const response = await apiClient.post('/auth/login', { 
        email, 
        password,
        device_name: options.deviceName,
        remember_me: options.rememberMe,
      })
      
      if (response.data.success) {
        const loginData = response.data.data
        
        tokenData.value = {
          token: loginData.token,
          expires_at: loginData.expires_at,
          expires_in: loginData.expires_in,
          created_at: new Date().toISOString(),
          device_name: loginData.device_name,
          abilities: loginData.abilities,
        }
        
        user.value = loginData.user
        
        // Enhanced localStorage with all token data
        localStorage.setItem('sanctum_token', loginData.token)
        localStorage.setItem('sanctum_token_expires_at', loginData.expires_at)
        localStorage.setItem('sanctum_token_created_at', tokenData.value.created_at)
        localStorage.setItem('sanctum_device_name', loginData.device_name)
        localStorage.setItem('sanctum_abilities', JSON.stringify(loginData.abilities))
        
        // Setup auto-refresh if needed
        setupAutoRefresh()
        
        // Load user devices
        await loadDevices()
      }
      
      return response.data
    } catch (error: any) {
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
      if (tokenData.value?.token) {
        await apiClient.post('/auth/logout')
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      clearAuthData()
      isLoading.value = false
    }
  }

  const refreshToken = async (): Promise<boolean> => {
    try {
      const response = await apiClient.post('/auth/refresh')
      
      if (response.data.success) {
        // Update token expiration
        if (tokenData.value) {
          tokenData.value.expires_at = response.data.data.expires_at
          tokenData.value.expires_in = response.data.data.expires_in
          localStorage.setItem('sanctum_token_expires_at', response.data.data.expires_at)
        }
        return true
      }
      return false
    } catch (error) {
      console.error('Token refresh failed:', error)
      return false
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
      return error.response?.data || { 
        success: false, 
        message: 'Failed to get user',
        error_code: 'NETWORK_ERROR'
      }
    }
  }

  // Device management
  const loadDevices = async (): Promise<void> => {
    try {
      const response = await apiClient.get('/auth/devices')
      if (response.data.success) {
        devices.value = response.data.data
      }
    } catch (error) {
      console.error('Failed to load devices:', error)
    }
  }

  const revokeDevice = async (tokenId: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/auth/devices/${tokenId}`)
      if (response.data.success) {
        await loadDevices() // Refresh device list
      }
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to revoke device'
      }
    }
  }

  const revokeOtherDevices = async (): Promise<ApiResponse> => {
    try {
      const response = await apiClient.post('/auth/revoke-others')
      if (response.data.success) {
        await loadDevices() // Refresh device list
      }
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to revoke other devices'
      }
    }
  }

  const revokeAllDevices = async (): Promise<ApiResponse> => {
    try {
      const response = await apiClient.post('/auth/revoke-all')
      if (response.data.success) {
        clearAuthData() // Logout current session
      }
      return response.data
    } catch (error: any) {
      return error.response?.data || { 
        success: false, 
        message: 'Failed to revoke all devices'
      }
    }
  }

  // Utility functions
  const hasAbility = (ability: string): boolean => {
    return tokenAbilities.value.includes(ability)
  }

  const hasAnyAbility = (abilities: string[]): boolean => {
    return abilities.some(ability => hasAbility(ability))
  }

  const hasAllAbilities = (abilities: string[]): boolean => {
    return abilities.every(ability => hasAbility(ability))
  }

  const clearAuthData = (): void => {
    tokenData.value = null
    user.value = null
    devices.value = []
    
    // Clear localStorage
    localStorage.removeItem('sanctum_token')
    localStorage.removeItem('sanctum_token_expires_at')
    localStorage.removeItem('sanctum_token_created_at')
    localStorage.removeItem('sanctum_device_name')
    localStorage.removeItem('sanctum_abilities')
    
    // Clear auto-refresh timer
    if (refreshTimer) {
      clearInterval(refreshTimer)
      refreshTimer = null
    }
  }

  const setupAutoRefresh = (): void => {
    // Clear existing timer
    if (refreshTimer) {
      clearInterval(refreshTimer)
    }
    
    // Setup new timer to check every 5 minutes
    refreshTimer = setInterval(async () => {
      if (shouldRefreshToken.value) {
        const success = await refreshToken()
        if (!success) {
          console.warn('Auto token refresh failed')
          logout()
        }
      }
    }, 5 * 60 * 1000) // 5 minutes
  }

  const initializeAuth = async (): Promise<void> => {
    if (tokenData.value?.token) {
      try {
        // Verify token and get current user
        const userResponse = await getCurrentUser()
        if (userResponse.success) {
          setupAutoRefresh()
          await loadDevices()
        } else {
          // Token is invalid, clear auth data
          clearAuthData()
        }
      } catch (error) {
        console.error('Auth initialization failed:', error)
        clearAuthData()
      }
    }
  }

  // Enhanced API methods with better error handling
  const handleApiResponse = <T>(response: any): ApiResponse<T> => {
    if (response.data) {
      return response.data
    }
    return {
      success: false,
      message: 'Unknown error occurred',
      error_code: 'UNKNOWN_ERROR'
    }
  }

  const handleApiError = (error: any): ApiResponse => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      logout()
      return {
        success: false,
        message: 'Authentication expired. Please login again.',
        error_code: 'TOKEN_EXPIRED'
      }
    }
    
    return error.response?.data || { 
      success: false, 
      message: 'Network error occurred',
      error_code: 'NETWORK_ERROR'
    }
  }

  // Orders CRUD with enhanced error handling
  const getOrders = async (): Promise<ApiResponse<Order[]>> => {
    try {
      const response = await apiClient.get('/admin/orders')
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
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
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const updateOrder = async (orderId: number, updateData: { status: string }): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.put(`/admin/orders/${orderId}`, updateData)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const deleteOrder = async (orderId: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/orders/${orderId}`)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const cancelOrder = async (orderId: number): Promise<ApiResponse<Order>> => {
    try {
      const response = await apiClient.patch(`/admin/orders/${orderId}/cancel`)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  // Users CRUD with enhanced error handling
  const getUsers = async (): Promise<ApiResponse<User[]>> => {
    try {
      const response = await apiClient.get('/admin/users')
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
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
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
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
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const deleteUser = async (userId: number): Promise<ApiResponse> => {
    try {
      const response = await apiClient.delete(`/admin/users/${userId}`)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const toggleUserVerification = async (userId: number): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.patch(`/admin/users/${userId}/toggle-verification`)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  const resetUserPassword = async (userId: number): Promise<ApiResponse<User>> => {
    try {
      const response = await apiClient.patch(`/admin/users/${userId}/reset-password`)
      return handleApiResponse(response)
    } catch (error: any) {
      return handleApiError(error)
    }
  }

  // Setup axios interceptors for token management
  apiClient.interceptors.request.use(
    (config) => {
      if (tokenData.value?.token) {
        config.headers.Authorization = `Bearer ${tokenData.value.token}`
      }
      return config
    },
    (error) => Promise.reject(error)
  )

  apiClient.interceptors.response.use(
    (response) => response,
    async (error) => {
      if (error.response?.status === 401 && error.response?.data?.error_code === 'TOKEN_EXPIRED') {
        // Try to refresh token once
        const success = await refreshToken()
        if (success && error.config) {
          // Retry the original request
          return apiClient.request(error.config)
        } else {
          logout()
        }
      }
      return Promise.reject(error)
    }
  )

  // Watch for token expiration and setup auto-refresh
  watch(shouldRefreshToken, (should) => {
    if (should && isAuthenticated.value) {
      refreshToken()
    }
  })

  return {
    // State
    user: readonly(user),
    devices: readonly(devices),
    tokenData: readonly(tokenData),
    isLoading: readonly(isLoading),
    
    // Computed
    isAuthenticated,
    tokenExpiresAt,
    tokenExpiresIn,
    shouldRefreshToken,
    currentDevice,
    tokenAbilities,
    
    // Auth methods
    login,
    logout,
    refreshToken,
    getCurrentUser,
    initializeAuth,
    
    // Device management
    loadDevices,
    revokeDevice,
    revokeOtherDevices,
    revokeAllDevices,
    
    // Utility
    hasAbility,
    hasAnyAbility,
    hasAllAbilities,
    
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