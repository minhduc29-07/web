<?php
require_once 'db.php';

check_login();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$message = ''; 
$message_type = ''; 

$edit_note_id = '';
$edit_sku = '';
$edit_name = '';
$edit_brand = '';
$edit_size = '';
$edit_quantity = '';
$edit_price = '';
$is_editing = false; 

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'admin_only') {
        $message = "Error: You do not have permission to access the User Management page."; // Translated
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_shoe'])) {
    
    $sku = $conn->real_escape_string($_POST['sku']);
    $name = $conn->real_escape_string($_POST['name']);
    $brand = $conn->real_escape_string($_POST['brand']);
    $size = (int)$_POST['size'];
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $note_id = $conn->real_escape_string($_POST['note_id']); 


    $edit_sku = $sku;
    $edit_name = $name;
    $edit_brand = $brand;
    $edit_size = $size;
    $edit_quantity = $quantity;
    $edit_price = $price;

    if (empty($sku) || empty($name) || empty($brand) || $size <= 0 || $quantity < 0 || $price <= 0) {
        $message = "Error: Please fill in all fields correctly."; // Translated
        $message_type = 'error';
    } else {
        
        try {
            if (!empty($note_id)) {
                $stmt = $conn->prepare("UPDATE shoes SET sku = ?, name = ?, brand = ?, size = ?, quantity = ?, price = ? WHERE id = ?");
                $stmt->bind_param("sssiidi", $sku, $name, $brand, $size, $quantity, $price, $note_id);
                
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "Product updated successfully!"; // Translated
                    $message_type = 'success';
                } else {
                    $message = "No changes made or error during update."; // Translated
                    $message_type = 'info';
                }

            } else {
                $stmt = $conn->prepare("INSERT INTO shoes (sku, name, brand, size, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiid", $sku, $name, $brand, $size, $quantity, $price);
                
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "New product added successfully!"; // Translated
                    $message_type = 'success';
                    // Clear edit variables after successful add
                    $edit_sku = $edit_name = $edit_brand = '';
                    $edit_size = $edit_quantity = $edit_price = 0;
                } else {
                     $message = "Error: Command executed but no rows were added."; // Translated
                     $message_type = 'error';
                }
            }
            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { 
                $message = "Error: SKU '" . html_safe($sku) . "' already exists. Please choose another SKU."; // Translated
                $message_type = 'error';
                $is_editing = !empty($note_id); 
            } else {
                $message = "Database Error: " . $e->getMessage(); // Translated
                $message_type = 'error';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $note_id_to_delete = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM shoes WHERE id = ?");
    $stmt->bind_param("i", $note_id_to_delete);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Product deleted successfully."; // Translated
        $message_type = 'success';
    } else {
        $message = "Error deleting product or product not found."; // Translated
        $message_type = 'error';
    }
    $stmt->close();
}

