erDiagram
    %% Bảng ROLES: Bảng tham chiếu cho quyền hạn
    ROLES {
        int id PK
        varchar name "Tên vai trò (Admin, Staff)"
        varchar description
    }
    
    %% Bảng USERS: Đã cập nhật để dùng password_hash và role_id (giống sơ đồ tham chiếu)
    USERS {
        int id PK
        varchar username UK
        varchar password_hash "Hash mật khẩu (thay vì Plain Text)"
        varchar email UK
        varchar dob "Ngày sinh (Từ App Gốc)"
        varchar location "Địa điểm (Từ App Gốc)"
        int role_id FK "Liên kết tới ROLES"
        datetime created_at
        datetime updated_at
        tinyint is_active
    }
    
    %% Bảng SHOES: Bổ sung Khóa ngoại để liên kết người quản lý
    SHOES {
        int id PK
        varchar sku UK
        varchar name
        varchar brand
        int size
        int quantity
        decimal price
        int created_by_user_id FK "Người tạo/Quản lý sản phẩm"
    }

    %% Bảng SESSIONS: Quản lý phiên đăng nhập và hoạt động của người dùng
    SESSIONS {
        int id PK
        int user_id FK
        varchar session_token
        datetime created_at
        datetime last_activity
        varchar ip_address
        varchar user_agent
    }
    
    %% Bảng PASSWORD_RESETS: Tính năng quên mật khẩu an toàn
    PASSWORD_RESETS {
        int id PK
        int user_id FK
        varchar token
        datetime expires_at
        datetime requested_at
        tinyint used
    }

    %% MỐI QUAN HỆ (Relationships)

    USERS ||--|{ ROLES : belongs_to "Người dùng thuộc về 1 Vai trò"
    
    USERS ||--o{ SHOES : manages "Người dùng quản lý nhiều sản phẩm"
    
    USERS ||--o{ SESSIONS : has "Người dùng có nhiều phiên hoạt động"
    
    USERS ||--o{ PASSWORD_RESETS : has "Người dùng có nhiều yêu cầu reset mật khẩu"
