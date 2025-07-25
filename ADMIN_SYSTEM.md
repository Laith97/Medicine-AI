# Admin User Management System

This document describes the admin user management system implemented for the Medical AI Diagnosis platform.

## Features

### Admin Dashboard
- Overview statistics (total users, admin users, regular users, recent users)
- Quick access to user management functions
- Recent user activity display

### User Management
- **View All Users**: Paginated list of all system users with their details
- **Create New User**: Form to create new users (regular users only)
- **Edit User**: Update user information including name, email, password, and role
- **View User Details**: Detailed view of individual users with statistics and activity
- **Delete Users**: Remove users from the system (with safety checks)

### Security Features
- **Admin Middleware**: Protects admin routes from unauthorized access using separate admin guard
- **Separate Admin Authentication**: Admins use a separate authentication system with their own login
- **Role-Based Access**: Only authenticated admin users can access admin functionality

## Admin Routes

All admin routes are prefixed with `/admin` and protected by authentication and admin middleware:

### Authentication Routes
- `GET /admin/login` - Admin login form
- `POST /admin/login` - Admin login handler
- `POST /admin/logout` - Admin logout

### Admin Panel Routes
- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/users` - List all users
- `GET /admin/users/create` - Create new user form
- `POST /admin/users` - Store new user
- `GET /admin/users/{user}` - View user details
- `GET /admin/users/{user}/edit` - Edit user form
- `PUT /admin/users/{user}` - Update user
- `DELETE /admin/users/{user}` - Delete user

## Admin Access

### Default Admin User
A default admin user is created in the `admins` table with the following credentials:
- **Email**: admin@medical.com
- **Password**: admin123

### Admin Access
Admins are managed separately from regular users:
1. **Admin Login**: Access via `/admin/login` with admin credentials
2. **Separate Database Table**: Admins are stored in the `admins` table, not the `users` table
3. **Via Database**: Insert directly into the `admins` table
4. **Via Tinker**: `Admin::create(['name' => 'Name', 'email' => 'email@example.com', 'password' => Hash::make('password')])`

### Admin Navigation
When logged in as an admin, you will see additional menu items:
- Admin Dashboard
- Manage Users

## Admin Model

The `Admin` model is separate from the `User` model and includes standard Authenticatable functionality:

```php
// Create new admin
Admin::create([
    'name' => 'Admin Name',
    'email' => 'admin@example.com', 
    'password' => Hash::make('password')
]);

// Check if current user is admin (in views)
Auth::guard('admin')->check()
```

## Middleware

The `AdminMiddleware` protects admin routes by:
1. Checking if admin is authenticated using the `admin` guard
2. Redirecting unauthenticated users to `/admin/login`

## Views

Admin views are located in `resources/views/admin/`:
- `auth/login.blade.php` - Admin login form
- `dashboard.blade.php` - Admin dashboard
- `users/index.blade.php` - User listing
- `users/create.blade.php` - Create user form
- `users/edit.blade.php` - Edit user form
- `users/show.blade.php` - User details view

## Database

The admin system uses a separate `admins` table created via migration `2025_07_24_061737_create_admins_table.php`. Regular users are stored in the `users` table and are completely separate from admin accounts.

## Usage Instructions

1. **Login as Admin**: Navigate to `/admin/login` and use admin credentials
2. **Access Admin Panel**: After login, you'll be redirected to the admin dashboard
3. **Manage Users**: From the dashboard, click "Manage All Users" to view regular users
4. **Create Users**: Click "Create New User" to add new regular users to the system
5. **Edit Users**: Click "Edit" next to any user to modify their information
6. **Delete Users**: Use the delete button to remove users from the system

## Security Considerations

- Admin routes are protected by separate admin middleware and guard
- Admins and regular users are completely separate systems
- Admin authentication is independent from user authentication
- All admin actions are logged through Laravel's built-in logging
- Password changes require confirmation
- Email uniqueness is enforced in both tables

## Future Enhancements

Potential improvements to consider:
- Activity logging for admin actions
- Bulk user operations
- User import/export functionality
- Multiple admin roles with different permissions
- Email notifications for admin actions
- User suspension/activation features
- Admin password reset functionality
