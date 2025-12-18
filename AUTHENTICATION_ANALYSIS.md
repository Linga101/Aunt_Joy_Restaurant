# Authentication System Analysis - Aunt Joy's Restaurant

## 📋 Overview
This document provides a comprehensive analysis of the login/authentication system, all protected routes, and entry points for user registration and login.

---

## 🔐 Authentication Architecture

### Core Authentication Flow

```
User Request
    ↓
[config/db.php]
    ├─ initSession() - Initialize PHP session
    ├─ isLoggedIn() - Check if user has active session
    ├─ hasRole(...roles) - Verify user role
    ├─ requireAuth() - Enforce authentication (redirect if not logged in)
    └─ requireRole(...roles) - Enforce role-based access
    ↓
[Session Variables Set Upon Login]
    ├─ $_SESSION['user_id'] - Unique user identifier
    ├─ $_SESSION['username'] - Username
    ├─ $_SESSION['email'] - User email
    ├─ $_SESSION['full_name'] - Full name
    ├─ $_SESSION['role_id'] - Role ID (1-4)
    └─ $_SESSION['role_name'] - Role name (Customer|Administrator|Sales Personnel|Manager)
```

### Database Role System (roles table)

| role_id | role_name | Purpose |
|---------|-----------|---------|
| 1 | Customer | Browse menu, place orders, view own orders |
| 2 | Administrator | Manage users, meals, all system functions |
| 3 | Sales Personnel | View orders, update order status |
| 4 | Manager | View reports, analytics, export data |

---

## 🔑 User Roles & Permissions

### 1. **Customer**
- **Registration**: Public (anyone can register)
- **Access**: Browse menu, place orders, view own orders
- **Protected Pages**:
  - `/views/customer/menu.php` (view only, no auth required)
  - `/views/customer/cart.php` (cart operations require authentication)
  - `/views/customer/orders.php` (view own orders)
- **Restrictions**: Cart access guarded with login redirect

### 2. **Administrator** (Super Admin)
- **Access**: All system functions including user management
- **Protected Pages**:
  - `/views/admin/dashboard.php` → `requireAuth()` + `requireRole('Administrator')`
  - `/views/admin/users.php` → `requireAuth()` + `requireRole('Administrator')`
  - `/views/admin/meals.php` → `requireAuth()` + `requireRole('Administrator')`
- **Controllers**:
  - `controllers/admin/save_user.php` → `isLoggedIn()` + `hasRole('Administrator')`
  - `controllers/admin/delete_user.php`
  - `controllers/admin/get_users.php`
  - `controllers/admin/save_meal.php`
  - `controllers/admin/delete_meal.php`

### 3. **Sales Personnel**
- **Access**: Order management (view and update order status)
- **Protected Pages**:
  - `/views/sales/dashboard.php` → `requireAuth()` + `requireRole('Sales Personnel', 'Administrator')`
- **Controllers**:
  - `controllers/sales/get_orders.php` → `isLoggedIn()` + `hasRole('Sales Personnel', 'Administrator')`
  - `controllers/sales/update_status.php`

### 4. **Manager**
- **Access**: Reports and analytics
- **Protected Pages**:
  - `/views/manager/dashboard.php` → `requireAuth()` + `requireRole('Manager', 'Administrator')`
- **Controllers**:
  - `controllers/manager/get_report.php` → `isLoggedIn()` + `hasRole('Manager', 'Administrator')`
  - `controllers/manager/export_pdf.php`
  - `controllers/manager/export_excel.php`

### 5. **Any Authenticated User**
- **Profile Access**:
  - `/views/auth/profile.php` → `requireAuth()` (no specific role required)

---

## 📍 Entry Points for Login/Register

### 1. **Landing Page** (`/index.php`)
- **Purpose**: Public home page with modal authentication
- **Login Entry**:
  - Button: "Sign In" (opens login modal)
  - Modal ID: `#loginModal`
  - Function: `openAuthModal('login')`
- **Register Entry**:
  - Button: "Partner with us" (opens register modal)
  - Modal ID: `#registerModal`
  - Function: `openAuthModal('register')`

### 2. **Dedicated Pages** (`/views/auth/`)

