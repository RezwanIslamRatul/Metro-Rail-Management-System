# Admin Login Testing Plan

This document provides a step-by-step guide to test and troubleshoot the admin login functionality.

## Setup Complete

The following changes have been made to fix the admin login:

1. Created robust admin authentication in `admin_auth.php`
2. Updated `admin/index.php` to use the new authentication system
3. Added detailed logging for troubleshooting
4. Created diagnostic tools to check for issues

## Testing Steps

Please follow these steps to test the admin login functionality:

1. First, check if the admin user exists by visiting:
   - http://localhost/metro-rail/admin/check_admin.php

2. If no admin user is found or there are session issues, you can fix it by visiting:
   - http://localhost/metro-rail/admin/fix_admin_login.php

3. Test the admin authentication with:
   - http://localhost/metro-rail/admin/test_admin.php

4. Access the main admin dashboard:
   - http://localhost/metro-rail/admin/index.php

5. To log out, visit:
   - http://localhost/metro-rail/logout.php

6. To log in again through the normal login form:
   - http://localhost/metro-rail/login.php
   - Use the credentials: admin@metrorail.com / admin123

## Troubleshooting

If you encounter issues:

1. Check the logs in `C:/xampp/htdocs/metro-rail/logs/` for error messages
2. Make sure sessions are working properly in PHP
3. Verify that cookies are enabled in your browser
4. Check PHP error logs for any PHP errors
5. Make sure the database connection is working

## Diagnostic Tools

The following diagnostic tools have been created:

- `admin/check_admin.php` - Diagnoses admin login issues
- `admin/fix_admin_login.php` - Manually sets admin session
- `admin/login_helper.php` - Shows admin login details
- `admin/test_admin.php` - Tests admin authentication

## Technical Details

The admin login flow works as follows:

1. User enters credentials in login.php
2. Upon successful login, session variables are set:
   - $_SESSION['user_id']
   - $_SESSION['user_name']
   - $_SESSION['user_email']
   - $_SESSION['user_role']
   - $_SESSION['last_activity']
3. User is redirected to admin/index.php
4. admin_auth.php checks if user is logged in and has admin role
5. If authorized, admin pages are displayed
