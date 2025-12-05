<?php
require_once 'db.php';
check_login(); // Chỉ nhân viên mới được vào

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$message_type = '';

// --- XỬ LÝ: THÊM VÀO GIỎ ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $max_qty = $_POST['max_qty'];

    // Kiểm tra xem sản phẩm đã có trong giỏ chưa
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $id) {
            if ($item['qty'] < $max_qty) {
                $item['qty']++;
            } else {
                $message = "Cannot add more. Stock limit reached!";
                $message_type = "error";
            }
            $found = true;
            break;
        }
    }
    // Nếu chưa có thì thêm mới
    if (!$found) {
        if ($max_qty > 0) {
            $_SESSION['cart'][] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'qty' => 1,
                'max_qty' => $max_qty
            ];
        } else {
            $message = "Product is out of stock!";
            $message_type = "error";
        }
    }
}

// --- XỬ LÝ: XÓA KHỎI GIỎ ---
if (isset($_GET['remove'])) {
    $index = $_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Sắp xếp lại mảng
    }
}

// --- XỬ LÝ: THANH TOÁN (CHECKOUT) ---
if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user_id'];
        $conn->begin_transaction(); // Bắt đầu giao dịch an toàn

        try {
            foreach ($_SESSION['cart'] as $item) {
                $pid = $item['id'];
                $qty = $item['qty'];
                $total = $item['price'] * $qty;
                $pname = $item['name'];

                // 1. Trừ kho
                $conn->query("UPDATE shoes SET quantity = quantity - $qty WHERE id = $pid");
                
                // 2. Lưu lịch sử bán hàng
                $stmt = $conn->prepare("INSERT INTO sales (user_id, product_name, quantity, total_price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isid", $user_id, $pname, $qty, $total);
                $stmt->execute();
            }

            $conn->commit(); // Lưu tất cả thay đổi
            $_SESSION['cart'] = []; // Xóa giỏ hàng
            $message = "Sale recorded successfully!";
            $message_type = "success";

        } catch (Exception $e) {
            $conn->rollback(); // Nếu lỗi thì hoàn tác tất cả
            $message = "Error during checkout: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Cart is empty!";
        $message_type = "error";
    }
}

// Lấy danh sách sản phẩm để hiển thị bên trái
$products = $conn->query("SELECT * FROM shoes WHERE quantity > 0 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS - Sales Counter</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* CSS Riêng cho trang POS */
        .pos-container { display: flex; gap: 20px; }
        .product-list { flex: 2; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
        .cart-panel { flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: fit-content; }
        
        .pos-card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 10px; text-align: center; transition: all 0.2s; }
        .pos-card:hover { transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.1); border-color: var(--primary-color); cursor: pointer; }
        .pos-card img { width: 80px; height: 80px; object-fit: cover; margin-bottom: 10px; }
        .pos-card h4 { font-size: 0.9rem; margin: 5px 0; height: 40px; overflow: hidden; }
        .pos-card .price { color: var(--primary-color); font-weight: bold; }
        .pos-card button { width: 100%; margin-top: 5px; padding: 8px; font-size: 0.8rem; }

        .cart-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 10px 0; }
        .cart-total { margin-top: 20px; font-size: 1.2rem; font-weight: bold; text-align: right; color: var(--primary-color); }
        .btn-remove { color: red; text-decoration: none; font-weight: bold; margin-left: 10px; }
        .btn-checkout { width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 8px; font-size: 1.1rem; margin-top: 20px; cursor: pointer; }
        .btn-checkout:hover { background: #218838; }
    </style>
</head>
<body>
    <?php require_once 'navigation.php'; ?>

    <div class="container">
        <h2>Sales Counter (POS)</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="pos-container">
            <div class="product-list">
                <?php while($row = $products->fetch_assoc()): ?>
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="name" value="<?php echo $row['name']; ?>">
                        <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                        <input type="hidden" name="max_qty" value="<?php echo $row['quantity']; ?>">
                        
                        <div class="pos-card" onclick="this.parentNode.submit();"> <?php 
                                $img = !empty($row['image']) ? "uploads/".$row['image'] : "uploads/no-image.png";
                                if (!file_exists($img)) $img = "uploads/no-image.png";
                            ?>
                            <img src="<?php echo $img; ?>" alt="Shoe">
                            <h4><?php echo html_safe($row['name']); ?></h4>
                            <div class="price"><?php echo number_format($row['price']); ?></div>
                            <small>Stock: <?php echo $row['quantity']; ?></small>
                            <input type="hidden" name="add_to_cart" value="1">
                        </div>
                    </form>
                <?php endwhile; ?>
            </div>

            <div class="cart-panel">
                <h3>Current Order</h3>
                <?php if (empty($_SESSION['cart'])): ?>
                    <p style="color: #999; text-align: center;">Cart is empty.</p>
                <?php else: ?>
                    <div class="cart-items">
                        <?php 
                        $grand_total = 0;
                        foreach ($_SESSION['cart'] as $index => $item): 
                            $line_total = $item['price'] * $item['qty'];
                            $grand_total += $line_total;
                        ?>
                            <div class="cart-item">
                                <div>
                                    <b><?php echo html_safe($item['name']); ?></b><br>
                                    <small><?php echo number_format($item['price']); ?> x <?php echo $item['qty']; ?></small>
                                </div>
                                <div>
                                    <?php echo number_format($line_total); ?>
                                    <a href="pos.php?remove=<?php echo $index; ?>" class="btn-remove">X</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-total">
                        Total: <?php echo number_format($grand_total); ?> VND
                    </div>

                    <form method="POST">
                        <button type="submit" name="checkout" class="btn-checkout" onclick="return confirm('Confirm payment?');">
                            PAY & SAVE
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>