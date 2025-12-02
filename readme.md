erDiagram
    %% Định nghĩa bảng USERS (Quản lý Tài khoản)
    USERS {
        int id PK
        varchar(50) username UK "Tên đăng nhập"
        varchar(255) password "Mật khẩu (Plain Text)"
        varchar(100) email UK
        date dob "Ngày sinh"
        varchar(100) location "Địa điểm"
        enum role "Vai trò ('admin', 'staff')"
    }
    
    %% Định nghĩa bảng SHOES (Quản lý Sản phẩm)
    SHOES {
        int id PK
        varchar(50) sku UK "Mã SKU"
        varchar(100) name "Tên sản phẩm"
        varchar(100) brand "Thương hiệu"
        int size
        int quantity "Số lượng tồn kho"
        decimal(10,2) price "Giá (VND)"
    }
    
    %% Mối quan hệ logic: Một người dùng có thể quản lý nhiều sản phẩm.
    USERS ||--o{ SHOES : Quản_lý
