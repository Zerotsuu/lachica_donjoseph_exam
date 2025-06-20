# E-Commerce Site Exam

A modern full-stack e-commerce application built with Laravel 11, Vue 3, TypeScript, and Inertia.js. Features a complete product management system with cart functionality, user authentication, and admin dashboard.

![Laravel](https://img.shields.io/badge/Laravel-11-red?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3-green?style=flat-square&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5-blue?style=flat-square&logo=typescript)
![Inertia.js](https://img.shields.io/badge/Inertia.js-SPA-purple?style=flat-square)

## ✨ Features

### 🛒 **E-Commerce Core**
- ✅ **Product Catalog** - Browse and search products with pagination
- ✅ **Shopping Cart** - Add, update, remove items with real-time updates
- ✅ **Order Management** - Place orders and track order history
- ✅ **Image Upload** - Product image management with file validation
- ✅ **Stock Management** - Real-time inventory tracking

### 👤 **User Management**
- ✅ **Authentication** - Register, login, email verification
- ✅ **Authorization** - Role-based access control (Admin/User)
- ✅ **Profile Management** - Update profile and password
- ✅ **Password Security** - Secure password reset functionality
- ✅ **Account Lockout** - Protection against brute force attacks

### 🎛️ **Admin Dashboard**
- ✅ **Product CRUD** - Complete product management
- ✅ **Order Management** - View and update order statuses
- ✅ **User Management** - Admin user control panel
- ✅ **Analytics Dashboard** - Overview of products, orders, and users
- ✅ **Responsive Design** - Mobile-friendly admin interface

### 🎨 **UI/UX Features**
- ✅ **Modern Design** - Clean, professional interface
- ✅ **Dark/Light Mode** - Theme switching capability
- ✅ **Responsive Layout** - Mobile-first design approach
- ✅ **Real-time Toasts** - User feedback notifications
- ✅ **Loading States** - Smooth user experience indicators

## 🛠️ Tech Stack

### **Backend**
- **Laravel 11** - PHP framework with latest features
- **MySQL** - Relational database management
- **Laravel Sanctum** - API authentication
- **Spatie Permissions** - Role and permission management

### **Frontend**
- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type-safe JavaScript development
- **Inertia.js** - Modern monolith SPA approach
- **Tailwind CSS** - Utility-first CSS framework
- **Headless UI** - Unstyled accessible UI components
- **Lucide Icons** - Beautiful & consistent icon set

### **Development Tools**
- **Vite** - Fast build tool and dev server
- **ESLint** - Code linting and formatting
- **Ziggy** - Laravel route generation for JavaScript
- **Pest** - Testing framework for PHP


## 🔗 API Endpoints
### **Authentication**
- `POST /register` - User registration
- `POST /login` - User login
- `POST /logout` - User logout
- `POST /forgot-password` - Password reset request
- `POST /reset-password` - Password reset confirmation

### **Cart Management**
- `GET /cart` - Get user's cart
- `POST /cart/add` - Add item to cart
- `PUT /cart/items/{item}` - Update cart item quantity
- `DELETE /cart/items/{item}` - Remove item from cart
- `POST /cart/place-order` - Place order from cart

### **Admin Routes** (Protected)
- `GET /admin/dashboard/*` - Admin dashboard views
- `Resource /admin/products` - Product CRUD operations
- `Resource /admin/orders` - Order management
- `Resource /admin/users` - User management

## 📁 File Structure

```
laravel-vue-setup/
├── app/                      # Laravel application logic
│   ├── Http/Controllers/     # Request controllers
│   │   ├── Http/Controllers/     # Request controllers
│   │   ├── Models/              # Eloquent models
│   │   └── Middleware/          # Custom middleware
│   ├── database/
│   │   ├── migrations/          # Database migrations
│   │   └── seeders/            # Database seeders
│   ├── resources/
│   │   ├── js/                 # Vue.js frontend application
│   │   │   ├── components/     # Reusable Vue components
│   │   │   ├── pages/         # Inertia page components
│   │   │   ├── layouts/       # Layout components
│   │   │   └── composables/   # Vue composables
│   │   └── css/               # Stylesheets
│   ├── routes/                # Laravel route definitions
│   └── public/               # Public assets
└── tests/               # PHP tests
```

## 🔐 Security Features

- **CSRF Protection** - All forms protected against CSRF attacks
- **SQL Injection Prevention** - Eloquent ORM with parameter binding
- **XSS Protection** - Output escaping and content sanitization
- **Password Hashing** - Bcrypt password encryption
- **Rate Limiting** - API and login attempt rate limiting
- **Email Verification** - Account verification system
- **Role-Based Access** - Admin/User role separation

## 🐛 Known Issues & Solutions

### **Cart Functionality After Login**
- ✅ **FIXED** - Attempted to implement CSRF token handling with Inertia.js router
- Cart now works without page refresh after login but still clunky

### **Route Management**
- Routes are still exposed. Need to minimize route exposure with ziggy

### **Testing Strategy [TODO]**
- Feature tests for API endpoints
- Component testing for Vue components
- Integration tests for user flows

# Laravel Vue Product Management System
[X] Landing Store Page
[X] Login / Register Page
[X] Admin Dashboard (Product Management Page)
[X] Admin Sidebar
[X] Admin Dashboard Modals
[X] basic CRUD functionality
[X] Login / Register Functionality
[X] Product rendering in Landing store page
[X] Upload images functionality
[X] Cart Functionality
[X] connect user_id to cart_id
[X] Place Order Functionality
[X] establish Cart to Place Order database relationship

---

# to do
[] bug** after logging in, user is unable to add items to cart. needs manual refresh of page. optimize code for this.
[] remove inline scripts
[] minimize route exposure with ziggy