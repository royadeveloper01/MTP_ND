# MTP Store

A lightweight, full-featured e-commerce web application built with native PHP, MySQL, and Bootstrap 5.

## Features

### Customer Features
*   **Browse Products**: Filter products by category (Men/Women).
*   **Product Variations**: Select specific sizes and colors for items.
*   **Interactive Cart**: Add items to the cart instantly using AJAX (Alpine.js) without page reloads.
*   **User Accounts**: Secure Registration, Login, and "Remember Me" functionality.
*   **Order Management**: Place orders and view personal order history/status.
*   **Password Recovery**: Secure "Forgot Password" flow via email using PHPMailer.

### Admin Features
*   **Dashboard**: Real-time overview of total products, orders, and registered customers.
*   **Order Management**: View detailed order information and update order statuses (Pending, Shipped, Delivered, etc.).
*   **Attribute Management**: Add or remove product attributes like Sizes and Colors.

## Tech Stack

*   **Backend**: PHP 7.4+ (Native)
*   **Database**: MySQL / MariaDB
*   **Frontend**: HTML5, CSS3, Bootstrap 5
*   **Interactivity**: Alpine.js
*   **Dependencies**: Composer (PHPMailer)

## Installation & Setup

### 1. Prerequisites
*   PHP installed (via Laragon, XAMPP, or standalone).
*   MySQL database.
*   [Composer](https://getcomposer.org/) installed globally.

### 2. Installation Steps

1.  **Clone the repository** (or download source):
    ```bash
    git clone https://github.com/royadeveloper01/MTP_ND.git
    cd MTP_ND
    ```

2.  **Install Dependencies**:
    Run the following command in the project root to install PHPMailer:
    ```bash
    composer install
    ```

3.  **Database Setup**:
    *   Create a new database named `mtp_nd`.
    *   Import the provided SQL schema (ensure tables like `users`, `products`, `orders`, `cart` are created).

4.  **Environment Configuration**:
    *   Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    *   Open `.env` and configure your database and SMTP settings:
        ```ini
        # Database
        DB_HOST=localhost
        DB_NAME=mtp_nd
        DB_USER=root
        DB_PASS=

        # Email (Required for Forgot Password)
        SMTP_HOST=smtp.gmail.com
        SMTP_USER=your-email@gmail.com
        SMTP_PASS=your-app-password
        ```

### 3. Running the Project
*   **Laragon/XAMPP**: Place the project folder in your `www` or `htdocs` directory and visit `http://localhost/MTP_ND`.
*   **PHP Built-in Server**:
    ```bash
    php -S localhost:8000
    ```

## Security Note
This project uses a `.env` file to store sensitive credentials. **Never** commit your `.env` file to version control. The `.gitignore` file is already configured to exclude it.

## License
Open source.