# Metro Rail Booking/Management System

A comprehensive web application for metro rail booking and management with role-based access control. This system aims to provide an efficient and user-friendly platform for managing metro rail operations, including station management, train scheduling, ticket booking, and user management.

## Features

-   **User Registration and Authentication:** Secure user registration and login system with password hashing and session management.
-   **Role-based Access Control:** Different user roles (Admin, Staff, Registered Users) with specific permissions and access levels.
-   **Metro Station Management:** Admins can add, update, and delete metro stations, including details like station name, location, and available facilities.
-   **Train Schedule Management:** Admins can create and manage train schedules, including train routes, departure times, and arrival times.
-   **Ticket Booking System:** Registered users can book tickets online, select preferred routes and schedules, and make secure payments.
-   **Fare Calculation:** The system automatically calculates fares based on the selected route, distance, and class.
-   **Route Planning:** Users can plan their journey by selecting source and destination stations, and the system will suggest the best possible routes.
-   **User Dashboard:** Registered users can view their booking history, manage their profile, and update their contact information.
-   **Admin Panel:** Comprehensive admin panel for managing all aspects of the system, including stations, trains, users, and bookings.
-   **Staff Management Portal:** Staff members can manage schedules, bookings, and user inquiries.
-   **Reporting and Analytics:** Generate reports on various aspects of the system, such as booking statistics, revenue, and user activity.

## Technology Stack

-   **Frontend:** HTML, CSS, JavaScript, Bootstrap, jQuery
-   **Backend:** PHP (version 7.4 or higher)
-   **Database:** MySQL (version 5.7 or higher)

## Project Structure

```
metro-rail/
├── index.php              # Entry point of the application. Displays a hero section with a brief description of the metro rail system, a quick booking form, a features section, latest updates, popular routes, testimonials, and a call to action.
├── register.php           # User registration page
├── login.php              # User login page
├── logout.php             # User logout script
├── contact.php            # Contact page
├── faq.php                # FAQ page
├── schedule-detail.php    # Schedule detail page
├── schedules.php          # Schedules page
├── stations.php           # Stations page
├── system_diagnostics.php # System diagnostics page
├── system_fix.php         # System fix page
│
├── css/                   # CSS files
│   ├── admin_modern.css   # Admin modern CSS
│   ├── admin.css          # Admin CSS
│   ├── booking-history.css# Booking history CSS
│   ├── booking.css        # Booking CSS
│   ├── modern.css         # Modern CSS
│   ├── pages.css          # Pages CSS
│   ├── style.css          # Main CSS
│   └── user.css           # User CSS
│
├── js/                    # JavaScript files
│   └── script.js          # Main script file
│
├── images/                # Image assets
│   └── metro-train.png    # Metro train image
│
├── includes/              # PHP includes
│   ├── admin_sidebar.php  # Admin sidebar
│   ├── config.php         # Database configuration settings, such as the database host, username, password, and database name. It also defines constants for debug mode, application URL, session timeout, contact email, and contact phone.
│   ├── db.php             # Database connection file. Establishes the database connection using the credentials defined in `config.php`. It includes functions for executing queries, fetching single rows, fetching multiple rows, inserting data, updating data, and deleting data. It uses prepared statements to prevent SQL injection attacks.
│   ├── footer.php         # Page footer
│   ├── functions.php      # Helper functions for sanitizing user input, redirecting to a URL, checking if a user is logged in, checking if a user has a specific role, logging debug information, getting logged-in user data, generating a random string, displaying error/success/info messages, formatting dates and currency, generating pagination links, calculating distance between two coordinates, calculating fare based on distance, generating QR codes for tickets, sending email notifications, logging system activity, and setting/displaying flash messages.
│   └── header.php         # This file contains the HTML header, including the doctype, meta tags, title, CSS links, and the navigation bar. It starts the session, checks for session timeout, and includes necessary files such as `config.php`, `db.php`, and `functions.php`. The navigation bar includes links to the home page, schedules, stations, fare calculator, contact us, login, and register pages. It also displays the user's name and a dropdown menu with links to the profile, booking history, and logout pages.
│   ├── footer.php         # This file contains the HTML footer, including the closing `</div>` tag for the main content, the footer section with information about Metro Rail, quick links, social media links, and a newsletter subscription form. It also includes the Bootstrap JS bundle, jQuery, and custom JavaScript files.
│   ├── admin_sidebar.php  # This file contains the sidebar for the admin panel. It includes links to the dashboard, stations, trains, routes, schedules, fares, bookings, users, staff, maintenance, announcements, reports, settings, and profile pages. It also includes a link to the main site and a logout link.
│   ├── update_function.php # This file contains a modified `update` function that is intended to replace the original `update` function in `includes/db.php`. The modified function includes error logging and debugging information to help identify and resolve issues with database updates.
├── admin/                 # Admin panel
│   ├── admin_auth.php     # Admin authentication. This file handles the authentication of admin users.
│   ├── announcements.php  # This file allows administrators to create, edit, and delete announcements that are displayed on the website.
│   ├── bookings.php       # This file allows administrators to manage bookings, including viewing, confirming, and cancelling bookings.
│   ├── change_password.php# Change password page
│   ├── fares.php          # This file allows administrators to manage fares, including setting base rates and per-kilometer rates.
│   ├── index.php          # Admin dashboard. Displays various statistics, such as the total number of users, staff members, stations, trains, routes, and bookings. It also displays recent bookings, new users, and active maintenance schedules. It uses a doughnut chart to display booking statistics. It includes the `admin_auth.php` file for authentication and the `admin_sidebar.php` file for the sidebar. 
│   ├── stations.php       # This file allows administrators to manage metro stations. They can add, edit, and delete stations, including details such as station name, code, address, latitude, longitude, and status. The page displays a list of all stations with their details and actions. It uses a modal form for adding and editing stations and includes validation for form data.
│   ├── trains.php         # This file allows administrators to manage trains, including adding, editing, and deleting trains, and assigning trains to routes.
│   ├── routes.php         # This file allows administrators to manage routes, including adding, editing, and deleting routes, and assigning stations to routes.
│   ├── schedules.php      # This file allows administrators to manage train schedules, including creating, editing, and deleting schedules, and assigning trains to schedules.
│   ├── settings.php        # This file allows administrators to manage system settings, such as the website title, contact information, and other configuration options.
│   ├── staff.php          # This file allows administrators to manage staff members, including adding, editing, and deleting staff members, and assigning roles and permissions.
│   ├── users.php          # This file allows administrators to manage users, including viewing, editing, and deleting user accounts.
│   ├── update_admin_styling.php # This file allows administrators to update the styling of the admin panel.
│
├── staff/                 # Staff panel
│   ├── check-in.php       # Check-in page
│   └── index.php          # Staff dashboard
│
├── user/                  # User panel
│   ├── booking.php        # Ticket booking page
│   ├── history.php        # Booking history page
│   ├── index.php          # User dashboard
│   ├── password.php       # Password management
│   ├── profile.php        # User profile
│   └── view-booking.php   # View booking page
│
├── database/              # Database scripts
│   └── metro_rail.sql     # Database schema
│
├── logs/                  # Log files
│   └── ...                # Various log files for debugging
│
└── vendor/                # Third-party libraries
```

