<?php
require_once 'db.php';

check_login();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$message = ''; 
$message_type = ''; 

// Biến cho form Edit
$edit_note_id = '';
$edit_sku = '';
$edit_name = '';
$edit_brand = '';
$edit_size = '';
$edit_quantity = '';
$edit_price = '';
$edit_image = ''; // Biến chứa tên ảnh hiện tại khi sửa
$is_editing = false; 


// Xử lý lỗi quyền truy cập
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'admin_only') {
        $message = "Error: You do not have permission to access the User Management page.";
        $message_type = 'error';
    }
}

// --- 2. XỬ LÝ FORM (THÊM / SỬA) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_shoe'])) {
    
    $sku = $conn->real_escape_string($_POST['sku']);
    $name = $conn->real_escape_string($_POST['name']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $size = (int)$_POST['size'];
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $note_id = $conn->real_escape_string($_POST['note_id']); 

    // Giữ lại giá trị nhập nếu có lỗi
    $edit_sku = $sku;
    $edit_name = $name;
    $edit_brand = $brand;
    $edit_size = $size;
    $edit_quantity = $quantity;
    $edit_price = $price;

    // --- LOGIC UPLOAD ẢNH ---
    $image_filename = ""; // Tên file ảnh sẽ lưu vào DB
    $has_new_image = false; // Cờ kiểm tra xem có upload ảnh mới không

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        // Tạo thư mục uploads nếu chưa có
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Lấy đuôi file (jpg, png...)
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        
        // Kiểm tra đuôi file hợp lệ
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_extension, $allowed_ext)) {
            // Đặt tên file mới: time_sku.duoi (để tránh trùng tên)
            $new_filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '', $sku) . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_filename = $new_filename;
                $has_new_image = true;
            } else {
                $message = "Error: Failed to upload image.";
                $message_type = 'error';
            }
        } else {
            $message = "Error: Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            $message_type = 'error';
        }
    }

    // Nếu không có lỗi upload thì mới lưu vào DB
    if (empty($message)) {
        if (empty($sku) || empty($name) || empty($brand) || $size <= 0 || $quantity < 0 || $price <= 0) {
            $message = "Error: Please fill in all fields correctly.";
            $message_type = 'error';
        } else {
            try {
                if (!empty($note_id)) {
                    // --- UPDATE (SỬA) ---
                    if ($has_new_image) {
                        // Nếu có ảnh mới -> Cập nhật cả cột image
                        $stmt = $conn->prepare("UPDATE shoes SET sku = ?, name = ?, brand = ?, size = ?, quantity = ?, price = ?, image = ? WHERE id = ?");
                        $stmt->bind_param("sssiidsi", $sku, $name, $brand, $size, $quantity, $price, $image_filename, $note_id);
                    } else {
                        // Nếu KHÔNG có ảnh mới -> Giữ nguyên ảnh cũ (không update cột image)
                        $stmt = $conn->prepare("UPDATE shoes SET sku = ?, name = ?, brand = ?, size = ?, quantity = ?, price = ? WHERE id = ?");
                        $stmt->bind_param("sssiidi", $sku, $name, $brand, $size, $quantity, $price, $note_id);
                    }
                    
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $message = "Product updated successfully!";
                        $message_type = 'success';
                    } else {
                        $message = "No changes made or error during update.";
                        $message_type = 'info';
                    }

                } else {
                    // --- INSERT (THÊM MỚI) ---
                    // Nếu không upload ảnh, dùng ảnh mặc định
                    if (!$has_new_image) {
                        $image_filename = 'no-image.png'; 
                    }

                    $stmt = $conn->prepare("INSERT INTO shoes (sku, name, brand, size, quantity, price, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssiids", $sku, $name, $brand, $size, $quantity, $price, $image_filename);
                    
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $message = "New product added successfully!";
                        $message_type = 'success';
                        // Reset form
                        $edit_sku = $edit_name = $edit_brand = '';
                        $edit_size = $edit_quantity = $edit_price = 0;
                    } else {
                        $message = "Error: Command executed but no rows were added.";
                        $message_type = 'error';
                    }
                }
                $stmt->close();

            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) { 
                    $message = "Error: SKU '" . html_safe($sku) . "' already exists.";
                    $message_type = 'error';
                    $is_editing = !empty($note_id); 
                } else {
                    $message = "Database Error: " . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    } else {
        // Nếu có lỗi upload ảnh thì giữ trạng thái form
         $is_editing = !empty($note_id);
    }
}

