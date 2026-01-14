# Shoe Store POS System 👟

A web-based Point of Sale (POS) and Inventory Management System designed for small shoe retailers. [cite_start]This project was developed as part of the **INS3064 Multimedia Design and Web Development** course at VNU-IS[cite: 1, 2].

## 📑 Project Overview
This system helps shop owners manage their inventory, process sales, and track financial performance through detailed reports. It features a secure login system and a user-friendly interface.

## ✨ Key Features

### 👤 User Authentication & Management
* **Secure Registration**: New staff members can register with encrypted passwords.
* **Role-based Access**: Admins have exclusive access to user management and financial reports.
* **Account Control**: Create, edit, and manage staff accounts directly from the dashboard.

### 📦 Inventory & Warehouse
* **Product Tracking**: Manage product details including SKU, Brand, Price, and Cost Price.
* **Low Stock Alerts**: Automatic visual warnings when product quantities drop below 5 units.
* **Warehouse Overview**: Real-time statistics on total products, total stock, and total warehouse value.

### 🛒 Point of Sale (POS)
* **Visual Catalog**: Browse products with clear images and pricing.
* **Size Management**: Dynamic size selection with real-time stock availability display.
* **Smart Cart**: Automatically calculates bill totals and updates inventory upon payment.

### 📊 Reports & History
* **Financial Analytics**: View daily revenue, COGS (Cost of Goods Sold), and Gross Profit.
* **Performance Tracking**: Top 5 best-selling products ranked by quantity.
* **Transaction Logs**: Comprehensive daily revenue summary and detailed transaction history.

## 🛠 Tech Stack
* **Frontend**: HTML5, CSS3.
* **Backend**: PHP.
* **Database**: MySQL.

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
