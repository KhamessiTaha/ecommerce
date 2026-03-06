# 🔐 Authentication System Setup Guide

## Overview

We just added complete authentication to Saroukh TN with:
- User registration & login
- Role-based access control (RBAC)
- Protected routes (cart and admin panel)
- User profile page
- Demo user seeding

## Step 1: Create User Table in Database

Run this SQL command in your MySQL database (via phpMyAdmin or MySQL CLI):

```sql
CREATE TABLE `user` (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    UNIQUE INDEX UNIQ_EMAIL (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Or run the migration via command line:
```bash
php bin/console doctrine:migrations:migrate
```

## Step 2: Seed Demo Users

Load the user fixtures to create demo accounts:

```bash
php bin/console doctrine:fixtures:load
```

This creates:
| Email | Password | Role |
|-------|----------|------|
| admin@saroukh.tn | password123 | Admin |
| user@saroukh.tn | password123 | Customer |
| jane@saroukh.tn | password123 | Customer |

## Step 3: Clear Cache

Always clear cache after authentication changes:

```bash
php bin/console cache:clear
```

## Step 4: Test the System

1. **Register a new user:**
   - Go to `/register`
   - Fill in full name, email, password
   - Click "Create Account"
   - Should redirect to login

2. **Login as Customer:**
   - Go to `/login`
   - Email: `user@saroukh.tn`
   - Password: `password123`
   - Click "Login"
   - Should redirect to products page
   - Cart link should now be visible
   - Should be able to add products to cart

3. **Access Cart:**
   - Only logged-in users can access `/cart`
   - If not logged in, redirects to `/login`

4. **Admin Access:**
   - Login with `admin@saroukh.tn` / `password123`
   - Admin link appears in navbar (⚙️ Admin)
   - Can access `/admin/products`
   - Can create/edit/delete products

5. **Non-Admin Cart:**
   - Regular users cannot access `/admin/products`
   - Get access denied error

## Architecture

### New Files Created

**Controllers:**
- `src/Controller/AuthController.php` - Login, logout, register actions
- `src/Controller/UserController.php` - User profile page

**Entities:**
- `src/Entity/User.php` - User model with roles and password

**Repositories:**
- `src/Repository/UserRepository.php` - User database queries

**Forms:**
- `src/Form/RegisterFormType.php` - Registration form with validation

**Fixtures:**
- `src/DataFixtures/UserFixtures.php` - Demo user seeding

**Templates:**
- `templates/auth/login.html.twig` - Login page
- `templates/auth/register.html.twig` - Registration page
- `templates/user/profile.html.twig` - User profile page

**Configuration:**
- `config/packages/security.yaml` - Updated with User provider & firewall

**Database:**
- `migrations/Version20260306120000.php` - User table migration

### How It Works

```
User visits /login
    ↓
AuthController::login() loads login template
    ↓
User submits email/password form
    ↓
Symfony Security checks credentials
    ↓
UserRepository finds user by email
    ↓
PasswordHasher verifies password
    ↓
If valid: Session created, user logged in
If invalid: Error message shown, try again
    ↓
Redirects to /products
    ↓
User now sees navbar with:
    - Their full name
    - Cart access (ROLE_USER only)
    - Admin link if ROLE_ADMIN
    - Logout button
```

### Route Protection

**Public routes (anyone):**
```
GET  /                    (home)
GET  /products            (product list)
GET  /products/{id}       (product detail)
GET  /api/products        (API)
GET  /login               (login page)
POST /login               (login submission)
GET  /register            (register page)
POST /register            (register submission)
GET  /logout              (logout - handled by security)
```

**Protected routes (ROLE_USER only):**
```
GET  /cart                (view cart)
POST /cart/add/{id}       (add to cart)
POST /cart/update/{id}    (update quantity)
POST /cart/remove/{id}    (remove item)
POST /cart/clear          (clear cart)
GET  /user/profile        (my profile)
```

**Protected routes (ROLE_ADMIN only):**
```
GET    /admin/products              (list products)
GET    /admin/products/new          (create product form)
POST   /admin/products/new          (create product)
GET    /admin/products/{id}/edit    (edit product form)
POST   /admin/products/{id}/edit    (save product)
GET    /admin/products/{id}/delete  (delete confirmation)
POST   /admin/products/{id}/delete  (delete product)
```

### Key Symfony Concepts Used

**1. Password Hashing:**
```php
$hashedPassword = $passwordHasher->hashPassword($user, 'plainPassword');
$user->setPassword($hashedPassword);
// Passwords never stored in plain text!
```

**2. Form Security:**
```twig
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```
Prevents CSRF (Cross-Site Request Forgery) attacks.

**3. Role-Based Access Control:**
```php
#[IsGranted('ROLE_ADMIN')]  // Only admins can access
class AdminProductController extends AbstractController
```

**4. Template User Access:**
```twig
{% if app.user %}
    Welcome, {{ app.user.fullName }}!
    {% if app.user.isAdmin() %}
        <a href="/admin">Admin Panel</a>
    {% endif %}
{% else %}
    <a href="/login">Login</a>
{% endif %}
```

**5. Entity User Provider:**
```yaml
# config/packages/security.yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
            property: email  # Use email to find users
```

## Security Best Practices Implemented

✅ **Passwords hashed** - Using bcrypt/argon2  
✅ **CSRF protection** - Hidden token in forms  
✅ **SQL injection safe** - Doctrine ORM binds parameters  
✅ **Role-based access** - Routes protected by role  
✅ **Session management** - Symfony handles HTTP only cookies  
✅ **Password validation** - Minimum 6 characters  
✅ **Email uniqueness** - Checked at registration  

## Troubleshooting

### "Access Denied" Error

This means you don't have the required role. For example:
- Tried to access `/admin/products` without ROLE_ADMIN
- Tried to access `/cart` without ROLE_USER

**Solution:** Login as a user with the correct role.

### "User Not Found" on Login

Email doesn't exist in database.

**Solution:** Register first at `/register`, or use demo credentials.

### Password Verification Fails

Wrong password or the user account doesn't exist.

**Solution:** Check email/password, or reset by recreating the user.

### CSRF Token Invalid

Form sent without the hidden CSRF token.

**Solution:** Never use `<form>` without including `{{ csrf_token() }}`.

## Admin Features

Admins can:
- View all products in a table
- Create new products
- Edit existing products
- Delete products
- See all current users (future feature)
- Manage inventory (future feature)

## Customer Features

Customers can:
- Browse all products
- Search and filter products
- View product recommendations
- Add products to cart
- View cart and adjust quantities
- View their profile
- Logout

## Next Steps (Optional Features)

1. **Forgot Password** - Allow users to reset via email
2. **User Management** - Admin can manage users/roles
3. **Order History** - Track past purchases
4. **Wishlist** - Save favorite products
5. **Email Verification** - Confirm email on registration
6. **Two-Factor Auth** - Extra security layer

---

**Now your Saroukh TN ecommerce app is secure! 🔐**