// --- 1. LẤY SỐ LIỆU THỐNG KÊ (DASHBOARD) ---
// Tính toán các con số để hiển thị trên 4 thẻ bài
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(quantity) as total_stock,
        SUM(quantity * price) as total_value,
        COUNT(CASE WHEN quantity < 5 THEN 1 END) as low_stock
    FROM shoes
")->fetch_assoc();

// --- 3. XÓA SẢN PHẨM ---
if (isset($_GET['delete'])) {
    $note_id_to_delete = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM shoes WHERE id = ?");
    $stmt->bind_param("i", $note_id_to_delete);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Product deleted successfully.";
        $message_type = 'success';
    } else {
        $message = "Error deleting product or product not found.";
        $message_type = 'error';
    }
    $stmt->close();
}

// --- 4. LẤY DỮ LIỆU ĐỂ SỬA ---
if (isset($_GET['edit'])) {
    $note_id_to_edit = (int)$_GET['edit'];
    // Lấy thêm cột image
    $stmt = $conn->prepare("SELECT sku, name, brand, size, quantity, price, image FROM shoes WHERE id = ?");
    $stmt->bind_param("i", $note_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $note = $result->fetch_assoc();
        $edit_note_id = $note_id_to_edit;
        $edit_sku = $note['sku'];
        $edit_name = $note['name'];
        $edit_brand = $note['brand'];
        $edit_size = $note['size'];
        $edit_quantity = $note['quantity'];
        $edit_price = $note['price'];
        $edit_image = $note['image']; // Lấy tên ảnh
        $is_editing = true;
    }
    $stmt->close();
}

// --- 5. TÌM KIẾM VÀ HIỂN THỊ DANH SÁCH ---
$search_query = "";
$search_term = "";

$sql = "SELECT * FROM shoes";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $sql .= " WHERE name LIKE ? OR brand LIKE ? OR sku LIKE ?";
    $search_query = "%" . $search_term . "%";
}

$sql .= " ORDER BY id DESC";

$stmt_read = $conn->prepare($sql);

if (!empty($search_query)) {
    $stmt_read->bind_param("sss", $search_query, $search_query, $search_query);
}

