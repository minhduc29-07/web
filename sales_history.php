<?php
require_once 'db.php';
check_login();

// Lấy danh sách lịch sử bán hàng
$sql = "SELECT s.*, u.username 
        FROM sales s 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.sale_date DESC";
$history = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales History</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php require_once 'navigation.php'; ?>
    <div class="container">
        <h2>Sales History</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Staff</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total (VND)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['sale_date']; ?></td>
                        <td><?php echo html_safe($row['username']); ?></td>
                        <td><?php echo html_safe($row['product_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo number_format($row['total_price']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>