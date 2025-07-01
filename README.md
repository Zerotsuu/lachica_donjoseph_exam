# E-Commerce Site Exam

A modern full-stack e-commerce application built with Laravel 11, Vue 3, TypeScript, and Inertia.js. Features a complete product management system with cart functionality, user authentication, and admin dashboard.

![Laravel](https://img.shields.io/badge/Laravel-11-red?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3-green?style=flat-square&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5-blue?style=flat-square&logo=typescript)
![Inertia.js](https://img.shields.io/badge/Inertia.js-SPA-purple?style=flat-square)

Routing Flow :
![image](https://github.com/user-attachments/assets/94edea7c-fd77-4b0f-91fa-d270c52117f0)



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
│   ├── Http/                 # HTTP layer
│   │   ├── Controllers/      # Request controllers
│   │   ├── Middleware/       # Custom middleware
│   │   ├── Resources/        # API resources
│   │   └── Traits/           # Reusable traits
│   ├── Models/               # Eloquent models
│   └── Services/             # Business logic services
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── js/                  # Vue.js frontend application
│   │   ├── components/      # Reusable Vue components
│   │   ├── pages/          # Inertia page components
│   │   ├── layouts/        # Layout components
│   │   └── composables/    # Vue composables
│   └── css/                # Stylesheets
├── routes/                 # Laravel route definitions
├── public/                 # Public assets
└── tests/                  # PHP tests
```