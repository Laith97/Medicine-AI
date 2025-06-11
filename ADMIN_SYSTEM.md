# Admin User Management System

This document describes the admin user management system implemented for the Medical AI Diagnosis platform.

## Features

### Admin Dashboard
- Overview statistics (total users, admin users, regular users, recent users)
- Quick access to user management functions
- Recent user activity display

### User Management
- **View All Users**: Paginated list of all system users with their details
- **Create New User**: Form to create new users with optional admin privileges
- **Edit User**: Update user information including name, email, password, and admin status
- **View User Details**: Detailed view of individual users with statistics and activity
- **Toggle Admin Status**: Promote/demote users to/from admin role
- **Delete Users**: Remove users from the system (with safety checks)

### Security Features
- **Admin Middleware**: Protects admin routes from unauthorized access
- **Self-Protection**: Admins cannot delete themselves or remove their own admin privileges
- **Role-Based Access**: Only admin users can access admin functionality

## Admin Routes

All admin routes are prefixed with `/admin` and protected by authentication and admin middleware:

- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/users` - List all users
- `GET /admin/users/create` - Create new user form
- `POST /admin/users` - Store new user
- `GET /admin/users/{user}` - View user details
- `GET /admin/users/{user}/edit` - Edit user form
- `PUT /admin/users/{user}` - Update user
- `DELETE /admin/users/{user}` - Delete user
- `POST /admin/users/{user}/toggle-admin` - Toggle admin status

## Admin Access

### Default Admin User
A default admin user is created with the following credentials:
- **Email**: admin@medical.com
- **Password**: admin123

### Making Users Admin
1. **Via Admin Panel**: Use the "Toggle Admin" button in the user management interface
2. **Via Database**: Set `is_admin = 1` in the users table
3. **Via Tinker**: `User::find(ID)->makeAdmin()`

### Admin Navigation
Admin users will see additional menu items in their user dropdown:
- Admin Dashboard
- Manage Users

## User Model Methods

The User model includes several helper methods for admin functionality:

```php
// Check if user is admin
$user->isAdmin()

// Make user admin
$user->makeAdmin()

// Remove admin privileges
$user->removeAdmin()
```

## Middleware

The `AdminMiddleware` protects admin routes by:
1. Checking if user is authenticated
2. Verifying user has admin privileges
3. Redirecting unauthorized users appropriately

## Views

Admin views are located in `resources/views/admin/`:
- `dashboard.blade.php` - Admin dashboard
- `users/index.blade.php` - User listing
- `users/create.blade.php` - Create user form
- `users/edit.blade.php` - Edit user form
- `users/show.blade.php` - User details view

## Database

The admin system uses the existing `users` table with an additional `is_admin` boolean column added via migration `2025_06_05_105858_add_is_admin_to_users_table.php`.

## Usage Instructions

1. **Login as Admin**: Use the admin credentials or any user with admin privileges
2. **Access Admin Panel**: Click on your name in the top-right corner and select "Admin Dashboard"
3. **Manage Users**: From the dashboard, click "Manage All Users" or use the "Manage Users" link in the dropdown
4. **Create Users**: Click "Create New User" to add new users to the system
5. **Edit Users**: Click "Edit" next to any user to modify their information
6. **Toggle Admin**: Use the "Make Admin" or "Remove Admin" buttons to change user privileges

## Security Considerations

- Admin routes are protected by middleware
- Users cannot modify their own admin status
- Users cannot delete their own accounts
- All admin actions are logged through Laravel's built-in logging
- Password changes require confirmation
- Email uniqueness is enforced

## Future Enhancements

Potential improvements to consider:
- Activity logging for admin actions
- Bulk user operations
- User import/export functionality
- Role-based permissions beyond simple admin/user
- Email notifications for admin actions
- User suspension/activation features