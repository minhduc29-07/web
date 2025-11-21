<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <a href="index.php" class="nav-brand">
        <img src="logo.png" alt="Logo">
        Shoe Inventory <!-- Translated -->
    </a>
    <ul class="nav-links">
        <li>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                Product Management <!-- Translated -->
            </a>
        </li>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="manage_users.php" class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                    User Management <!-- Translated -->
                </a>
            </li>
        <?php endif; ?>
        
        <li>
            <a href="logout.php" class="logout-link">
                Logout (<?php echo html_safe($_SESSION['username'] ?? 'User'); ?>) <!-- Translated -->
            </a>
        </li>
    </ul>
</nav>