$stmt_read->execute();
$result_read = $stmt_read->get_result();
$shoes_list = [];
while ($row = $result_read->fetch_assoc()) {
    $shoes_list[] = $row;
}
$stmt_read->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Inventory Management</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <h2 style="font-weight: 600;">
            Welcome, <?php echo html_safe($username); ?>!
        </h2>
        <p style="color: #666; margin-top: -10px; margin-bottom: 30px;">
            Here is your warehouse overview.
        </p>

        <div class="stats-container">
            <div class="stat-card blue">
                <h4>Total Products</h4>
                <div class="number"><?php echo number_format($stats['total_products'] ?? 0); ?></div>
            </div>
            <div class="stat-card green">
                <h4>Total Stock</h4>
                <div class="number"><?php echo number_format($stats['total_stock'] ?? 0); ?></div>
            </div>
            <div class="stat-card orange">
                <h4>Total Value (VND)</h4>
                <div class="number"><?php echo number_format($stats['total_value'] ?? 0); ?></div>
            </div>
            <div class="stat-card red">
                <h4>Low Stock (< 5)</h4>
                <div class="number" style="color: #F55C5C;"><?php echo number_format($stats['low_stock'] ?? 0); ?></div>
            </div>
        </div>

        <div class="form-container" style="max-width: none; box-shadow: none; padding: 0; margin: 0;">
            <h3><?php echo $is_editing ? 'Update Product' : 'Add New Product'; ?></h3>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo html_safe($message); ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="note_id" value="<?php echo html_safe($edit_note_id); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <?php if ($is_editing): ?>
                            <input type="hidden" name="sku" value="<?php echo html_safe($edit_sku); ?>">
                        <?php endif; ?>

                        <input type="text" id="sku" name="sku" placeholder="e.g., NIKE-AF1-001" 
                                value="<?php echo html_safe($edit_sku); ?>" required 
                                <?php echo $is_editing ? 'disabled' : ''; ?>> <?php if ($is_editing): ?>
                            <small>SKU cannot be edited after creation.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Air Force 1 '07" 
                               value="<?php echo html_safe($edit_name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" placeholder="e.g., Nike" 
                               value="<?php echo html_safe($edit_brand); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="size">Size</label>
                        <input type="number" id="size" name="size" placeholder="e.g., 41" 
                               value="<?php echo html_safe($edit_size); ?>" required min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Stock Quantity</label>
                        <input type="number" id="quantity" name="quantity" placeholder="e.g., 10" 
                               value="<?php echo html_safe($edit_quantity); ?>" required min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (VND)</label>
                        <input type="number" id="price" name="price" placeholder="e.g., 3000000" 
                               value="<?php echo html_safe($edit_price); ?>" required min="0" step="1000">
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*" style="padding: 10px; background: white;">
                        <?php if ($is_editing && !empty($edit_image) && $edit_image != 'no-image.png'): ?>
                            <small>Current image: <?php echo html_safe($edit_image); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" name="save_shoe">
                    <?php echo $is_editing ? 'Update Product' : 'Add to Inventory'; ?>
                </button>
                
                <?php if ($is_editing): ?>
                    <a href="index.php" class="btn-cancel">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="search-bar" style="margin-top: 40px;">
            <form action="index.php" method="GET" style="display: flex; width: 100%; gap: 10px;">
                <input type="text" name="search" placeholder="Search by Name, Brand, or SKU..." value="<?php echo html_safe($search_term); ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="index.php" class="clear-button">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th> <th>SKU</th>
                        <th>Product Name</th>
                        <th>Brand</th>
                        <th>Size</th>
                        <th>Quantity</th>
                        <th>Price (VND)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shoes_list)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;"> <?php if (!empty($search_term)): ?>
                                    No products found matching "<?php echo html_safe($search_term); ?>".
                                <?php else: ?>
                                    No products in inventory yet.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($shoes_list as $shoe): ?>
                            <tr>
                                <td>
                                    <?php 
                                        // Kiểm tra nếu ảnh tồn tại trong thư mục uploads, nếu không dùng no-image.png
                                        $img_name = !empty($shoe['image']) ? $shoe['image'] : 'no-image.png';
                                        $img_url = "uploads/" . $img_name;
                                        
                                        // Xử lý trường hợp file trong DB có nhưng file thực tế bị xóa
                                        if (!file_exists($img_url)) {
                                            $img_url = "uploads/no-image.png"; // Bạn nên tạo sẵn file này hoặc để trống
                                        }
                                    ?>
                                    <img src="<?php echo html_safe($img_url); ?>" class="shoe-img-thumb" alt="Shoe" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                </td>

                                <td><?php echo html_safe($shoe['sku']); ?></td>
                                
                                <td style="font-weight: 600; color: var(--primary-color);">
                                    <?php echo html_safe($shoe['name']); ?>
                                </td>
                                
                                <td><?php echo html_safe($shoe['brand']); ?></td>
                                <td><?php echo html_safe($shoe['size']); ?></td>
                                
                                <td style="<?php echo ($shoe['quantity'] < 5) ? 'color: red; font-weight: bold;' : ''; ?>">
                                    <?php echo html_safe($shoe['quantity']); ?>
                                </td>
                                
                                <td><?php echo number_format($shoe['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="index.php?edit=<?php echo $shoe['id']; ?>" class="btn-edit">Edit</a>
                                        <a href="index.php?delete=<?php echo $shoe['id']; ?>" class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this product? (SKU: <?php echo html_safe($shoe['sku']); ?>)');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div> </body>
</html>
