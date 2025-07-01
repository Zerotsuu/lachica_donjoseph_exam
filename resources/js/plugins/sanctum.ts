import axios from 'axios'

// Simple Sanctum setup for SPAs following Laravel documentation
// Uses session-based authentication (no tokens)

declare global {
  interface Window {
    Laravel?: {
      csrfToken?: string
    }
  }
}

export function setupSanctum() {
  // Set up axios defaults for Laravel
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  axios.defaults.headers.common['Accept'] = 'application/json'
  axios.defaults.headers.common['Content-Type'] = 'application/json'
  
  // Enable credentials for session-based auth
  axios.defaults.withCredentials = true

  // Set initial CSRF token from meta tag
  refreshCsrfToken()

  // Request interceptor to ensure CSRF token is always sent
  axios.interceptors.request.use(
    (config) => {
      const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken
      }
      return config
    },
    (error) => Promise.reject(error)
  )

  // Response interceptor for handling auth errors
  axios.interceptors.response.use(
    (response) => response,
    async (error) => {
      const originalRequest = error.config

      // Handle CSRF token mismatch (419)
      if (error.response?.status === 419 && !originalRequest._retry) {
        console.warn('CSRF token mismatch detected, refreshing...')
        originalRequest._retry = true
        
        try {
          // Refresh CSRF token from server
          await initializeCsrfProtection()
          
          // Update the original request with new token
          const newCsrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          if (newCsrfToken) {
            originalRequest.headers['X-CSRF-TOKEN'] = newCsrfToken
          }
          
          // Retry the original request
          return axios.request(originalRequest)
        } catch (refreshError) {
          console.error('Failed to refresh CSRF token:', refreshError)
          // Redirect to login if CSRF refresh fails
          window.location.href = '/login'
          return Promise.reject(error)
        }
      }

      // Handle authentication errors (401)
      if (error.response?.status === 401) {
        console.warn('Authentication required, redirecting to login')
        window.location.href = '/login'
      }
      
      return Promise.reject(error)
    }
  )
}

/**
 * Initialize CSRF protection for SPA authentication
 * This should be called before making any authenticated requests
 */
export async function initializeCsrfProtection(): Promise<void> {
  try {
    // Get CSRF cookie from Laravel Sanctum endpoint
    await axios.get('/sanctum/csrf-cookie')
    console.log('CSRF protection initialized')
  } catch (error) {
    console.error('Failed to initialize CSRF protection:', error)
    throw error
  }
}

/**
 * Get CSRF token from meta tag and set in axios defaults
 */
export function refreshCsrfToken(): void {
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    const csrfToken = token.getAttribute('content')
    if (csrfToken) {
      axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken
    }
  }
}

/**
 * Update CSRF token in meta tag (useful after logout)
 */
export function updateCsrfTokenInMeta(newToken: string): void {
  const metaTag = document.head.querySelector('meta[name="csrf-token"]')
  if (metaTag) {
    metaTag.setAttribute('content', newToken)
  } else {
    // Create meta tag if it doesn't exist
    const newMetaTag = document.createElement('meta')
    newMetaTag.name = 'csrf-token'
    newMetaTag.content = newToken
    document.head.appendChild(newMetaTag)
  }
  
  // Update axios default header
  axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken
}

export default {
  install() {
    setupSanctum()
  }
} 