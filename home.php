<?php
$page_title = "E-Storefront - FeatherFlow";
require_once 'backend/db.php';
include 'header.php';

// Fetch all available products
try {
    $stmt = $pdo->query("SELECT * FROM Products ORDER BY Category, ProductName");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Category unit mapping for display
$unit_map = [
    'Eggs' => 'Per Crate',
    'Meat' => 'Unit kg',
    'Live' => 'Head Count',
    'Live Birds' => 'Head Count',
    'Fertilizer' => 'Per Bag'
];
?>

    <main class="container">
        <h2 style="color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 2rem;">Available Farm Stocks</h2>
        
        <!-- Search Options -->
        <div style="margin-bottom: 2rem; display: flex; flex-direction: column; gap: 0.5rem; max-width: 500px;">
            <label for="product-search" style="font-weight: 600; font-size: 0.9rem; color: var(--primary-color);">🔍 Search Catalog Stocks</label>
            <input type="text" id="product-search" placeholder="Type name or category (e.g. eggs, chicken)..." onkeyup="filterProducts()" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; width: 100%;">
        </div>

        <div class="product-grid" style="margin-top: 1rem;">
            <?php if (empty($products)): ?>
                <p style="text-align: center; grid-column: 1 / -1; padding: 3rem; color: #718096;">No products currently available in the catalog database.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-id="<?php echo $product['ProductID']; ?>" data-name="<?php echo htmlspecialchars($product['ProductName']); ?>" data-price="<?php echo $product['UnitPrice']; ?>" data-stock="<?php echo $product['StockQuantity']; ?>">
                        <div style="height: 190px; overflow: hidden; background-color: #edf2f7; position: relative;">
                            <img src="<?php echo htmlspecialchars(!empty($product['ImageURL']) ? $product['ImageURL'] : 'images/fresh_eggs.png'); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="product-info" style="padding: 1.25rem;">
                            <span class="product-category"><?php echo htmlspecialchars($product['Category']); ?></span>
                            <h3 class="product-title"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                            <p style="color:#718096; font-size:0.9rem; margin-bottom:1rem; min-height: 45px;"><?php echo htmlspecialchars($product['Description']); ?></p>
                            <div class="product-price">$<?php echo number_format($product['UnitPrice'], 2); ?> <span style="font-size: 0.8rem; color:#718096;">/ <?php echo isset($unit_map[$product['Category']]) ? $unit_map[$product['Category']] : 'Unit'; ?></span></div>
                            
                            <div style="font-size: 0.85rem; margin-bottom: 1rem; font-weight: 600; color: <?php echo $product['StockQuantity'] > 0 ? '#38a169' : '#e53e3e'; ?>;">
                                <?php echo $product['StockQuantity'] > 0 ? 'In Stock: ' . $product['StockQuantity'] . ' units' : 'Out of Stock'; ?>
                            </div>
                            
                            <button class="btn add-to-cart-btn" style="width: 100%;" <?php echo $product['StockQuantity'] > 0 ? '' : 'disabled style="background-color: #a0aec0; cursor: not-allowed;"'; ?>>
                                <?php echo $product['StockQuantity'] > 0 ? 'Add to Basket' : 'Unavailable'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Checkout Modal -->
    <div id="checkout-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close-btn" onclick="closeCheckoutModal()">&times;</span>
            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">🛒 Complete Your Order</h3>
            
            <div id="checkout-alert" class="alert-box" style="display: none;"></div>
            
            <!-- Cart Items List -->
            <div id="checkout-items-list" style="margin-bottom: 1.5rem; max-height: 250px; overflow-y: auto;">
                <!-- Populated dynamically via app.js -->
            </div>
            
            <div class="checkout-totals">
                <span>Total Amount:</span>
                <span id="checkout-total-price">$0.00</span>
            </div>
            
            <!-- Customer Delivery Information -->
            <form id="checkout-form" onsubmit="handleCheckoutSubmit(event)" style="margin-top: 1.5rem;">
                <h4 style="color: var(--text-dark); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">Delivery & Contact Details</h4>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert-box alert-danger" style="margin-bottom: 1.5rem;">
                        You must be logged in to finalize your purchase.
                    </div>
                    <button type="button" class="btn" style="width: 100%; background-color: var(--primary-color);" onclick="openLoginModal(event)">Click Here to Login / Register</button>
                <?php else: ?>
                    <div class="form-group">
                        <label for="checkout-name">Recipient Name</label>
                        <input type="text" id="checkout-name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="checkout-phone">Phone Number</label>
                        <input type="text" id="checkout-phone" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" required placeholder="e.g. +1234567890">
                    </div>
                    <div class="form-group">
                        <label for="checkout-address">Delivery Address</label>
                        <textarea id="checkout-address" rows="3" required placeholder="Enter delivery address details"><?php echo htmlspecialchars($_SESSION['user_address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Confirm & Place Order</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

<?php
include 'footer.php';
?>
