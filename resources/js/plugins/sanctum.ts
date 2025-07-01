import axios from 'axios'

// Get token from global window object or session (provided by HandleInertiaRequests)
declare global {
  interface Window {
    Laravel?: {
      csrfToken?: string
    }
  }
}

export function setupSanctum() {
  // Set up axios defaults
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  axios.defaults.headers.common['Accept'] = 'application/json'
  axios.defaults.headers.common['Content-Type'] = 'application/json'

  // Set CSRF token if available
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content')
  }

  // Add request interceptor to include Sanctum token from page props
  axios.interceptors.request.use(
    (config) => {
      // Check if we have a Sanctum token in the page props
      const pageElement = document.getElementById('app')
      if (pageElement) {
        const pageData = pageElement.getAttribute('data-page')
        if (pageData) {
          try {
            const page = JSON.parse(pageData)
            const sanctumToken = page.props?.auth?.token
            
            if (sanctumToken) {
              config.headers.Authorization = `Bearer ${sanctumToken}`
            }
          } catch (error) {
            console.warn('Could not parse page data for Sanctum token')
          }
        }
      }
      
      return config
    },
    (error) => Promise.reject(error)
  )

  // Add response interceptor for token expiration handling
  axios.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Token expired or invalid, redirect to login
        window.location.href = '/login'
      }
      return Promise.reject(error)
    }
  )
}

export default {
  install() {
    setupSanctum()
  }
} 