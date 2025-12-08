<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <a href="pos.php" class="nav-brand">
        <img src="logo.png" alt="Logo">
        Shoe Store POS
    </a>
    
    <ul class="nav-links">
        <li>
            <a href="pos.php" class="<?php echo ($current_page == 'pos.php') ? 'active' : ''; ?>" style="color: #28a745; font-weight: bold;">
                POS (Sell)
            </a>
        </li>
        <li>
            <a href="report.php" class="<?php echo ($current_page == 'report.php') ? 'active' : ''; ?>" style="color: #F7B84B;">
                Revenue Report
            </a>
        </li>
        <li>
            <a href="sales_history.php" class="<?php echo ($current_page == 'sales_history.php') ? 'active' : ''; ?>">
                Sales History
            </a>
        </li>

        <li>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                Warehouse (Import)
            </a>
        </li>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="manage_users.php" class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                    User Management
                </a>
            </li>
        <?php endif; ?>
        
        <li>
            <a href="logout.php" class="logout-link">
                Logout (<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>)
            </a>
        </li>
    </ul>
</nav>
