import { ref, computed } from 'vue'
import { useToast } from './useToast'
import type { ApiError } from '@/types/api'

/**
 * Semantic Error Handler
 * 
 * Provides consistent error handling, user feedback, and debugging
 * across the application with semantic error categorization.
 */

// Error state management
const errors = ref<Record<string, any>>({})
const isLoading = ref(false)
const lastError = ref<ApiError | null>(null)

// Error types for semantic handling
const ERROR_TYPES = {
  VALIDATION: 'validation',
  AUTHENTICATION: 'authentication',
  AUTHORIZATION: 'authorization',
  NETWORK: 'network',
  SERVER: 'server',
  BUSINESS: 'business',
  UNKNOWN: 'unknown'
} as const

type ErrorType = typeof ERROR_TYPES[keyof typeof ERROR_TYPES]

interface ProcessedError {
  type: ErrorType
  message: string
  details?: any
  userMessage: string
  shouldRetry: boolean
  shouldShowToast: boolean
  severity: 'low' | 'medium' | 'high' | 'critical'
}

export function useErrorHandler() {
  const { showError } = useToast()

  /**
   * Clear all errors
   */
  const clearErrors = (): void => {
    errors.value = {}
    lastError.value = null
  }

  /**
   * Clear specific field error
   */
  const clearFieldError = (field: string): void => {
    if (errors.value[field]) {
      delete errors.value[field]
    }
  }

  /**
   * Set field-specific error
   */
  const setFieldError = (field: string, message: string): void => {
    errors.value[field] = [message]
  }

  /**
   * Process and categorize error semantically
   */
  const processError = (error: any): ProcessedError => {
    // Handle different error formats
    if (error?.response?.data) {
      return processApiError(error.response.data)
    }
    
    if (error?.data) {
      return processApiError(error.data)
    }
    
    if (error instanceof Error) {
      return processJavaScriptError(error)
    }
    
    if (typeof error === 'string') {
      return processStringError(error)
    }
    
    return processUnknownError(error)
  }

  /**
   * Process API error responses
   */
  const processApiError = (errorData: ApiError & { status?: number }): ProcessedError => {
    const status = errorData.status || 500
    
    // Determine error type based on status code and content
    let type: ErrorType = ERROR_TYPES.UNKNOWN
    let severity: ProcessedError['severity'] = 'medium'
    let shouldRetry = false
    
    if (status === 401) {
      type = ERROR_TYPES.AUTHENTICATION
      severity = 'high'
    } else if (status === 403) {
      type = ERROR_TYPES.AUTHORIZATION
      severity = 'high'
    } else if (status === 422) {
      type = ERROR_TYPES.VALIDATION
      severity = 'low'
    } else if (status >= 500) {
      type = ERROR_TYPES.SERVER
      severity = 'critical'
      shouldRetry = true
    } else if (status === 0 || !navigator.onLine) {
      type = ERROR_TYPES.NETWORK
      severity = 'high'
      shouldRetry = true
    } else if (status >= 400 && status < 500) {
      type = ERROR_TYPES.BUSINESS
      severity = 'medium'
    }

    // Generate user-friendly message
    const userMessage = generateUserMessage(type, status, errorData.message)

    return {
      type,
      message: errorData.message || 'An error occurred',
      details: errorData.errors || errorData.debug,
      userMessage,
      shouldRetry,
      shouldShowToast: type !== ERROR_TYPES.VALIDATION,
      severity
    }
  }

  /**
   * Process JavaScript runtime errors
   */
  const processJavaScriptError = (error: Error): ProcessedError => {
    return {
      type: ERROR_TYPES.UNKNOWN,
      message: error.message,
      details: {
        name: error.name,
        stack: error.stack
      },
      userMessage: 'An unexpected error occurred. Please try again.',
      shouldRetry: false,
      shouldShowToast: true,
      severity: 'medium'
    }
  }

  /**
   * Process string errors
   */
  const processStringError = (error: string): ProcessedError => {
    return {
      type: ERROR_TYPES.UNKNOWN,
      message: error,
      userMessage: error,
      shouldRetry: false,
      shouldShowToast: true,
      severity: 'medium'
    }
  }

  /**
   * Process unknown error formats
   */
  const processUnknownError = (error: any): ProcessedError => {
    return {
      type: ERROR_TYPES.UNKNOWN,
      message: 'Unknown error occurred',
      details: error,
      userMessage: 'Something went wrong. Please try again.',
      shouldRetry: false,
      shouldShowToast: true,
      severity: 'medium'
    }
  }

  /**
   * Generate user-friendly error messages
   */
  const generateUserMessage = (type: ErrorType, status: number, originalMessage?: string): string => {
    switch (type) {
      case ERROR_TYPES.AUTHENTICATION:
        return 'Your session has expired. Please log in again.'
      
      case ERROR_TYPES.AUTHORIZATION:
        return 'You don\'t have permission to perform this action.'
      
      case ERROR_TYPES.VALIDATION:
        return 'Please check the form fields and try again.'
      
      case ERROR_TYPES.NETWORK:
        return 'Network connection issue. Please check your internet connection.'
      
      case ERROR_TYPES.SERVER:
        return 'Server is temporarily unavailable. Please try again in a moment.'
      
      case ERROR_TYPES.BUSINESS:
        return originalMessage || 'The requested action could not be completed.'
      
      default:
        return originalMessage || 'An unexpected error occurred. Please try again.'
    }
  }

  /**
   * Handle error with semantic processing
   */
  const handleError = (error: any, context?: string): ProcessedError => {
    const processedError = processError(error)
    
    // Store the processed error
    lastError.value = {
      success: false,
      message: processedError.message,
      error_code: processedError.type.toUpperCase(),
      ...processedError.details
    }

    // Handle validation errors specifically
    if (processedError.type === ERROR_TYPES.VALIDATION && processedError.details) {
      errors.value = processedError.details
    } else {
      clearErrors()
    }

    // Show toast notification if appropriate
    if (processedError.shouldShowToast) {
      showError('Error', processedError.userMessage)
    }

    // Log error for debugging
    console.error(`[${context || 'ErrorHandler'}] ${processedError.type}:`, {
      processedError,
      originalError: error
    })

    return processedError
  }

  /**
   * Handle authentication errors specifically
   */
  const handleAuthError = (error: any): void => {
    const processedError = handleError(error, 'Authentication')
    
    if (processedError.type === ERROR_TYPES.AUTHENTICATION) {
      // Redirect to login page or trigger logout
      window.location.href = '/login'
    }
  }

  /**
   * Handle form validation errors
   */
  const handleValidationError = (error: any): Record<string, string[]> => {
    const processedError = handleError(error, 'Validation')
    
    if (processedError.type === ERROR_TYPES.VALIDATION && processedError.details) {
      return processedError.details
    }
    
    return {}
  }

  /**
   * Retry mechanism for retryable errors
   */
  const createRetryHandler = (originalFunction: (...args: any[]) => Promise<any>, maxRetries = 3) => {
    return async (...args: any[]) => {
      let retries = 0
      
      while (retries < maxRetries) {
        try {
          return await originalFunction(...args)
        } catch (error) {
          const processedError = processError(error)
          
          if (!processedError.shouldRetry || retries === maxRetries - 1) {
            throw error
          }
          
          retries++
          
          // Exponential backoff
          const delay = Math.pow(2, retries) * 1000
          await new Promise(resolve => setTimeout(resolve, delay))
        }
      }
    }
  }

  /**
   * Get error message for a specific field
   */
  const getFieldError = (field: string): string | null => {
    const fieldErrors = errors.value[field]
    return fieldErrors && fieldErrors.length > 0 ? fieldErrors[0] : null
  }

  /**
   * Check if field has error
   */
  const hasFieldError = (field: string): boolean => {
    return !!errors.value[field] && errors.value[field].length > 0
  }

  /**
   * Get all error messages as a flat array
   */
  const getAllErrors = computed((): string[] => {
    return Object.values(errors.value).flat()
  })

  /**
   * Check if there are any errors
   */
  const hasErrors = computed((): boolean => {
    return Object.keys(errors.value).length > 0
  })

  /**
   * Get error count
   */
  const errorCount = computed((): number => {
    return Object.keys(errors.value).length
  })

  return {
    // State
    errors: computed(() => errors.value),
    isLoading: computed(() => isLoading.value),
    lastError: computed(() => lastError.value),
    hasErrors,
    errorCount,
    getAllErrors,

    // Error handling methods
    handleError,
    handleAuthError,
    handleValidationError,
    processError,

    // Field-specific methods
    getFieldError,
    hasFieldError,
    setFieldError,
    clearFieldError,
    clearErrors,

    // Utility methods
    createRetryHandler,

    // Constants
    ERROR_TYPES: ERROR_TYPES as Readonly<typeof ERROR_TYPES>
  }
} 