<?php
require_once 'db.php';
check_login();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$message_type = '';

// --- XỬ LÝ: THÊM VÀO GIỎ (CẬP NHẬT: NHẬN SỐ LƯỢNG TÙY CHỌN) ---
if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['product_id'];
    $name = $_POST['name'];
    $price = (float)$_POST['price'];
    $cost_price = (float)$_POST['cost_price']; // Lấy giá vốn từ form ẩn
    $max_qty = (int)$_POST['max_qty'];
    $buy_qty = (int)$_POST['buy_qty']; // Lấy số lượng người dùng nhập

    if ($buy_qty <= 0) {
        $message = "Quantity must be at least 1.";
        $message_type = "error";
    } else {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                // Kiểm tra: Số trong giỏ + Số muốn mua thêm có vượt quá kho không?
                if (($item['qty'] + $buy_qty) <= $max_qty) {
                    $item['qty'] += $buy_qty;
                } else {
                    $message = "Cannot add $buy_qty more. Stock limit reached!";
                    $message_type = "error";
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            if ($buy_qty <= $max_qty) {
                $_SESSION['cart'][] = [
                    'id' => $id,
                    'name' => $name,
                    'price' => $price,
                    'cost_price' => $cost_price, // Lưu giá vốn vào session
                    'qty' => $buy_qty,
                    'max_qty' => $max_qty
                ];
            } else {
                $message = "Not enough stock!";
                $message_type = "error";
            }
        }
    }
}

// --- XỬ LÝ: XÓA GIỎ ---
if (isset($_GET['remove'])) {
    $index = $_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

// --- XỬ LÝ: THANH TOÁN ---
if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user_id'];
        $conn->begin_transaction();

        try {
            foreach ($_SESSION['cart'] as $item) {
                $pid = $item['id'];
                $qty = $item['qty'];
                $total = $item['price'] * $qty;
                $pname = $item['name'];
                $unit_cost = $item['cost_price']; // Lấy giá vốn đơn vị

                $conn->query("UPDATE shoes SET quantity = quantity - $qty WHERE id = $pid");
                
                $stmt = $conn->prepare("INSERT INTO sales (user_id, product_name, quantity, total_price, unit_cost_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isidd", $user_id, $pname, $qty, $total, $unit_cost);
                $stmt->execute();
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            $message = "Sale recorded successfully!";
            $message_type = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Cart is empty!";
        $message_type = "error";
    }
}

// CẬP NHẬT: Lấy thêm cột cost_price
$products = $conn->query("SELECT *, cost_price FROM shoes WHERE quantity > 0 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS System</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .pos-container { display: flex; gap: 20px; }
        .product-list { flex: 2; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .cart-panel { flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: fit-content; }
        
        .pos-card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 10px; text-align: center; }
        .pos-card img { width: 80px; height: 80px; object-fit: cover; margin-bottom: 10px; }
        .pos-card h4 { font-size: 0.9rem; margin: 5px 0; height: 40px; overflow: hidden; }
        
        /* Style cho ô nhập số lượng */
        .qty-input-group { display: flex; justify-content: center; gap: 5px; margin-top: 5px; }
        .qty-input-group input { width: 50px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 4px; }
        .btn-add { background: var(--primary-color); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-add:hover { background: var(--primary-dark); }
        
        .cart-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 10px 0; }
        .cart-total { margin-top: 20px; font-size: 1.2rem; font-weight: bold; text-align: right; color: var(--primary-color); }
        .btn-checkout { width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 8px; font-size: 1.1rem; margin-top: 20px; cursor: pointer; }
        
        /* CSS MỚI cho lợi nhuận gộp */
        .cart-profit { color: #28a745; font-size: 0.8rem; } 
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
                    <div class="pos-card">
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="name" value="<?php echo $row['name']; ?>">
                            <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                            <input type="hidden" name="cost_price" value="<?php echo $row['cost_price']; ?>"> <input type="hidden" name="max_qty" value="<?php echo $row['quantity']; ?>">
                            
                            <?php 
                                $img = !empty($row['image']) ? "uploads/".$row['image'] : "uploads/no-image.png";
                                if (!file_exists($img)) $img = "uploads/no-image.png";
                            ?>
                            <img src="<?php echo $img; ?>" alt="Shoe">
                            <h4><?php echo html_safe($row['name']); ?></h4>
                            <div style="font-weight:bold; color:#4A90E2;"><?php echo number_format($row['price']); ?></div>
                            <small>Stock: <?php echo $row['quantity']; ?></small>
                            
                            <div class="qty-input-group">
                                <input type="number" name="buy_qty" value="1" min="1" max="<?php echo $row['quantity']; ?>">
                                <button type="submit" name="add_to_cart" class="btn-add">Add</button>
                            </div>
                        </form>
                    </div>
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
                        $total_cost = 0;
                        $total_profit = 0;

                        foreach ($_SESSION['cart'] as $index => $item): 
                            $line_total = $item['price'] * $item['qty'];
                            $line_cost = $item['cost_price'] * $item['qty']; // Tính giá vốn theo dòng
                            $line_profit = $line_total - $line_cost; // Tính lợi nhuận theo dòng

                            $grand_total += $line_total;
                            $total_profit += $line_profit; // Cộng dồn lợi nhuận

                        ?>
                            <div class="cart-item">
                                <div>
                                    <b><?php echo html_safe($item['name']); ?></b><br>
                                    <small><?php echo number_format($item['price']); ?> x <strong><?php echo $item['qty']; ?></strong></small>
                                    <div class="cart-profit">Lãi gộp: <?php echo number_format($line_profit); ?></div> </div>
                                <div>
                                    <?php echo number_format($line_total); ?>
                                    <a href="pos.php?remove=<?php echo $index; ?>" style="color:red; margin-left:10px; text-decoration:none;">X</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-total" style="color:#28a745;">Gross Profit: <?php echo number_format($total_profit); ?> VND</div> <div class="cart-total">Total: <?php echo number_format($grand_total); ?> VND</div>
                    <form method="POST">
                        <button type="submit" name="checkout" class="btn-checkout" onclick="return confirm('Confirm payment?');">PAY & SAVE</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