if (isset($_GET['edit'])) {
    $note_id_to_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT sku, name, brand, size, quantity, price FROM shoes WHERE id = ?");
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
        $is_editing = true;
    }
    $stmt->close();
}

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
    <title>Shoe Inventory Management</title> <!-- Translated -->
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>"> <!-- Cache-busting -->
</head>
<body>

    <?php require_once 'navigation.php'; ?>

    <div class="container">
        
        <h2 style="font-weight: 600;">
            Welcome, <?php echo html_safe($username); ?>! <!-- Translated -->
        </h2>
        <p style="color: #666; margin-top: -10px; margin-bottom: 30px;">
            Have a productive day. 
        </p>

        <div class="form-container" style="max-width: none; box-shadow: none; padding: 0; margin: 0;">
            <h3><?php echo $is_editing ? 'Update Product' : 'Add New Product'; ?></h3> <!-- Translated -->
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo html_safe($message); ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="note_id" value="<?php echo html_safe($edit_note_id); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="sku">SKU</label> <!-- Translated -->
                        <input type="text" id="sku" name="sku" placeholder="e.g., NIKE-AF1-001" 
                               value="<?php echo html_safe($edit_sku); ?>" required 
                               <?php echo $is_editing ? 'disabled' : ''; // Lock SKU on Edit ?>>
                        <?php if ($is_editing): ?>
                            <small>SKU cannot be edited after creation.</small> <!-- Translated -->
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Product Name</label> <!-- Translated -->
                        <input type="text" id="name" name="name" placeholder="e.g., Air Force 1 '07" 
                               value="<?php echo html_safe($edit_name); ?>" required> <!-- Translated -->
                    </div>
                    
                    <div class="form-group">
                        <label for="brand">Brand</label> <!-- Translated -->
                        <input type="text" id="brand" name="brand" placeholder="e.g., Nike" 
                               value="<?php echo html_safe($edit_brand); ?>" required> <!-- Translated -->
                    </div>
                    
                    <div class="form-group">
                        <label for="size">Size</label> <!-- Translated -->
                        <input type="number" id="size" name="size" placeholder="e.g., 41" 
                               value="<?php echo html_safe($edit_size); ?>" required min="0"> <!-- Translated -->
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Stock Quantity</label> <!-- Translated -->
                        <input type="number" id="quantity" name="quantity" placeholder="e.g., 10" 
                               value="<?php echo html_safe($edit_quantity); ?>" required min="0"> <!-- Translated -->
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (VND)</label> <!-- Translated -->
                        <input type="number" id="price" name="price" placeholder="e.g., 3000000" 
                               value="<?php echo html_safe($edit_price); ?>" required min="0" step="1000"> <!-- Translated -->
                    </div>
                </div>

                <button type="submit" name="save_shoe">
                    <?php echo $is_editing ? 'Update Product' : 'Add to Inventory'; ?> <!-- Translated -->
                </button>
                
                <?php if ($is_editing): ?>
                    <a href="index.php" class="btn-cancel">Cancel Edit</a> <!-- Translated -->
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Bar -->
        <div class="search-bar" style="margin-top: 40px;">
            <form action="index.php" method="GET" style="display: flex; width: 100%; gap: 10px;">
                <input type="text" name="search" placeholder="Search by Name, Brand, or SKU..." value="<?php echo html_safe($search_term); ?>"> <!-- Translated -->
                <button type="submit">Search</button> <!-- Translated -->
                <?php if (!empty($search_term)): ?>
                    <a href="index.php" class="clear-button">Clear Search</a> <!-- Translated -->
                <?php endif; ?>
            </form>
        </div>

        <!-- Display Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>id</th>
                        <th>SKU</th>
                        <th>Product Name</th> <!-- Translated -->
                        <th>Brand</th> <!-- Translated -->
                        <th>Size</th> <!-- Translated -->
                        <th>Quantity</th> <!-- Translated -->
                        <th>Price (VND)</th> <!-- Translated -->
                        <th>Actions</th> <!-- Translated -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shoes_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">
                                <?php if (!empty($search_term)): ?>
                                    No products found matching "<?php echo html_safe($search_term); ?>". <!-- Translated -->
                                <?php else: ?>
                                    No products in inventory yet. <!-- Translated -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($shoes_list as $shoe): ?>
                            <tr>
                                <td><?php echo html_safe($shoe['id']); ?></td>
                                <td><?php echo html_safe($shoe['sku']); ?></td>
                                <td><?php echo html_safe($shoe['name']); ?></td>
                                <td><?php echo html_safe($shoe['brand']); ?></td>
                                <td><?php echo html_safe($shoe['size']); ?></td>
                                <td><?php echo html_safe($shoe['quantity']); ?></td>
                                <td><?php echo number_format($shoe['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="index.php?edit=<?php echo $shoe['id']; ?>" class="btn-edit">Edit</a> <!-- Translated -->
                                        <a href="index.php?delete=<?php echo $shoe['id']; ?>" class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this product? (SKU: <?php echo html_safe($shoe['sku']); ?>)');">Delete</a> <!-- Translated -->
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div> <!-- /container -->

</body>
</html>