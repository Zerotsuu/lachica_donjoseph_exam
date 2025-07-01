import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { initializeCsrfProtection } from '@/plugins/sanctum'

// Simple session-based authentication state for SPAs
// Complements Inertia form-based login/logout

const isInitialized = ref(false)

export function useAuth() {
  // Get auth data from Inertia page props
  const page = usePage()
  
  // Computed auth state from Inertia props
  const user = computed(() => page.props.auth?.user || null)
  const isAuthenticated = computed(() => !!user.value)
  
  // Initialize authentication (mainly for CSRF protection)
  const initializeAuth = async (): Promise<void> => {
    if (isInitialized.value) return
    
    try {
      // Initialize CSRF protection for the SPA
      await initializeCsrfProtection()
      isInitialized.value = true
      console.log('Authentication initialized with CSRF protection')
    } catch (error) {
      console.error('Failed to initialize authentication:', error)
    }
  }
  
  // Check if user has specific role (safely accessing role property)
  const hasRole = (role: string): boolean => {
    return (user.value as any)?.role === role
  }
  
  // Check if user is admin
  const isAdmin = computed(() => hasRole('admin'))
  
  // Check if user is verified
  const isVerified = computed(() => !!user.value?.email_verified_at)
  
  // Logout function (redirects to logout route)
  const logout = (): void => {
    // Use Inertia's router to handle logout
    window.location.href = '/logout'
  }
  
  return {
    // State
    user,
    isAuthenticated,
    isInitialized,
    
    // Computed
    isAdmin,
    isVerified,
    
    // Methods
    initializeAuth,
    hasRole,
    logout,
  }
} 