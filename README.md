# E-Commerce Site Exam

A modern full-stack e-commerce application built with Laravel 11, Vue 3, TypeScript, and Inertia.js. Features a complete product management system with cart functionality, user authentication, and admin dashboard.

![Laravel](https://img.shields.io/badge/Laravel-11-red?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3-green?style=flat-square&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5-blue?style=flat-square&logo=typescript)
![Inertia.js](https://img.shields.io/badge/Inertia.js-SPA-purple?style=flat-square)


### **[TODO] / MISSING FEATURES**
- Feature tests for API endpoints
- Component testing for Vue components
- Integration tests for user flows
- Email user verification
- Forget password verification
- bug** after logging in, user is unable to add items to cart. needs manual refresh of page. optimize code for this.
- remove inline scripts
- all of backend / frontend testing
- Product stock validation
- API's for frontend listings (except cart.)

## ✨ Features
### 🛒 **E-Commerce Core**
- ✅ **Product Catalog** - Browse and search products with pagination
- ✅ **Shopping Cart** - Add, update, remove items with real-time updates
- ✅ **Order Management** - Place orders and track order history
- ✅ **Image Upload** - Product image management with file validation
- ✅ **Stock Management** - Real-time inventory tracking

### 👤 **User Management**
- ✅ **Authentication** - Register, login
- ✅ **Authorization** - Role-based access control (Admin/User)
- ✅ **Profile Management** - Update profile and password
- ✅ **Account Lockout**

### 🎛️ **Admin Dashboard**
- ✅ **Product CRUD** - Complete product management
- ✅ **Order Management** - View and update order statuses
- ✅ **User Management** - Admin user control panel

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
- `POST /register` - User registration (modified from starter-kit)
- `POST /login` - User login (modified from starter-kit)
- `POST /logout` - User logout (modified from starter-kit)
- `POST /forgot-password` - Password reset request (untested - from starter-kit)
- `POST /reset-password` - Password reset confirmation (untested - from starter-kit)

### **Cart Management**
- `GET /cart` - Get user's cart
- `POST /cart/add` - Add item to cart
- `PUT /cart/items/{item}` - Update cart item quantity
- `DELETE /cart/items/{item}` - Remove item from cart
- `POST /cart/place-order` - Place order from cart

### **Admin Routes** (Protected)
- `GET /admin/dashboard/*` - Admin dashboard views (modified from starter-kit)
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



## 🐛 Known Issues & Solutions

### **Cart Functionality After Login**
- ✅ **FIXED** - Attempted to implement CSRF token handling with Inertia.js router
- Cart now works without page refresh after login but still clunky

### **Route Management**
- Routes are still exposed. Need to minimize route exposure with ziggy


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

# Laravel Vue Setup with Sanctum Authentication

A Laravel application with Vue.js frontend using Laravel 12 Sanctum for authentication.

## Features

### Laravel 12 Sanctum Authentication
- **Hybrid Authentication**: Web sessions for browser + API tokens for programmatic access
- **Token Expiration**: Configurable token expiration times with automatic pruning
- **Ability-Based Authorization**: Fine-grained permissions using Laravel 12 ability middleware
- **Enhanced Security**: Account lockout protection, CSRF protection, XSS prevention
- **Role-Based Access**: Admin and user roles with different access levels

### Authentication Features
- **Web Authentication**: Traditional session-based login for browsers
- **API Authentication**: Bearer token authentication for API clients
- **Token Management**: Create, revoke, and manage API tokens
- **Session Tracking**: User activity monitoring and session management
- **Account Security**: Failed login attempt tracking and account lockout

### Laravel 12 Sanctum Enhancements
- **New Ability Middleware**: `abilities` and `ability` middleware for granular permissions
- **Token Expiration**: Independent token expiration times per token
- **Automatic Pruning**: Scheduled task to clean up expired tokens
- **Enhanced Configuration**: Dynamic stateful domain configuration
- **Improved Error Handling**: Better error messages for permission issues

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy environment configuration:
   ```bash
   cp .env.example .env
   ```

4. Configure Laravel 12 Sanctum settings in `.env`:
   ```env
   # Sanctum Configuration
   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
   SANCTUM_EXPIRATION_MINUTES=525600  # 1 year default
   AUTH_GUARD=sanctum
   ```

5. Generate application key and run migrations:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

6. Install Sanctum (Laravel 12):
   ```bash
   php artisan install:api
   ```

7. Build frontend:
   ```bash
   npm run build
   ```

## Laravel 12 Sanctum Features

### Token Abilities
Tokens can be assigned specific abilities for fine-grained access control:

```php
// Create token with specific abilities
$token = $user->createToken('admin-token', ['admin:read', 'admin:write'], now()->addDays(7));

// Check abilities in controllers
if ($user->tokenCan('admin:write')) {
    // User can perform write operations
}
```

### Ability Middleware
Routes can be protected with specific ability requirements:

```php
// Require specific ability
Route::get('/orders', OrderController::class)->middleware('ability:admin:read');

// Require multiple abilities
Route::post('/orders', OrderController::class)->middleware('abilities:admin:read,admin:write');
```

### Token Expiration
Configure global or per-token expiration:

```php
// Global expiration in config/sanctum.php
'expiration' => 525600, // 1 year in minutes

// Per-token expiration
$token = $user->createToken('api-token', ['*'], now()->addDays(30));
```

### Automatic Token Pruning
Clean up expired tokens automatically:

```bash
# Manual pruning
php artisan sanctum:prune-expired --hours=24

# Scheduled pruning (configured in routes/console.php)
Schedule::command('sanctum:prune-expired --hours=24')->daily();
```

## API Usage

### Authentication
```javascript
// Login
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});

// Use token
const data = await fetch('/api/admin/orders', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Frontend Integration
The application includes a Vue.js composable with Laravel 12 features:

```javascript
import { useSanctumApi } from '@/composables/useSanctumApi'

const { 
  login, 
  logout, 
  isAuthenticated, 
  tokenExpiresIn,
  getOrders 
} = useSanctumApi()

// Enhanced token management
console.log(`Token expires in ${tokenExpiresIn.value} minutes`)
```

## Security Features

### Account Lockout
- Failed login attempts are tracked
- Accounts are temporarily locked after multiple failures
- Automatic unlock after specified time period

### Token Security
- SHA-256 hashing for stored tokens
- Configurable token prefixes for security scanning
- Automatic token revocation on logout
- Expired token cleanup

### Permission System
- Role-based access control (Admin, User)
- Token ability-based permissions
- Route-level protection with middleware
- API endpoint access control

## Configuration

### Sanctum Configuration (`config/sanctum.php`)
```php
// Stateful domains for SPA authentication
'stateful' => [/* domains */],

// Token expiration (minutes)
'expiration' => 525600,

// Authentication guards
'guard' => ['web'],
```

### Middleware Configuration (`bootstrap/app.php`)
```php
$middleware->alias([
    'abilities' => CheckAbilities::class,
    'ability' => CheckForAnyAbility::class,
    'sanctum.admin' => SanctumAdminMiddleware::class,
]);
```

## Development

### Frontend Development
```bash
npm run dev
```

### Backend Development
```bash
php artisan serve
```

### Running Tests
```bash
php artisan test
```

## Laravel 12 Migration Notes

This application has been updated to use Laravel 12 Sanctum features:

1. **New Middleware**: Added `abilities` and `ability` middleware
2. **Token Expiration**: Implemented configurable token expiration
3. **Enhanced Config**: Using dynamic domain configuration helpers
4. **Automatic Pruning**: Added scheduled token cleanup
5. **Improved Frontend**: Enhanced Vue composable with expiration tracking

The implementation maintains backward compatibility while adding new Laravel 12 capabilities.
