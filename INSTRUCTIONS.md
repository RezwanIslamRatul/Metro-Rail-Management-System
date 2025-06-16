# Instructions for Running the Metro Rail Booking/Management System

These instructions will guide you through the process of setting up and running the Metro Rail Booking/Management System on your local machine.

## Prerequisites

Before you begin, make sure you have the following software installed:

-   **Web Server:** Apache or Nginx
-   **PHP:** Version 7.4 or higher
-   **MySQL:** Version 5.7 or higher
-   **Git:** For cloning the repository

## Installation Steps

1.  **Clone the repository:**
    Open your terminal and run the following command to clone the repository to your local machine:
    ```bash
    git clone [repository_url]
    cd metro-rail
    ```

2.  **Create a MySQL database:**
    *   Open your MySQL client (e.g., MySQL Workbench, phpMyAdmin).
    *   Create a new database named `metro_rail`.

3.  **Import the database schema:**
    *   In your MySQL client, import the `database/metro_rail.sql` file to create the necessary tables and data.

4.  **Configure the database connection:**
    *   Open the `includes/config.php` file in a text editor.
    *   Update the following constants with your MySQL server credentials:
        ```php
        define('DB_HOST', 'localhost'); // Your database host
        define('DB_USER', 'root');      // Your database username
        define('DB_PASS', '');          // Your database password
        define('DB_NAME', 'metro_rail');   // Your database name
        ```
    *   Save the file.

5.  **Set up your web server:**
    *   Configure your web server (Apache or Nginx) to point to the `metro-rail` directory.
    *   For Apache, you can create a virtual host configuration file in `/etc/apache2/sites-available/`:
        ```apache
        <VirtualHost *:80>
            ServerName localhost
            DocumentRoot /path/to/metro-rail
            
            <Directory /path/to/metro-rail>
                AllowOverride All
                Require all granted
            </Directory>
        </VirtualHost>
        ```
    *   Enable the virtual host and restart Apache:
        ```bash
        sudo a2ensite metro-rail.conf
        sudo systemctl restart apache2
        ```
    *   For Nginx, you can create a server block in `/etc/nginx/sites-available/`:
        ```nginx
        server {
            listen 80;
            server_name localhost;
            root /path/to/metro-rail;

            index index.php index.html index.htm;

            location / {
                try_files $uri $uri/ /index.php?$args;
            }

            location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php7.4-fpm.sock;
            }

            location ~ /\.ht {
                deny all;
            }
        }
        ```
    *   Enable the server block and restart Nginx:
        ```bash
        sudo ln -s /etc/nginx/sites-available/metro-rail /etc/nginx/sites-enabled/
        sudo systemctl restart nginx
        ```

6.  **Access the application:**
    *   Open your web browser and navigate to `http://localhost` (or your configured ServerName/server_name).

## Additional Notes

-   Make sure that PHP is properly configured with the necessary extensions (e.g., `mysqli`, `pdo_mysql`).
-   If you encounter any issues, check the web server and PHP error logs for more information.
-   For debugging purposes, you can enable debug mode in `includes/config.php` by setting `define('DEBUG_MODE', true);`.

## Admin Login

-   To access the admin panel, navigate to `http://localhost/admin`.
-   Use the following default credentials:
    *   Username: `admin`
    *   Password: `password`
    *   **Note:** It is highly recommended to change the default admin password after logging in for the first time.

## Troubleshooting

-   **Database connection errors:** Double-check the database credentials in `includes/config.php`.
-   **Page not found errors:** Make sure your web server is properly configured to point to the project directory.
-   **Internal Server Errors:** Check the web server and PHP error logs for more information.

By following these instructions, you should be able to successfully set up and run the Metro Rail Booking/Management System on your local machine.
