<?php
require_once 'db.php';
check_login();

// --- 1. TÍNH DOANH THU HÔM NAY ---
$today = date('Y-m-d');
$sql_today = "SELECT SUM(total_price) as today_revenue FROM sales WHERE DATE(sale_date) = '$today'";
$result_today = $conn->query($sql_today)->fetch_assoc();
$revenue_today = $result_today['today_revenue'] ?? 0;

// --- 2. TÍNH DOANH THU THEO NGÀY ---
$sql_daily = "SELECT 
                DATE(sale_date) as report_date, 
                SUM(total_price) as daily_revenue, 
                COUNT(*) as total_orders,
                SUM(quantity) as total_items
              FROM sales 
              GROUP BY DATE(sale_date) 
              ORDER BY report_date DESC";
$result_daily = $conn->query($sql_daily);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .report-header { display: flex; gap: 20px; margin-bottom: 30px; }
        .summary-card {
            flex: 1;
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
            text-align: center;
        }
        .summary-card h3 { margin: 0; font-size: 1rem; opacity: 0.9; text-transform: uppercase; }
        .summary-card .money { font-size: 2.5rem; font-weight: bold; margin-top: 10px; }
        
        .report-table th { background-color: #f8f9fa; }
        .report-table tr:hover { background-color: #f1f1f1; }
        .high-revenue { color: #28a745; font-weight: bold; }
        
        /* CSS cho nút xem chi tiết */
        .btn-view-detail {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .btn-view-detail:hover {
            background-color: #e6f7ff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <div class="report-header">
            <div class="summary-card">
                <h3><i class="fas fa-calendar-day"></i> Today's Revenue</h3>
                <div class="money"><?php echo number_format($revenue_today); ?> ₫</div>
                <small><?php echo date("d/m/Y"); ?></small>
            </div>
        </div>

        <h3><i class="fas fa-chart-line"></i> Daily Revenue History</h3>
        
        <div class="table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date (Click to view)</th>
                        <th>Total Orders</th>
                        <th>Items Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_daily->num_rows > 0): ?>
                        <?php while($row = $result_daily->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="sales_history.php?date=<?php echo $row['report_date']; ?>" class="btn-view-detail">
                                        <i class="far fa-eye"></i> 
                                        <?php echo date("d/m/Y", strtotime($row['report_date'])); ?>
                                    </a>
                                    
                                    <?php if($row['report_date'] == $today) echo '<span style="color:red; font-size:0.8rem;">(Today)</span>'; ?>
                                </td>
                                <td><?php echo $row['total_orders']; ?> orders</td>
                                <td><?php echo $row['total_items']; ?> pairs</td>
                                <td class="high-revenue">
                                    <?php echo number_format($row['daily_revenue']); ?> ₫
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center">No sales data recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