## Installation

1.  **Clone the repository:**
    ```bash
    git clone [repository_url]
    cd metro-rail
    ```
2.  **Import the database:**
    *   Create a new MySQL database.
    *   Import the `database/metro_rail.sql` file using a MySQL client or phpMyAdmin.
3.  **Configure the database connection:**
    *   Edit the `includes/config.php` file.
    *   Update the database credentials (host, username, password, database name) to match your MySQL server configuration.
4.  **Set up your web server:**
    *   Configure your web server (e.g., Apache, Nginx) to point to the project directory.
    *   Ensure that PHP is enabled and properly configured.
5.  **Access the application:**
    *   Open your web browser and navigate to the project URL.

## User Roles

-   **Admin:**
    *   Full access to the system.
    *   Manage stations, trains, routes, users, and system settings.
    *   Generate reports and analytics.
-   **Staff:**
    *   Manage train schedules and bookings.
    *   Handle user inquiries and provide support.
-   **Registered User:**
    *   Book tickets online.
    *   View booking history and manage personal profile.

## Contribution Guidelines

We welcome contributions to the Metro Rail Booking/Management System! If you'd like to contribute, please follow these guidelines:

1.  Fork the repository.
2.  Create a new branch for your feature or bug fix.
3.  Make your changes and test them thoroughly.
4.  Submit a pull request with a clear description of your changes.

## Credits

-   This project uses Bootstrap for responsive design.
-   jQuery is used for DOM manipulation and AJAX requests.
-   The development team includes [Your Names Here].

## Debugging and Diagnostics

The project includes several debugging and diagnostic tools to help identify and resolve issues:

-   **Debug Logs:** Check the `logs/` directory for various log files, including admin access logs, login attempts, and error messages.
-   **System Diagnostics Page:** The `system_diagnostics.php` page provides information about the system configuration and status.
-   **Admin Login Tool:** The `admin/admin_login_tool.php` can be used to quickly login as an admin user for testing purposes.
-   **Debug Mode:** Enable debug mode in `includes/config.php` to display detailed error messages.

## License

This project is for educational purposes.
