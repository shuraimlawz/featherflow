<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "FeatherFlow - Poultry Management & E-Commerce";
require_once 'backend/db.php';

$success_msg = '';
$error_msg = '';
$user_role = $_SESSION['user_role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Handle POST submissions directly in the index (Admin operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'Admin') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'log_yield') {
        $flockId = intval($_POST['flockId'] ?? 0);
        $goodEggs = intval($_POST['goodEggs'] ?? 0);
        $damagedEggs = intval($_POST['damagedEggs'] ?? 0);
        $logDate = $_POST['logDate'] ?? date('Y-m-d');
        
        if ($flockId > 0 && $goodEggs >= 0 && $damagedEggs >= 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO EggLogs (FlockID, LogDate, GoodQuantity, DamagedQuantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$flockId, $logDate, $goodEggs, $damagedEggs]);
                $success_msg = "Daily egg production parameters logged successfully.";
            } catch (PDOException $e) {
                $error_msg = "Failed to log egg yield: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please fill out all fields with valid values.";
        }
    }
    
    else if ($action === 'add_product') {
        $prodName  = trim($_POST['prodName']  ?? '');
        $prodCat   = trim($_POST['prodCat']   ?? '');
        $prodPrice = floatval($_POST['prodPrice'] ?? 0.00);
        $prodStock = intval($_POST['prodStock']   ?? 0);
        $prodDesc  = trim($_POST['prodDesc']  ?? '');

        // --- Handle image file upload ---
        $prodImage = 'images/fresh_eggs.png'; // default fallback
        if (!empty($_FILES['prodImageFile']['name'])) {
            $file      = $_FILES['prodImageFile'];
            $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize   = 5 * 1024 * 1024; // 5 MB
            $uploadDir = __DIR__ . '/images/';

            if (!in_array($file['type'], $allowed)) {
                $error_msg = 'Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $error_msg = 'Image file exceeds the 5 MB size limit.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error_msg = 'File upload error (code ' . $file['error'] . ').';
            } else {
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $filename = $safeName . '_' . time() . '.' . $ext;
                $dest     = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $prodImage = 'images/' . $filename;
                } else {
                    $error_msg = 'Failed to save the uploaded file. Check folder permissions on images/.';
                }
            }
        }

        if (empty($error_msg) && !empty($prodName) && !empty($prodCat) && $prodPrice >= 0 && $prodStock >= 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO Products (ProductName, Category, UnitPrice, StockQuantity, Description, ImageURL) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$prodName, $prodCat, $prodPrice, $prodStock, $prodDesc, $prodImage]);
                $success_msg = "Product '$prodName' has been pushed live in the catalog.";
            } catch (PDOException $e) {
                $error_msg = "Failed to add product: " . $e->getMessage();
            }
        } elseif (empty($error_msg)) {
            $error_msg = "Please provide valid product information.";
        }
    }

    
    else if ($action === 'add_flock') {
        $breed = trim($_POST['breed'] ?? '');
        $hatchDate = $_POST['hatchDate'] ?? date('Y-m-d');
        $count = intval($_POST['count'] ?? 0);
        $status = $_POST['status'] ?? 'Brooding';
        
        if (!empty($breed) && $count >= 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO Flocks (Breed, HatchDate, InitialCount, CurrentCount, Status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$breed, $hatchDate, $count, $count, $status]);
                $success_msg = "Flock batch registered successfully.";
            } catch (PDOException $e) {
                $error_msg = "Failed to register flock: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please fill in all flock details.";
        }
    }
    
    else if ($action === 'adjust_headcount') {
        $flockId = intval($_POST['adjustFlockId'] ?? 0);
        $changeAmount = intval($_POST['changeAmount'] ?? 0);
        $reason = $_POST['changeReason'] ?? '';
        
        if ($flockId > 0 && $changeAmount > 0) {
            try {
                $stmt = $pdo->prepare("SELECT CurrentCount, Breed FROM Flocks WHERE FlockID = ?");
                $stmt->execute([$flockId]);
                $flock = $stmt->fetch();
                
                if ($flock) {
                    $newCount = max(0, $flock['CurrentCount'] - $changeAmount);
                    $updateStmt = $pdo->prepare("UPDATE Flocks SET CurrentCount = ? WHERE FlockID = ?");
                    $updateStmt->execute([$newCount, $flockId]);
                    $success_msg = "Flock '" . $flock['Breed'] . "' count reduced by $changeAmount due to $reason. Current headcount: $newCount.";
                } else {
                    $error_msg = "Target flock not found.";
                }
            } catch (PDOException $e) {
                $error_msg = "Failed to adjust headcount: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please specify a valid reduction quantity.";
        }
    }
    
    else if ($action === 'update_order_status') {
        $orderId = intval($_POST['orderId'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($orderId > 0 && !empty($status)) {
            try {
                $stmt = $pdo->prepare("UPDATE Orders SET Status = ? WHERE OrderID = ?");
                $stmt->execute([$status, $orderId]);
                $success_msg = "Order #$orderId status updated to '$status'.";
            } catch (PDOException $e) {
                $error_msg = "Failed to update order status: " . $e->getMessage();
            }
        }
    }
}

// Fetch display data based on auth role
$products = [];
$customer_orders = [];
$customer_profile = null;

$totalActiveFlock = 0;
$yieldEfficiency = 0.0;
$pendingOrders = 0;
$flocks = [];
$admin_orders = [];
$eggLogs = [];

try {
    // Products table is needed for both customer and guest views (e-commerce showcase)
    $productsStmt = $pdo->query("SELECT * FROM Products ORDER BY Category, ProductName");
    $products = $productsStmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load catalog products: " . $e->getMessage();
}

if ($user_role === 'Customer') {
    try {
        // Fetch order history for the logged-in customer
        $orderStmt = $pdo->prepare("SELECT OrderID, OrderDate, TotalAmount, Status FROM Orders WHERE CustomerID = ? ORDER BY OrderDate DESC");
        $orderStmt->execute([$user_id]);
        $customer_orders = $orderStmt->fetchAll();
        
        // Fetch profile details
        $profileStmt = $pdo->prepare("SELECT Phone, Address, Email FROM Users WHERE UserID = ?");
        $profileStmt->execute([$user_id]);
        $customer_profile = $profileStmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Failed to load customer profile: " . $e->getMessage();
    }
} else if ($user_role === 'Admin') {
    try {
        // Total active flock headcount
        $flockCountStmt = $pdo->query("SELECT SUM(CurrentCount) as total FROM Flocks WHERE Status != 'Harvested'");
        $totalActiveFlock = intval($flockCountStmt->fetch()['total'] ?? 0);
        
        // Yield efficiency
        $yieldStmt = $pdo->query("SELECT SUM(GoodQuantity) as good, SUM(DamagedQuantity) as damaged FROM EggLogs");
        $yieldData = $yieldStmt->fetch();
        $goodEggs = floatval($yieldData['good'] ?? 0);
        $damagedEggs = floatval($yieldData['damaged'] ?? 0);
        $totalEggs = $goodEggs + $damagedEggs;
        $yieldEfficiency = ($totalEggs > 0) ? round(($goodEggs / $totalEggs) * 100, 1) : 0.0;
        
        // Pending orders
        $pendingStmt = $pdo->query("SELECT COUNT(*) as total FROM Orders WHERE Status = 'Pending'");
        $pendingOrders = intval($pendingStmt->fetch()['total'] ?? 0);
        
        // List of flocks
        $flocksStmt = $pdo->query("SELECT * FROM Flocks ORDER BY Status, FlockID");
        $flocks = $flocksStmt->fetchAll();
        
        // Recent orders
        $adminOrdersStmt = $pdo->query("
            SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, u.Name as CustomerName, u.Phone, u.Address 
            FROM Orders o 
            JOIN Users u ON o.CustomerID = u.UserID 
            ORDER BY o.OrderDate DESC
        ");
        $admin_orders = $adminOrdersStmt->fetchAll();
        
        // Recent egg logs
        $logsStmt = $pdo->query("
            SELECT e.LogID, e.LogDate, e.GoodQuantity, e.DamagedQuantity, f.Breed 
            FROM EggLogs e 
            JOIN Flocks f ON e.FlockID = f.FlockID 
            ORDER BY e.LogDate DESC LIMIT 10
        ");
        $eggLogs = $logsStmt->fetchAll();
    } catch (PDOException $e) {
        $error_msg = "Database query error: " . $e->getMessage();
    }
}

// Category unit mapping for displays
$unit_map = [
    'Eggs' => 'Per Crate',
    'Meat' => 'Unit kg',
    'Live' => 'Head Count',
    'Live Birds' => 'Head Count',
    'Fertilizer' => 'Per Bag'
];

include 'header.php';
?>

    <main class="container">
        
        <?php if (empty($user_role)): ?>
            <!-- ================= GUEST LANDING DASHBOARD ================= -->
            <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem;">
                <h2 style="color: var(--primary-color);">Welcome to FeatherFlow</h2>
                <p style="color: #718096; font-size: 0.95rem;">Experience high-grade organic agricultural operations and fresh farm e-commerce stocks.</p>
            </div>

            <!-- Lock Banner / Login Prompt -->
            <div class="alert-box alert-success" style="background-color: #ebf8ff; border-color: #bee3f8; color: #2b6cb0; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <span>💡 You are viewing the farm products catalog. **Sign in** to place items in your cart and complete purchases!</span>
                <button class="btn" onclick="openLoginModal(event)" style="padding: 0.4rem 1rem; font-size: 0.85rem;">Login / Register</button>
            </div>

            <!-- Search Options -->
            <div style="margin-bottom: 2rem; display: flex; flex-direction: column; gap: 0.5rem; max-width: 500px;">
                <label for="product-search" style="font-weight: 600; font-size: 0.9rem; color: var(--primary-color);">🔍 Search Catalog Stocks</label>
                <input type="text" id="product-search" placeholder="Type name or category (e.g. eggs, chicken)..." onkeyup="filterProducts()" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; width: 100%;">
            </div>

            <!-- E-Commerce Showcase for Guests -->
            <div class="product-grid" style="margin-top: 1rem;">
                <?php if (empty($products)): ?>
                    <p style="color:#718096; text-align: center; grid-column: 1/-1; padding: 2rem;">No items currently in farm inventory.</p>
                <?php else: ?>
                    <?php foreach ($products as $prod): ?>
                        <div class="product-card" data-id="<?php echo $prod['ProductID']; ?>" data-name="<?php echo htmlspecialchars($prod['ProductName']); ?>" data-price="<?php echo $prod['UnitPrice']; ?>" data-stock="<?php echo $prod['StockQuantity']; ?>">
                            <div style="height: 190px; overflow: hidden; background-color: #edf2f7; position: relative;">
                                <img src="<?php echo htmlspecialchars(!empty($prod['ImageURL']) ? $prod['ImageURL'] : 'images/fresh_eggs.png'); ?>" alt="<?php echo htmlspecialchars($prod['ProductName']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="product-info" style="padding: 1.25rem;">
                                <span class="product-category" style="font-size: 0.75rem;"><?php echo htmlspecialchars($prod['Category']); ?></span>
                                <h4 class="product-title" style="font-size: 1.25rem; margin: 0.25rem 0;"><?php echo htmlspecialchars($prod['ProductName']); ?></h4>
                                <p style="color:#718096; font-size:0.85rem; margin-bottom:0.75rem; min-height: 42px; line-height: 1.4;"><?php echo htmlspecialchars($prod['Description']); ?></p>
                                <div class="product-price" style="font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--primary-color);">
                                    $<?php echo number_format($prod['UnitPrice'], 2); ?> 
                                    <span style="font-size: 0.75rem; color:#718096; font-weight: normal;">/ <?php echo $unit_map[$prod['Category']] ?? 'Unit'; ?></span>
                                </div>
                                <div style="font-size: 0.8rem; margin-bottom: 0.75rem; font-weight: 600; color: <?php echo $prod['StockQuantity'] > 0 ? '#38a169' : '#e53e3e'; ?>;">
                                    <?php echo $prod['StockQuantity'] > 0 ? 'Stock: ' . $prod['StockQuantity'] . ' units' : 'Out of Stock'; ?>
                                </div>
                                <button class="btn add-to-cart-btn" style="width: 100%; padding: 0.6rem 1rem; font-size: 0.9rem;" <?php echo $prod['StockQuantity'] > 0 ? '' : 'disabled style="background-color: #a0aec0; cursor: not-allowed;"'; ?>>
                                    <?php echo $prod['StockQuantity'] > 0 ? 'Add to Basket' : 'Out of Stock'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($user_role === 'Customer'): ?>
            <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: var(--primary-color);">Welcome Back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                    <p style="color: #718096; font-size: 0.95rem;">Browse and order fresh farm products below.</p>
                </div>
                <div style="background-color: #f7fafc; padding: 0.5rem 1rem; border-radius: 20px; border: 1px solid var(--border-color); font-size: 0.85rem; font-weight: 600; color: var(--primary-color);">
                    👤 Customer Portal
                </div>
            </div>

            <!-- Search Bar -->
            <div style="margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; max-width: 500px;">
                <label for="product-search" style="font-weight: 600; font-size: 0.9rem; color: var(--primary-color);">🔍 Filter Stock Catalog</label>
                <input type="text" id="product-search" placeholder="Type product title or category name..." onkeyup="filterProducts()" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; width: 100%;">
            </div>

            <!-- Full-width Product Grid -->
            <div class="product-grid" style="margin-top: 1rem;">
                <?php if (empty($products)): ?>
                    <p style="color:#718096; grid-column: 1 / -1; text-align: center; padding: 2rem;">No products available.</p>
                <?php else: ?>
                    <?php foreach ($products as $prod): ?>
                        <div class="product-card" data-id="<?php echo $prod['ProductID']; ?>" data-name="<?php echo htmlspecialchars($prod['ProductName']); ?>" data-price="<?php echo $prod['UnitPrice']; ?>" data-stock="<?php echo $prod['StockQuantity']; ?>">
                            <div style="height: 190px; overflow: hidden; background-color: #edf2f7; position: relative;">
                                <img src="<?php echo htmlspecialchars(!empty($prod['ImageURL']) ? $prod['ImageURL'] : 'images/fresh_eggs.png'); ?>" alt="<?php echo htmlspecialchars($prod['ProductName']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="product-info" style="padding: 1.25rem;">
                                <span class="product-category" style="font-size: 0.75rem;"><?php echo htmlspecialchars($prod['Category']); ?></span>
                                <h4 class="product-title" style="font-size: 1.25rem; margin: 0.25rem 0;"><?php echo htmlspecialchars($prod['ProductName']); ?></h4>
                                <p style="color:#718096; font-size:0.85rem; margin-bottom:0.75rem; min-height: 42px; line-height: 1.4;"><?php echo htmlspecialchars($prod['Description']); ?></p>
                                <div class="product-price" style="font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--primary-color);">
                                    $<?php echo number_format($prod['UnitPrice'], 2); ?>
                                    <span style="font-size: 0.75rem; color:#718096; font-weight: normal;">/ <?php echo $unit_map[$prod['Category']] ?? 'Unit'; ?></span>
                                </div>
                                <div style="font-size: 0.8rem; margin-bottom: 0.75rem; font-weight: 600; color: <?php echo $prod['StockQuantity'] > 0 ? '#38a169' : '#e53e3e'; ?>;">
                                    <?php echo $prod['StockQuantity'] > 0 ? 'Stock: ' . $prod['StockQuantity'] . ' units' : 'Out of Stock'; ?>
                                </div>
                                <button class="btn add-to-cart-btn" style="width: 100%; padding: 0.6rem 1rem; font-size: 0.9rem;" <?php echo $prod['StockQuantity'] > 0 ? '' : 'disabled style="background-color: #a0aec0; cursor: not-allowed;"'; ?>>
                                    <?php echo $prod['StockQuantity'] > 0 ? 'Add to Basket' : 'Out of Stock'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>


        <?php elseif ($user_role === 'Admin'): ?>
            <!-- ================= ADMINISTRATOR PORTAL WITH SIDEBAR ================= -->
            <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: var(--primary-color);">Poultry Operations Dashboard</h2>
                    <p style="color: #718096; font-size: 0.95rem;">Logged in: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (System Administrator)</p>
                </div>
                <div style="background-color: #fffaf0; padding: 0.5rem 1rem; border-radius: 20px; border: 1px solid var(--secondary-color); font-size: 0.85rem; font-weight: 600; color: var(--secondary-color);">
                    🛠️ Admin Portal
                </div>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-box alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-box alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <!-- Sidebar Grid Layout -->
            <div class="admin-container">
                
                <!-- Left Sidebar Navigation Column -->
                <aside class="admin-sidebar">
                    <h3>Dashboard Nav</h3>
                    <ul>
                        <li><a href="#admin-stats" class="active-section" onclick="scrollToSection(event, 'admin-stats')">📈 System Overview</a></li>
                        <li><a href="#admin-yield" onclick="scrollToSection(event, 'admin-yield')">🥚 Log Daily Yield</a></li>
                        <li><a href="#admin-product" onclick="scrollToSection(event, 'admin-product')">🛍️ Add Catalog Product</a></li>
                        <li><a href="#admin-flock" onclick="scrollToSection(event, 'admin-flock')">🐔 Flock Management</a></li>
                        <li><a href="#admin-orders" onclick="scrollToSection(event, 'admin-orders')">📦 Web Orders</a></li>
                        <li><a href="#admin-catalog-grid" onclick="scrollToSection(event, 'admin-catalog-grid')">📋 Product Inventory</a></li>
                        <li><a href="#admin-logs" onclick="scrollToSection(event, 'admin-logs')">📊 Collection History</a></li>
                    </ul>
                </aside>

                <!-- Right Scrollable Content Column -->
                <div style="display: flex; flex-direction: column; gap: 3.5rem; width: 100%;">
                    
                    <!-- 1. Stats Cards -->
                    <section id="admin-stats" style="scroll-margin-top: 100px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">📈 Operations System Overview</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                            <div class="product-card" style="padding: 1.5rem; text-align: center; border-left: 5px solid var(--primary-color); background-color: var(--card-bg);">
                                <span style="color: #718096; font-weight: 600; font-size: 0.9rem;">TOTAL ACTIVE FLOCK</span>
                                <h3 style="font-size: 2rem; color: var(--primary-color); margin-top: 0.5rem;"><?php echo number_format($totalActiveFlock); ?> Birds</h3>
                            </div>
                            <div class="product-card" style="padding: 1.5rem; text-align: center; border-left: 5px solid var(--secondary-color); background-color: var(--card-bg);">
                                <span style="color: #718096; font-weight: 600; font-size: 0.9rem;">DAILY YIELD EFFICIENCY</span>
                                <h3 style="font-size: 2rem; color: var(--secondary-color); margin-top: 0.5rem;"><?php echo $yieldEfficiency; ?>%</h3>
                            </div>
                            <div class="product-card" style="padding: 1.5rem; text-align: center; border-left: 5px solid var(--accent-color); background-color: var(--card-bg);">
                                <span style="color: #718096; font-weight: 600; font-size: 0.9rem;">PENDING WEB ORDERS</span>
                                <h3 style="font-size: 2rem; color: var(--accent-color); margin-top: 0.5rem;"><?php echo $pendingOrders; ?> Orders</h3>
                            </div>
                        </div>
                    </section>

                    <!-- 2. Yield Forms & Catalog forms -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 2rem;">
                        
                        <!-- Form 1: Daily Egg Yield -->
                        <section id="admin-yield" class="form-box" style="margin: 0; max-width: 100%; scroll-margin-top: 100px;">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">🥚 Log Daily Egg Yield</h3>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="log_yield">
                                <div class="form-group">
                                    <label for="flockId">Select Target Flock Batch</label>
                                    <select id="flockId" name="flockId" required>
                                        <option value="">-- Choose Flock --</option>
                                        <?php foreach ($flocks as $flock): ?>
                                            <?php if ($flock['Status'] === 'Laying'): ?>
                                                <option value="<?php echo $flock['FlockID']; ?>">
                                                    Flock <?php echo $flock['FlockID']; ?> - <?php echo htmlspecialchars($flock['Breed']); ?> (Current: <?php echo $flock['CurrentCount']; ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="logDate">Yield Logging Date</label>
                                    <input type="date" id="logDate" name="logDate" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex: 1;">
                                        <label for="goodEggs">Healthy (Good)</label>
                                        <input type="number" id="goodEggs" name="goodEggs" min="0" placeholder="e.g. 350" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="damagedEggs">Damaged</label>
                                        <input type="number" id="damagedEggs" name="damagedEggs" min="0" placeholder="e.g. 12" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn" style="width: 100%; background-color: var(--primary-color);">Commit Yield Log Entry</button>
                            </form>
                        </section>

                        <!-- Form 2: Product Sync -->
                        <section id="admin-product" class="form-box" style="margin: 0; max-width: 100%; scroll-margin-top: 100px;">
                            <h3 style="color: var(--secondary-color); margin-bottom: 1.5rem;">🛍️ Sync E-Store Product Catalog</h3>
                            <form action="index.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_product">
                                <div class="form-group">
                                    <label for="prodName">Product Commercial Name</label>
                                    <input type="text" id="prodName" name="prodName" placeholder="e.g. Fresh Organic Crates (Jumbo)" required>
                                </div>
                                <div class="form-group">
                                    <label for="prodCat">Inventory Category</label>
                                    <select id="prodCat" name="prodCat" required>
                                        <option value="Eggs">Eggs</option>
                                        <option value="Meat">Meat</option>
                                        <option value="Live Birds">Live Birds</option>
                                        <option value="Fertilizer">Organic Fertilizer</option>
                                    </select>
                                </div>
                                <div class="form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex: 1;">
                                        <label for="prodPrice">Unit Price ($)</label>
                                        <input type="number" id="prodPrice" name="prodPrice" step="0.01" min="0.00" placeholder="4.50" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="prodStock">Initial Stock</label>
                                        <input type="number" id="prodStock" name="prodStock" min="0" placeholder="150" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="prodImageFile">Product Image <span style="color:#718096;font-weight:400;font-size:0.8rem;">(JPG / PNG / GIF / WEBP &mdash; max 5 MB)</span></label>
                                    <div style="border: 2px dashed var(--border-color); border-radius: 8px; padding: 1rem; background: #f7fafc; text-align: center; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('prodImageFile').click()" id="upload-drop-zone">
                                        <img id="img-preview" src="images/fresh_eggs.png" alt="Preview" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 6px; margin-bottom: 0.5rem; display: block; margin-left: auto; margin-right: auto;">
                                        <p id="upload-hint" style="color:#718096; font-size:0.85rem; margin:0;">📁 Click to choose an image from your device</p>
                                        <p id="upload-filename" style="color: var(--primary-color); font-size: 0.8rem; font-weight: 600; margin: 0.25rem 0 0;"></p>
                                    </div>
                                    <input type="file" id="prodImageFile" name="prodImageFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="previewProductImage(this)">
                                </div>
                                <script>
                                function previewProductImage(input) {
                                    const preview = document.getElementById('img-preview');
                                    const hint    = document.getElementById('upload-hint');
                                    const fname   = document.getElementById('upload-filename');
                                    const zone    = document.getElementById('upload-drop-zone');
                                    if (input.files && input.files[0]) {
                                        const reader = new FileReader();
                                        reader.onload = e => {
                                            preview.src = e.target.result;
                                            hint.textContent  = '✅ Image selected — click to change';
                                            fname.textContent = input.files[0].name;
                                            zone.style.borderColor = 'var(--primary-color)';
                                        };
                                        reader.readAsDataURL(input.files[0]);
                                    }
                                }
                                </script>

                                <div class="form-group">
                                    <label for="prodDesc">Product Description</label>
                                    <textarea id="prodDesc" name="prodDesc" rows="2" placeholder="Brief detail about size, packaging, weight..."></textarea>
                                </div>
                                <button type="submit" class="btn" style="width: 100%;">Push Product Live</button>
                            </form>
                        </section>

                    </div>

                    <!-- 3. Flock Management Section -->
                    <section id="admin-flock" style="scroll-margin-top: 100px; display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 2rem;">
                        
                        <!-- Register flock -->
                        <div class="form-box" style="margin: 0; max-width: 100%;">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">🐔 Register New Flock Batch</h3>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="add_flock">
                                <div class="form-group">
                                    <label for="breed">Breed Name</label>
                                    <input type="text" id="breed" name="breed" placeholder="e.g. Rhode Island Red" required>
                                </div>
                                <div class="form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex: 1;">
                                        <label for="hatchDate">Hatch Date</label>
                                        <input type="date" id="hatchDate" name="hatchDate" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="count">Initial Count</label>
                                        <input type="number" id="count" name="count" min="1" placeholder="e.g. 500" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="status">Development Status</label>
                                    <select id="status" name="status">
                                        <option value="Brooding">Brooding</option>
                                        <option value="Laying">Laying</option>
                                        <option value="Harvested">Harvested</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn" style="width: 100%; background-color: var(--primary-color);">Initialize Flock</button>
                            </form>
                        </div>

                        <!-- Culling log -->
                        <div class="form-box" style="margin: 0; max-width: 100%;">
                            <h3 style="color: var(--accent-color); margin-bottom: 1.5rem;">📉 Log Flock Count Reduction</h3>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="adjust_headcount">
                                <div class="form-group">
                                    <label for="adjustFlockId">Target Flock</label>
                                    <select id="adjustFlockId" name="adjustFlockId" required>
                                        <option value="">-- Select Flock --</option>
                                        <?php foreach ($flocks as $flock): ?>
                                            <?php if ($flock['Status'] !== 'Harvested'): ?>
                                                <option value="<?php echo $flock['FlockID']; ?>">
                                                    Flock <?php echo $flock['FlockID']; ?> - <?php echo htmlspecialchars($flock['Breed']); ?> (Headcount: <?php echo $flock['CurrentCount']; ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex: 1;">
                                        <label for="changeAmount">Reduction Count</label>
                                        <input type="number" id="changeAmount" name="changeAmount" min="1" placeholder="Quantity" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="changeReason">Reduction Reason</label>
                                        <select id="changeReason" name="changeReason">
                                            <option value="Mortality">Mortality (Natural Deaths)</option>
                                            <option value="Sold / Transferred">Sold / Farm Transfer</option>
                                            <option value="Harvested">Culled / Processed</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn" style="width: 100%; background-color: var(--accent-color);">Submit Reduction Log</button>
                            </form>
                        </div>

                    </section>

                    <!-- 4. Web Orders -->
                    <section id="admin-orders" class="form-box" style="margin: 0; max-width: 100%; overflow-x: auto; scroll-margin-top: 100px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">📦 Customer Web Orders Fulfillment</h3>
                        
                        <?php if (empty($admin_orders)): ?>
                            <p style="color: #718096; text-align: center; padding: 2rem;">No customer orders placed yet.</p>
                        <?php else: ?>
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--primary-color);">
                                        <th style="padding: 0.75rem;">Order #</th>
                                        <th style="padding: 0.75rem;">Customer</th>
                                        <th style="padding: 0.75rem;">Date</th>
                                        <th style="padding: 0.75rem;">Amount</th>
                                        <th style="padding: 0.75rem;">Fulfillment Address</th>
                                        <th style="padding: 0.75rem;">Status</th>
                                        <th style="padding: 0.75rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_orders as $order): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                                            <td style="padding: 0.75rem; font-weight: bold;">#<?php echo $order['OrderID']; ?></td>
                                            <td style="padding: 0.75rem;">
                                                <strong><?php echo htmlspecialchars($order['CustomerName']); ?></strong><br>
                                                <span style="font-size: 0.8rem; color:#718096;"><?php echo htmlspecialchars($order['Phone']); ?></span>
                                            </td>
                                            <td style="padding: 0.75rem; font-size: 0.85rem;"><?php echo date('M d, Y g:i A', strtotime($order['OrderDate'])); ?></td>
                                            <td style="padding: 0.75rem; font-weight: 600; color: var(--primary-color);">$<?php echo number_format($order['TotalAmount'], 2); ?></td>
                                            <td style="padding: 0.75rem; font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($order['Address']); ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php
                                                $status_colors = [
                                                    'Pending' => 'var(--secondary-color)',
                                                    'Processing' => '#3182ce',
                                                    'Shipped' => '#805ad5',
                                                    'Completed' => 'var(--primary-color)',
                                                    'Cancelled' => 'var(--accent-color)'
                                                ];
                                                $color = $status_colors[$order['Status']] ?? '#718096';
                                                ?>
                                                <span style="display: inline-block; background-color: <?php echo $color; ?>; color: white; padding: 0.15rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                                    <?php echo htmlspecialchars($order['Status']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <form action="index.php" method="POST" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
                                                    <input type="hidden" name="action" value="update_order_status">
                                                    <input type="hidden" name="orderId" value="<?php echo $order['OrderID']; ?>">
                                                    <select name="status" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; width: auto;" onchange="this.form.submit()">
                                                        <option value="Pending" <?php echo $order['Status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Processing" <?php echo $order['Status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="Shipped" <?php echo $order['Status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="Completed" <?php echo $order['Status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="Cancelled" <?php echo $order['Status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>

                    <!-- 5. E-Commerce styled Product showcase catalog in Admin view -->
                    <section id="admin-catalog-grid" class="form-box" style="margin: 0; max-width: 100%; scroll-margin-top: 100px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">📋 Dynamic Product Stock Catalog</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                            <?php if (empty($products)): ?>
                                <p style="color:#718096; grid-column: 1 / -1; text-align: center; padding: 2rem;">No products registered.</p>
                            <?php else: ?>
                                <?php foreach ($products as $prod): ?>
                                    <div class="product-card">
                                        <div style="height: 140px; overflow: hidden; background-color: #edf2f7; position: relative;">
                                            <img src="<?php echo htmlspecialchars(!empty($prod['ImageURL']) ? $prod['ImageURL'] : 'images/fresh_eggs.png'); ?>" alt="<?php echo htmlspecialchars($prod['ProductName']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="product-info" style="padding: 1.25rem;">
                                            <span class="product-category" style="font-size: 0.75rem;"><?php echo htmlspecialchars($prod['Category']); ?></span>
                                            <h4 class="product-title" style="font-size: 1.1rem; margin: 0.25rem 0;"><?php echo htmlspecialchars($prod['ProductName']); ?></h4>
                                            <div class="product-price" style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary-color);">
                                                $<?php echo number_format($prod['UnitPrice'], 2); ?> 
                                                <span style="font-size: 0.75rem; color:#718096; font-weight: normal;">/ <?php echo $unit_map[$prod['Category']] ?? 'Unit'; ?></span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; font-weight: bold; background-color: #f7fafc; padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color);">
                                                <span style="color: #4a5568;">Stock level:</span>
                                                <span style="color: <?php echo $prod['StockQuantity'] > 0 ? 'var(--primary-color)' : 'var(--accent-color)'; ?>;">
                                                    <?php echo htmlspecialchars($prod['StockQuantity']); ?> units
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- 6. Recent Logs -->
                    <section id="admin-logs" class="form-box" style="margin: 0; max-width: 100%; overflow-x: auto; scroll-margin-top: 100px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">📊 Recent Egg Collection Logs</h3>
                        <?php if (empty($eggLogs)): ?>
                            <p style="color: #718096; text-align: center; padding: 1.5rem;">No yield logs entered yet.</p>
                        <?php else: ?>
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--primary-color);">
                                        <th style="padding: 0.5rem;">Date</th>
                                        <th style="padding: 0.5rem;">Flock Batch</th>
                                        <th style="padding: 0.5rem;">Healthy</th>
                                        <th style="padding: 0.5rem;">Damaged</th>
                                        <th style="padding: 0.5rem;">Efficiency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eggLogs as $log): ?>
                                        <?php
                                        $total = $log['GoodQuantity'] + $log['DamagedQuantity'];
                                        $eff = $total > 0 ? round(($log['GoodQuantity'] / $total) * 100, 1) : 0;
                                        ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 0.5rem; font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($log['LogDate'])); ?></td>
                                            <td style="padding: 0.5rem; font-weight: 500;"><?php echo htmlspecialchars($log['Breed']); ?></td>
                                            <td style="padding: 0.5rem; color: var(--primary-color); font-weight: 600;"><?php echo $log['GoodQuantity']; ?></td>
                                            <td style="padding: 0.5rem; color: var(--accent-color);"><?php echo $log['DamagedQuantity']; ?></td>
                                            <td style="padding: 0.5rem; font-weight: bold;"><?php echo $eff; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>

                </div>

            </div>

            <!-- JavaScript helper for Admin Sidebar scrolling & highlighting -->
            <script>
                function scrollToSection(event, sectionId) {
                    event.preventDefault();
                    
                    // Highlight selected item in sidebar
                    const links = document.querySelectorAll('.admin-sidebar ul li a');
                    links.forEach(link => link.classList.remove('active-section'));
                    event.currentTarget.classList.add('active-section');
                    
                    // Scroll to target element smoothly
                    const element = document.getElementById(sectionId);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            </script>

        <?php endif; ?>

    </main>

<?php
include 'footer.php';
?>