#### Login Page
- **File**: `/views/auth/login.php`
- **Route**: `/aunt_joy/views/auth/login.php`
- **Access**: Public (redirects to dashboard if already logged in)
- **Redirect Rules**:
  ```
  If logged in already:
    - Customer → /aunt_joy/views/customer/menu.php
    - Administrator → /aunt_joy/views/admin/dashboard.php
    - Sales Personnel → /aunt_joy/views/sales/dashboard.php
    - Manager → /aunt_joy/views/manager/dashboard.php
  ```
- **Features**:
  - Login with username or email
  - Remember me checkbox
  - Forgot password link (UI placeholder)
  - Sign up link to register page
  - Alert container for error/success messages

#### Register Page
- **File**: `/views/auth/register.php`
- **Route**: `/aunt_joy/views/auth/register.php`
- **Access**: Public (redirects to menu if already logged in)
- **Form Fields**:
  - Full Name (required)
  - Username (required, unique)
  - Email (required, unique)
  - Phone Number (optional)
  - Password (required, min 6 chars)
  - Confirm Password (required)
  - Terms & Conditions (checkbox)
- **On Success**: Redirects to `/views/customer/menu.php`

#### Profile/Settings Page
- **File**: `/views/auth/profile.php`
- **Route**: `/aunt_joy/views/auth/profile.php`
- **Access**: Authenticated users only (`requireAuth()`)
- **Role-Independent**: Works for all roles

---

## 🔗 API Routes (Controllers)

### Authentication Controllers
All located in `/controllers/auth/`

#### POST `/controllers/auth/login.php`
```
Request:
{
  "username": "string (username or email)",
  "password": "string"
}

Response Success:
{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "admin",
    "email": "admin@test.com",
    "full_name": "Admin User",
    "role_id": 2,
    "role_name": "Administrator"
  },
  "message": "Login successful"
}

Response Error:
{
  "success": false,
  "data": null,
  "message": "Invalid username or password"
}
```

**Validation Rules**:
- Username/email required
- Password required
- User must be active (`is_active = 1`)
- Password verified using `verifyPassword()` function

#### POST `/controllers/auth/register.php`
```
Request:
{
  "full_name": "string",
  "username": "string (unique)",
  "email": "string (unique)",
  "phone_number": "string (optional)",
  "password": "string"
}

Response Success:
{
  "success": true,
  "data": {
    "user_id": 5,
    "username": "newuser",
    "email": "user@example.com"
  },
  "message": "Registration successful"
}

Response Error:
{
  "success": false,
  "data": null,
  "message": "Username or email already exists"
}
```

**Validation Rules**:
- All fields required
- Email must be valid format
- Username unique in database
- Email unique in database
- Password minimum 6 characters
- Default role: 1 (Customer)

#### GET/POST `/controllers/auth/logout.php`
```
Process:
- Destroys session
- Clears all session variables
- Sets logout_message flash message
- Redirects to login page

Response: Page redirect to /views/auth/login.php
```

---

## 🛡️ Protected Routes Map

### Public Pages (No Authentication Required)
```
/index.php                              (Home/Landing - Modal Auth Available)
/views/auth/login.php                   (Login Page)
/views/auth/register.php                (Register Page)
/views/customer/menu.php                (Browse Menu - Cart restricted)
```

### Protected Pages - All Authenticated Users
```
/views/auth/profile.php                 (requireAuth() only)
```

### Protected Pages - Customer Only
```
/views/customer/cart.php                (Cart management)
/views/customer/orders.php              (View own orders)
```
**Note**: Customer role restriction enforced via JavaScript guards in cart.js

### Protected Pages - Administrator Only
```
/views/admin/dashboard.php              (Admin dashboard)
/views/admin/users.php                  (User management)
/views/admin/meals.php                  (Meal management)
```
**All require**: `requireAuth()` + `requireRole('Administrator')`

### Protected Pages - Sales Personnel (+ Admin)
```
/views/sales/dashboard.php              (Order management)
```
**Requires**: `requireAuth()` + `requireRole('Sales Personnel', 'Administrator')`

### Protected Pages - Manager (+ Admin)
```
/views/manager/dashboard.php            (Reports & Analytics)
```
**Requires**: `requireAuth()` + `requireRole('Manager', 'Administrator')`

---

## 📡 API Endpoints (Require Authentication)

### Admin Controllers (`requireAuth()` + `hasRole('Administrator')`)
```
POST   /controllers/admin/save_user.php
POST   /controllers/admin/delete_user.php
GET    /controllers/admin/get_users.php
POST   /controllers/admin/save_meal.php
POST   /controllers/admin/delete_meal.php
```

