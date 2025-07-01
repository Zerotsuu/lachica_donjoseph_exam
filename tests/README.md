# Testing Architecture Guide

## 🎯 **Semantic Testing Structure**

This guide establishes consistent testing patterns across the Laravel-Vue application.

## 📁 **Directory Structure**

```
tests/
├── Feature/                    # Integration & end-to-end tests
│   ├── Auth/                   # Authentication flow tests
│   ├── Api/                    # API endpoint tests
│   ├── Admin/                  # Admin functionality tests
│   └── User/                   # User workflow tests
├── Unit/                       # Isolated unit tests
│   ├── Services/               # Service layer tests
│   ├── Models/                 # Eloquent model tests
│   └── Helpers/                # Utility function tests
└── Frontend/                   # Frontend component tests
    ├── Components/             # Vue component tests
    ├── Composables/            # Vue composable tests
    └── Pages/                  # Page integration tests
```

## 🔧 **Testing Patterns**

### **1. Service Layer Testing**
```php
// tests/Unit/Services/UserServiceTest.php
class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected UserService $userService;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }
    
    /** @test */
    public function it_creates_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'user'
        ];
        
        $result = $this->userService->create($userData);
        
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
    }
}
```

### **2. API Testing Patterns**
```php
// tests/Feature/Api/UserApiTest.php
class UserApiTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function authenticated_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user'
            ]);
            
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email', 'role']
            ]);
    }
}
```

### **3. Frontend Testing (Vitest + Vue Test Utils)**
```typescript
// tests/Frontend/Components/UserForm.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UserFormDialog from '@/components/users/UserFormDialog.vue'

describe('UserFormDialog', () => {
  it('validates required fields', async () => {
    const wrapper = mount(UserFormDialog, {
      props: {
        isOpen: true,
        mode: 'create'
      }
    })
    
    // Attempt to submit empty form
    await wrapper.find('form').trigger('submit')
    
    // Check for validation errors
    expect(wrapper.find('[data-testid="name-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="email-error"]').exists()).toBe(true)
  })
})
```

## 🏗️ **Test Factory Patterns**

### **Model Factories**
```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
    
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }
    
    public function withOrders(int $count = 3): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            Order::factory($count)->for($user)->create();
        });
    }
}
```

## 🎯 **Testing Commands**

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Frontend tests
npm run test              # Run all frontend tests
npm run test:unit         # Unit tests only
npm run test:component    # Component tests only
npm run test:watch        # Watch mode
```

## 📋 **Testing Checklist**

### **For New Features:**
- [ ] Unit tests for service methods
- [ ] API endpoint tests (success/error cases)
- [ ] Component tests for UI interactions
- [ ] Integration tests for complete workflows
- [ ] Error handling tests
- [ ] Permission/authorization tests

### **For Bug Fixes:**
- [ ] Test that reproduces the bug
- [ ] Test that verifies the fix
- [ ] Regression tests for related functionality

## 🔍 **Test Data Management**

### **Using Database Transactions**
```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    use DatabaseTransactions; // Automatically rolls back changes
    
    // Your tests here
}
```

### **Using RefreshDatabase**
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase; // Rebuilds database for each test
    
    // Your tests here
}
``` 