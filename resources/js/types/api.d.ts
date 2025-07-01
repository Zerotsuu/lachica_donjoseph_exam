/**
 * API Type Definitions
 * 
 * Comprehensive type safety for API interactions, responses, and data models.
 * Provides semantic typing for better development experience and error prevention.
 */

// Core Entity Types
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'user'
  email_verified_at: string | null
  created_at: string
  updated_at: string
  last_activity?: string
  failed_login_attempts?: number
  account_locked_until?: string | null
}

export interface Product {
  id: number
  name: string
  description: string | null
  price: number
  stocks: number
  image: string | null
  created_at: string
  updated_at: string
}

export interface Order {
  id: number
  user_id: number
  customer_name: string
  product_id: number
  product_name: string
  quantity: number
  total_amount: number
  status: OrderStatus
  created_at: string
  updated_at: string
  product?: Product
  user?: User
}

export interface OrderItem {
  id: number
  order_id: number
  product_id: number
  quantity: number
  price: number
  product?: Product
}

export interface Cart {
  id: number
  user_id: number
  created_at: string
  updated_at: string
  items: CartItem[]
}

export interface CartItem {
  id: number
  cart_id: number
  product_id: number
  quantity: number
  created_at: string
  updated_at: string
  product?: Product
}

// Enum Types for Better Type Safety
export type OrderStatus = 'Pending' | 'For Delivery' | 'Delivered' | 'Cancelled'
export type UserRole = 'admin' | 'user'

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean
  message?: string
  data?: T
  error_code?: string
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

// Form Data Types
export interface LoginFormData {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterFormData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface UserFormData {
  name: string
  email: string
  password?: string
  password_confirmation?: string
  role?: UserRole
}

export interface ProductFormData {
  name: string
  description?: string
  price: number
  stocks: number
  image?: File | string | null
}

export interface OrderFormData {
  customer_name: string
  product_id: number
  quantity: number
  status: OrderStatus
}

// Error Handling Types
export interface ValidationError {
  message: string
  errors: Record<string, string[]>
}

export interface ApiError {
  success: false
  message: string
  error_code?: string
  errors?: Record<string, string[]>
  debug?: {
    exception: string
    message: string
    file: string
    line: number
  }
}

// Authentication Types
export interface AuthUser {
  user: User | null
}

export interface AuthResponse extends ApiResponse<User> {
  token?: string
  expires_at?: string
}

// CRUD Operation Types
export interface CrudOperations<T, CreateData = Partial<T>, UpdateData = Partial<T>> {
  getAll: (filters?: Record<string, any>) => Promise<ApiResponse<T[]>>
  getById: (id: number) => Promise<ApiResponse<T>>
  create: (data: CreateData) => Promise<ApiResponse<T>>
  update: (id: number, data: UpdateData) => Promise<ApiResponse<T>>
  delete: (id: number) => Promise<ApiResponse<null>>
}

// Table/DataTable Types
export interface TableColumn<T = any> {
  key: keyof T | string
  label: string
  sortable?: boolean
  searchable?: boolean
  type?: 'text' | 'number' | 'date' | 'status' | 'currency' | 'image'
  width?: string
  align?: 'left' | 'center' | 'right'
  render?: (value: any, row: T) => string | number
}

export interface TablePagination {
  page: number
  perPage: number
  total: number
  totalPages: number
}

export interface TableState<T = any> {
  data: T[]
  loading: boolean
  error: string | null
  pagination: TablePagination
  sorting: {
    column: string | null
    direction: 'asc' | 'desc'
  }
  filters: Record<string, any>
}

// Modal/Dialog Types
export type ModalMode = 'create' | 'edit' | 'view' | 'delete'

export interface ModalState<T = any> {
  isOpen: boolean
  mode: ModalMode
  data: T | null
  loading: boolean
}

// Form Field Types
export interface FormField {
  name: string
  label: string
  type: 'text' | 'email' | 'password' | 'number' | 'textarea' | 'select' | 'file' | 'checkbox'
  placeholder?: string
  required?: boolean
  disabled?: boolean
  options?: Array<{ value: string | number; label: string }>
  validation?: string[]
}

// Component Prop Types
export interface ComponentBaseProps {
  class?: string
  style?: Record<string, string>
}

export interface LoadingProps extends ComponentBaseProps {
  size?: 'sm' | 'md' | 'lg'
  text?: string
}

export interface EmptyStateProps extends ComponentBaseProps {
  title: string
  description?: string
  icon?: string
  actionText?: string
  onAction?: () => void
}

// Toast/Notification Types
export interface ToastMessage {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  description?: string
  duration?: number
  persistent?: boolean
}

// Navigation Types
export interface NavItem {
  label: string
  href?: string
  icon?: string
  badge?: string | number
  children?: NavItem[]
  active?: boolean
  external?: boolean
}

// Settings/Profile Types
export interface ProfileFormData {
  name: string
  email: string
}

export interface PasswordFormData {
  current_password: string
  password: string
  password_confirmation: string
}

// Dashboard/Analytics Types
export interface DashboardStats {
  total_users: number
  total_products: number
  total_orders: number
  total_revenue: number
  recent_orders: Order[]
  popular_products: Product[]
}

// Search and Filter Types
export interface SearchFilters {
  query?: string
  status?: string[]
  dateRange?: {
    start: string
    end: string
  }
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
}

// File Upload Types
export interface FileUploadOptions {
  accept?: string
  maxSize?: number
  multiple?: boolean
  preview?: boolean
}

export interface UploadedFile {
  file: File
  preview?: string
  progress?: number
  uploaded?: boolean
  error?: string
}

// Utility Types
export type DeepPartial<T> = {
  [P in keyof T]?: T[P] extends object ? DeepPartial<T[P]> : T[P]
}

export type OmitId<T> = Omit<T, 'id' | 'created_at' | 'updated_at'>

export type CreatePayload<T> = OmitId<T>

export type UpdatePayload<T> = Partial<OmitId<T>>

// HTTP Method Types
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

// Route Types
export interface RouteParams {
  [key: string]: string | number
}

export interface QueryParams {
  [key: string]: string | number | boolean | string[] | number[]
} 