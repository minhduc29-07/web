# 👟 Shoe Inventory Management System

A simple inventory management system built with **pure PHP** and **MySQL**. This application allows for product management, stock tracking, and user account management with role-based access control (Admin/Staff).

## 🚀 Key Features

-   **Authentication**: Secure Login, Registration, and Logout.
-   **Role-Based Access Control (RBAC)**:
    -   **Admin**: Full access to Product Management (CRUD) + User Management (Add/Edit/Delete Staff accounts).
    -   **Staff**: Access to Product Management only (Cannot access the User Management page).
-   **Product Management**:
    -   Add, Update, and Delete shoe products.
    -   Search products by Name, Brand, or SKU.
    -   Validation for duplicate SKUs.
-   **Interface**: Responsive, clean UI built with pure CSS.

## 🛠️ Tech Stack

-   **Language**: PHP (Native)
-   **Database**: MySQL
-   **Frontend**: HTML5, CSS3 (Custom styles)

## ⚙️ Installation Guide

1.  **Clone the project** into your XAMPP/WAMP `htdocs` directory.
2.  **Database Setup**:
    -   Open phpMyAdmin and create a new database named: `shoe_store`.
    -   Import the SQL file (or run the SQL script to create `users` and `shoes` tables).
3.  **Configuration**:
    -   Check the `db.php` file to ensure database connection details are correct (default: user `root`, empty password).
4.  **Default Admin Credentials**:
    -   Username: `admin`
    -   Password: `123456`

---

## 📊 Database Schema (ER Diagram)

Below is the extended architecture schema for the system (rendered automatically by GitHub):

```mermaid
erDiagram
    %% Bảng ROLES
    ROLES {
        int id PK
        varchar name
        varchar description
    }
    
    %% Bảng USERS 
    USERS {
        int id PK
        varchar username
        varchar password_hash
        varchar email
        varchar fullname
        tinyint role_id FK
        datetime created_at
        datetime updated_at
        tinyint is_active
    }
    
    %% Bảng SESSIONS
    SESSIONS {
        int id PK
        int user_id FK
        varchar session_token
        datetime created_at
        datetime last_activity
        varchar ip_address
        varchar user_agent
    }
    
    %% Bảng PASSWORD_RESETS
    PASSWORD_RESETS {
        int id PK
        int user_id FK
        varchar token
        datetime expires_at
        datetime requested_at
        tinyint used
    }
    
    %% Bảng SHOES (Cập nhật: Thêm cost_price)
    SHOES {
        int id PK
        varchar sku
        varchar name
        varchar brand
        int size
        int quantity
        decimal price
        decimal cost_price  
        int created_by_user_id FK
    }

    %% Bảng SALES (MỚI: Lịch sử giao dịch)
    SALES {
        int id PK
        int user_id FK
        varchar product_name
        int quantity
        decimal total_price
        decimal unit_cost_price 
        datetime sale_date
    }

    %% Quan hệ
    ROLES ||--o{ USERS : "belongs to"
    USERS ||--o{ SESSIONS : "has"
    USERS ||--o{ PASSWORD_RESETS : "has"
    USERS ||--o{ SHOES : "manages"
    USERS ||--o{ SALES : "transacts"
