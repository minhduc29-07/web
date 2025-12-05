<?php
require_once 'db.php';
check_login();

// 1. LẤY BÁO CÁO DOANH THU THEO NGÀY
// Gom nhóm theo ngày (Ngày, Tổng tiền, Tổng số lượng giày bán ra)
$sql_report = "SELECT 
                DATE(sale_date) as report_date, 
                SUM(total_price) as daily_revenue, 
                SUM(quantity) as items_sold 
               FROM sales 
               GROUP BY DATE(sale_date) 
               ORDER BY report_date DESC";
$report = $conn->query($sql_report);

// 2. LẤY CHI TIẾT LỊCH SỬ GIAO DỊCH (Như cũ)
$sql_history = "SELECT s.*, u.username 
                FROM sales s 
                JOIN users u ON s.user_id = u.id 
                ORDER BY s.sale_date DESC";
$history = $conn->query($sql_history);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales History & Revenue</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .report-card {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .report-card h3 { color: white; margin-top: 0; }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin: 40px 0 20px 0; color: #333; }
    </style>
</head>
<body>
    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <h2 class="section-title">Daily Revenue Report</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr style="background-color: #f0f8ff;">
                        <th>Date</th>
                        <th>Items Sold</th>
                        <th>Total Revenue (VND)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($report->num_rows > 0): ?>
                        <?php while($row = $report->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:bold;">
                                <?php echo date("d/m/Y", strtotime($row['report_date'])); ?>
                            </td>
                            <td><?php echo $row['items_sold']; ?> pairs</td>
                            <td style="color: #28a745; font-weight: bold; font-size: 1.1rem;">
                                <?php echo number_format($row['daily_revenue']); ?>
                            </td>
                            <td><span style="background:#e6ffed; color:#28a745; padding:5px 10px; border-radius:15px; font-size:0.8rem;">Completed</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center">No sales data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="section-title">Detailed Transaction History</h2>
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
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo date("H:i - d/m/Y", strtotime($row['sale_date'])); ?></td>
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
