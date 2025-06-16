# Admin Login Fix Guide

This document outlines the steps to fix and troubleshoot admin login issues in the Metro Rail Management System.

## Common Issues and Solutions

1. **Admin Authentication Problems**:
   - Admin cannot log in through the regular login page
   - Session variables not being set correctly
   - Redirect issues after login

2. **Database Configuration**:
   - Admin user might not exist or has incorrect role
   - Password might be outdated or incorrectly hashed

## Files Modified

We've updated the following files to improve admin authentication:

1. `login.php` - Enhanced debugging and session handling
2. `includes/functions.php` - Added debug_log function for better tracking
3. `admin/admin_auth.php` - Improved authentication logging and error handling
4. `direct_admin_login.php` - Added debugging for direct admin login

## New Diagnostic Tools

We've created several diagnostic tools to help identify and fix admin login issues:

1. `admin/admin_login_tool.php` - Step-by-step login testing and diagnostics
2. `admin/admin_diagnostics.php` - Detailed system and session information
3. `admin/reset_admin_password.php` - Quick admin password reset

## How to Fix Admin Login

Follow these steps to fix admin login issues:

### Step 1: Reset Admin Password

1. Visit `http://localhost/metro-final/admin/admin_login_tool.php`
2. Click on "Reset Password to 'admin123'"
3. Confirm the admin information is correct (email, role, etc.)

### Step 2: Test Login Process

1. On the admin login tool page, enter the admin email and password ('admin123')
2. Click "Test Login" to verify the credentials
3. Check if the session variables are set correctly
4. Verify isAdmin() returns 'true'

### Step 3: Try Regular Login

1. Go to `http://localhost/metro-final/login.php`
2. Enter the admin email and password ('admin123')
3. If login fails, check the logs directory for error messages

### Step 4: Use Direct Admin Login

If regular login still doesn't work, you can use:
- `http://localhost/metro-final/direct_admin_login.php`

This will bypass the login form and set the admin session directly.

## Debugging

If issues persist, check these log files:

1. `logs/login_attempts.log` - Records of login attempts
2. `logs/successful_logins.log` - Successful login information
3. `logs/admin_access_denied.log` - Records of failed admin access attempts
4. `logs/role_debug.txt` - Role check debugging information

## Configuration Issues

Make sure these settings are correct:

1. **APP_URL** in `includes/config.php` should be set to `http://localhost/metro-final`
2. Database connection parameters should be correct
3. Check if the session path and permissions are correct

## Maintenance

After fixing the issues, you can:

1. Implement stronger password security
2. Add two-factor authentication for admin
3. Set up periodic password rotation
4. Create a password recovery mechanism