### Sales Controllers (`requireAuth()` + `hasRole('Sales Personnel', 'Administrator')`)
```
GET    /controllers/sales/get_orders.php
POST   /controllers/sales/update_status.php
```

### Customer Controllers
```
GET    /controllers/customer/get_meals.php
GET    /controllers/customer/get_orders.php
POST   /controllers/customer/place_order.php
```

### Manager Controllers (`requireAuth()` + `hasRole('Manager', 'Administrator')`)
```
GET    /controllers/manager/get_report.php
GET    /controllers/manager/export_pdf.php
GET    /controllers/manager/export_excel.php
```

---

## 🔄 Client-Side Authentication Flow

### Login Flow (Modal or Dedicated Page)

**1. User submits login form**
```javascript
// assets/js/auth.js - handleLogin()
POST /aunt_joy/controllers/auth/login.php
{
  "username": "...",
  "password": "..."
}
```

**2. Server validates & creates session**
```php
// controllers/auth/login.php
- Finds user by username OR email
- Verifies password hash
- Sets $_SESSION variables
- Updates last_login timestamp
- Returns user data (without password)
```

**3. Client redirects based on role**
```javascript
// Redirect mapping:
{
  'Customer': '/aunt_joy/views/customer/menu.php',
  'Administrator': '/aunt_joy/views/admin/dashboard.php',
  'Sales Personnel': '/aunt_joy/views/sales/dashboard.php',
  'Manager': '/aunt_joy/views/manager/dashboard.php'
}
```

### Register Flow

**1. User submits registration form**
```javascript
// assets/js/auth.js - handleRegister()
POST /aunt_joy/controllers/auth/register.php
{
  "full_name": "...",
  "username": "...",
  "email": "...",
  "phone_number": "...",
  "password": "..."
}
```

**2. Server validates & creates account**
```php
// controllers/auth/register.php
- Validates all inputs
- Checks duplicate username/email
- Creates new user with role_id = 1 (Customer)
- Auto-logs in user (sets session)
- Returns success message
```

**3. Client redirects to menu**
```javascript
// Automatic redirect to:
/aunt_joy/views/customer/menu.php (after 600ms delay)
```

### Logout Flow

**1. User clicks logout**
```
Navigation sidebar → Logout button
or
/controllers/auth/logout.php
```

**2. Server terminates session**
```php
// controllers/auth/logout.php
- session_unset() - Clear all variables
- session_destroy() - Destroy session
- Set logout_message flash
- Redirect to login page
```

**3. User returned to login**
```
Browser redirected to: /aunt_joy/views/auth/login.php
```

---

## 🎯 Modal Authentication System

### Modal Structure (`/index.php`)

#### Login Modal
```html
<div id="loginModal" class="modal">
  <div class="modal-content modal-auth">
    <span class="modal-close" onclick="closeLoginModal()">&times;</span>
    <!-- Form fields: username, password, remember-me, forgot-password -->
    <!-- Login button submits to /controllers/auth/login.php -->
  </div>
</div>
```

#### Register Modal
```html
<div id="registerModal" class="modal">
  <div class="modal-content modal-auth">
    <span class="modal-close" onclick="closeRegisterModal()">&times;</span>
    <!-- Form fields: full_name, username, email, phone, password, confirm_password, terms -->
    <!-- Register button submits to /controllers/auth/register.php -->
  </div>
</div>
```

### Modal Control Functions (`assets/js/main.js`)

```javascript
openAuthModal(authType)      // 'login' or 'register'
closeAuthModal()              // Closes active modal
switchAuthModal(authType)     // Switch between login/register without closing
```

### Modal Features
- Backdrop blur when active
- Close on: X button, ESC key, backdrop click
- Smooth animations (0.15s fade + 0.2s slide)
- Form validation before submission
- Alert messages (success/error)
- Auto-redirect on successful auth

---

## 🔐 Password & Security

### Password Hashing
```php
// config/functions.php
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
```

### Default Test Account
```
Username: admin
Email: admin@auntjoy.test
Password: password123
Role: Administrator
User ID: 1
```

---

## 📊 Session Variable Reference

All available after successful login:

