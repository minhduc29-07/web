<?php
require_once 'db.php';
check_login();

// --- LOGIC PHP GIỎ HÀNG ---
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$message = ''; $message_type = '';

if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['product_id']; 
    $buy_qty = (int)$_POST['buy_qty'];

    if ($id == 0) {
        $message = "Please select a size!"; $message_type = "error";
    } elseif ($buy_qty <= 0) {
        $message = "Quantity > 0"; $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT * FROM shoes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $name_with_size = $product['name'] . " (Size: " . $product['size'] . ")";
            $max_qty = $product['quantity'];
            
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    if (($item['qty'] + $buy_qty) <= $max_qty) {
                        $item['qty'] += $buy_qty;
                    } else {
                        $message = "Stock limit reached for Size " . $product['size']; $message_type = "error";
                    }
                    $found = true; break;
                }
            }
            if (!$found) {
                if ($buy_qty <= $max_qty) {
                    $_SESSION['cart'][] = [
                        'id'=>$id, 
                        'name'=>$name_with_size,
                        'price'=>$product['price'], 
                        'qty'=>$buy_qty, 
                        'max_qty'=>$max_qty
                    ];
                } else {
                    $message = "Not enough stock!"; $message_type = "error";
                }
            }
        }
    }
}

if (isset($_GET['remove'])) {
    $index = $_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user_id'];
        $conn->begin_transaction();
        try {
            foreach ($_SESSION['cart'] as $item) {
                $pid = $item['id']; $qty = $item['qty']; $total = $item['price'] * $qty; $pname = $item['name'];
                $conn->query("UPDATE shoes SET quantity = quantity - $qty WHERE id = $pid");
                $stmt = $conn->prepare("INSERT INTO sales (user_id, product_name, quantity, total_price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isid", $user_id, $pname, $qty, $total);
                $stmt->execute();
            }
            $conn->commit();
            $_SESSION['cart'] = [];
            $message = "Payment Successful!"; $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage(); $message_type = "error";
        }
    } else { $message = "Cart is empty!"; $message_type = "error"; }
}

// --- LOGIC GOM NHÓM SẢN PHẨM ---
$raw_data = $conn->query("SELECT * FROM shoes WHERE quantity > 0 ORDER BY name ASC, size ASC");
$grouped_products = [];

while($row = $raw_data->fetch_assoc()) {
    $name = $row['name'];
    if (!isset($grouped_products[$name])) {
        $grouped_products[$name] = [
            'image' => $row['image'],
            'price' => $row['price'],
            'variants' => []
        ];
    }
    $grouped_products[$name]['variants'][] = [
        'id' => $row['id'],
        'size' => $row['size'],
        'quantity' => $row['quantity']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .size-select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #f9f9f9;
            font-weight: bold;
            cursor: pointer;
        }
        /* CSS cho Thanh Tìm Kiếm Mới */
        .search-container {
            margin-bottom: 20px;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Padding trái chừa chỗ cho icon */
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box; /* Quan trọng để không bị vỡ layout */
        }
        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }
    </style>
</head>
<body style="height: 100vh; overflow: hidden;">

    <?php require_once 'navigation.php'; ?>

    <div class="container" style="max-width: 100%; margin: 10px auto; height: 100%;">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>" style="margin-bottom: 10px;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="pos-layout">
            
            <div class="pos-products-area">
                
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="posSearch" class="search-input" placeholder="Search product name..." autocomplete="off">
                </div>

                <div class="pos-grid" id="productGrid">
                    <?php foreach($grouped_products as $prod_name => $prod_data): ?>
                        <div class="pos-item" data-name="<?php echo strtolower(html_safe($prod_name)); ?>">
                            <form method="POST" style="height: 100%; display: flex; flex-direction: column;">
                                
                                <?php 
                                    $img = !empty($prod_data['image']) ? "uploads/".$prod_data['image'] : "uploads/no-image.png";
                                    if (!file_exists($img)) $img = "uploads/no-image.png";
                                ?>
                                <img src="<?php echo $img; ?>" alt="Product">
                                
                                <div class="pos-item-content">
                                    <h4><?php echo html_safe($prod_name); ?></h4>
                                    <div class="price"><?php echo number_format($prod_data['price']); ?> ₫</div>
                                    
                                    <label style="font-size: 0.8rem; color: #666;">Select Size:</label>
                                    <select name="product_id" class="size-select" required>
                                        <option value="0">-- Choose Size --</option>
                                        <?php foreach($prod_data['variants'] as $variant): ?>
                                            <option value="<?php echo $variant['id']; ?>">
                                                Size <?php echo $variant['size']; ?> (Stock: <?php echo $variant['quantity']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <div class="qty-control">
                                        <input type="number" name="buy_qty" value="1" min="1" style="width: 60px;">
                                        <button type="submit" name="add_to_cart" class="btn-add-pos">
                                            <i class="fas fa-cart-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="noResults" style="display:none; text-align:center; padding: 20px; color: #888;">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No products found.</p>
                </div>
            </div>

            <div class="pos-cart-area">
                <div class="cart-header">
                    <h3 style="margin:0;"><i class="fas fa-shopping-cart"></i> Current Order</h3>
                </div>

                <div class="cart-body">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div style="text-align: center; color: #999; margin-top: 50px;">
                            <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>Cart is empty</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $grand_total = 0;
                        foreach ($_SESSION['cart'] as $index => $item): 
                            $line_total = $item['price'] * $item['qty'];
                            $grand_total += $line_total;
                        ?>
                            <div class="cart-row">
                                <div class="cart-item-info">
                                    <b><?php echo html_safe($item['name']); ?></b>
                                    <span><?php echo number_format($item['price']); ?> x <strong><?php echo $item['qty']; ?></strong></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: 600;"><?php echo number_format($line_total); ?></span>
                                    <a href="pos.php?remove=<?php echo $index; ?>" class="btn-remove-item">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span>Total:</span>
                        <span style="color: var(--primary-color);"><?php echo number_format($grand_total ?? 0); ?> VND</span>
                    </div>
                    <?php if (!empty($_SESSION['cart'])): ?>
                    <form method="POST">
                        <button type="submit" name="checkout" class="btn-pay" onclick="return confirm('Confirm payment?');">
                            <i class="fas fa-money-bill-wave"></i> PAY NOW
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('posSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.pos-item');
            let hasResults = false;

            items.forEach(function(item) {
                let name = item.getAttribute('data-name');
                if (name.includes(filter)) {
                    item.style.display = ""; // Hiện
                    hasResults = true;
                } else {
                    item.style.display = "none"; // Ẩn
                }
            });

            // Hiển thị thông báo nếu không có kết quả
            let noResDiv = document.getElementById('noResults');
            if (!hasResults) {
                noResDiv.style.display = "block";
            } else {
                noResDiv.style.display = "none";
            }
        });
    </script>
</body>
</html>
