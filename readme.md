```mermaid
erDiagram
    %% Định nghĩa bảng USERS
    USERS {
        int id PK "ID Người dùng"
        varchar username UK "Tên đăng nhập"
        varchar password "Mật khẩu"
        varchar email UK "Email"
        date dob "Ngày sinh"
        varchar location "Địa điểm"
        enum role "Vai trò (admin/staff)"
    }
    
    %% Định nghĩa bảng SHOES
    SHOES {
        int id PK "ID Sản phẩm"
        varchar sku UK "Mã SKU"
        varchar name "Tên sản phẩm"
        varchar brand "Thương hiệu"
        int size "Kích cỡ"
        int quantity "Số lượng tồn kho"
        decimal price "Giá (VND)"
    }
    
    %% Mối quan hệ logic
    USERS ||--o{ SHOES : Quản_lý
