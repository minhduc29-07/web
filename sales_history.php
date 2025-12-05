<?php
require_once 'db.php';
check_login();

// Kiểm tra xem có ngày nào được truyền vào không?
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// 1. LẤY BÁO CÁO DOANH THU (Chỉ hiện khi KHÔNG lọc theo ngày)
$report = null;
if (empty($filter_date)) {
    $sql_report = "SELECT 
                    DATE(sale_date) as report_date, 
                    SUM(total_price) as daily_revenue, 
                    SUM(quantity) as items_sold 
                   FROM sales 
                   GROUP BY DATE(sale_date) 
                   ORDER BY report_date DESC";
    $report = $conn->query($sql_report);
}

// 2. LẤY CHI TIẾT GIAO DỊCH (Có lọc theo ngày nếu cần)
$sql_history = "SELECT s.*, u.username 
                FROM sales s 
                JOIN users u ON s.user_id = u.id ";

if (!empty($filter_date)) {
    // Nếu có ngày, thêm điều kiện WHERE
    $sql_history .= " WHERE DATE(s.sale_date) = '$filter_date' ";
}

$sql_history .= " ORDER BY s.sale_date DESC"; // Sắp xếp mới nhất lên đầu
$history = $conn->query($sql_history);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales History</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin: 40px 0 20px 0; color: #333; }
        .filter-header {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-back {
            text-decoration: none;
            background: white;
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            color: #333;
            font-weight: bold;
        }
        .btn-back:hover { background: #f0f0f0; }
    </style>
</head>
<body>
    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <?php if (!empty($filter_date)): ?>
            <div class="filter-header">
                <div>
                    <h2 style="margin:0; color: var(--primary-color);">
                        <i class="fas fa-calendar-alt"></i> 
                        Transactions for: <?php echo date("d/m/Y", strtotime($filter_date)); ?>
                    </h2>
                    <p style="margin: 5px 0 0 0; color: #666;">Showing all orders created on this date.</p>
                </div>
                <a href="report.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Report</a>
            </div>

        <?php else: ?>
            <h2 class="section-title">Daily Revenue Summary</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr style="background-color: #f0f8ff;">
                            <th>Date</th>
                            <th>Items Sold</th>
                            <th>Total Revenue (VND)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report && $report->num_rows > 0): ?>
                            <?php while($row = $report->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:bold;">
                                    <?php echo date("d/m/Y", strtotime($row['report_date'])); ?>
                                </td>
                                <td><?php echo $row['items_sold']; ?> pairs</td>
                                <td style="color: #28a745; font-weight: bold;">
                                    <?php echo number_format($row['daily_revenue']); ?>
                                </td>
                                <td>
                                    <a href="sales_history.php?date=<?php echo $row['report_date']; ?>" style="color:blue;">View Details</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center">No sales data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <h2 class="section-title">All Transactions</h2>
        <?php endif; ?>


        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>Staff</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows > 0): ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo date("H:i", strtotime($row['sale_date'])); ?> <small style="color:#999"><?php echo date("d/m", strtotime($row['sale_date'])); ?></small></td>
                            <td><?php echo html_safe($row['username']); ?></td>
                            <td><?php echo html_safe($row['product_name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td style="font-weight:bold;"><?php echo number_format($row['total_price']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