| Variable | Type | Example |
|----------|------|---------|
| `$_SESSION['user_id']` | int | 1 |
| `$_SESSION['username']` | string | "admin" |
| `$_SESSION['email']` | string | "admin@auntjoy.test" |
| `$_SESSION['full_name']` | string | "System Administrator" |
| `$_SESSION['role_id']` | int | 2 |
| `$_SESSION['role_name']` | string | "Administrator" |

**Check in PHP**:
```php
if (isLoggedIn()) { /* User is authenticated */ }
if (hasRole('Administrator')) { /* User is admin */ }
if (hasRole('Customer', 'Sales Personnel')) { /* Multiple roles */ }
```

**Check in JavaScript**:
```javascript
// Window globals set by header.php
window.AUNT_JOY = {
  isLoggedIn: boolean,
  userId: int,
  username: string,
  email: string,
  role: string,
  fullName: string
}
```

---

## 🚀 Complete User Journey

### New User (Register → Browse → Order)
```
1. Visit /index.php
   ↓
2. Click "Partner with us" → Register modal opens
   ↓
3. Fill form (name, username, email, phone, password)
   ↓
4. Submit → POST /controllers/auth/register.php
   ↓
5. Account created, auto-logged in
   ↓
6. Redirect to /views/customer/menu.php
   ↓
7. Browse meals, add to cart
   ↓
8. View cart → Redirects to /views/customer/cart.php
   ↓
9. Review items, place order → POST /controllers/customer/place_order.php
   ↓
10. View orders in /views/customer/orders.php
```

### Returning User (Login → Actions)
```
1. Visit /index.php
   ↓
2. Click "Sign In" → Login modal opens
   ↓
3. Enter username/email + password
   ↓
4. Submit → POST /controllers/auth/login.php
   ↓
5. Role-based redirect:
   - Customer → /views/customer/menu.php
   - Admin → /views/admin/dashboard.php
   - Sales → /views/sales/dashboard.php
   - Manager → /views/manager/dashboard.php
```

### Admin Workflow
```
1. Login as Administrator
   ↓
2. Redirected to /views/admin/dashboard.php
   ↓
3. Access:
   - User Management → /views/admin/users.php
   - Meal Management → /views/admin/meals.php
   - Profile/Settings → /views/auth/profile.php
   ↓
4. Can use all CRUD operations via API controllers
```

---

## ⚠️ Authentication Guards

### Server-Side Guards
- **requireAuth()**: Blocks anonymous users, redirects to login
- **requireRole()**: Blocks unauthorized roles, shows error JSON
- **isLoggedIn()**: Checks session without redirect
- **hasRole()**: Checks role without redirect

### Client-Side Guards
- **Cart Access**: JavaScript `hasCartAccess()` in cart.js
  - Checks `window.AUNT_JOY.isLoggedIn` and role
  - Shows notification if blocked
  - Redirects to login with next parameter

### API Response Guards
- All controllers check `isLoggedIn()` first
- Then check `hasRole()` for authorization
- Return JSON error if blocked (not HTML redirect)

---

## 📝 Summary Table

| Aspect | Details |
|--------|---------|
| **Login Methods** | Modal on /index.php, Dedicated page /views/auth/login.php |
| **Registration** | Modal on /index.php, Dedicated page /views/auth/register.php |
| **Session Type** | PHP native $_SESSION |
| **Session Storage** | Server-side (files or database) |
| **Password Hash** | bcrypt (PASSWORD_BCRYPT) |
| **Role System** | 4 roles: Customer, Administrator, Sales Personnel, Manager |
| **Protected Pages** | 9 pages (3 admin, 2 customer, 1 sales, 1 manager, 1 profile, 1 landing) |
| **API Endpoints** | 15+ protected controllers |
| **Authentication Check** | `isLoggedIn()` in db.php |
| **Authorization Check** | `hasRole()` in db.php |
| **Logout** | Destroys session, redirects to login |
| **Auto-Login on Register** | Yes - User auto-logged in after registration |
| **Remember Me** | UI exists, backend not fully implemented |

---

## 🔧 Configuration Files

- **Database**: `/config/db.php` (connection + auth functions)
- **Functions**: `/config/functions.php` (user/meal/order logic)
- **Schema**: `/database/schema.sql` (database structure)
- **JavaScript Auth**: `/assets/js/auth.js` (form handling)
- **JavaScript Main**: `/assets/js/main.js` (modal control, guards)

---

*Last Updated: December 10, 2025*
