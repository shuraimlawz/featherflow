<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to place an order.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['items']) || !is_array($input['items']) || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No products found in the checkout order.']);
    exit;
}

$recipientName = trim($input['name'] ?? '');
$recipientPhone = trim($input['phone'] ?? '');
$recipientAddress = trim($input['address'] ?? '');

if (empty($recipientName) || empty($recipientPhone) || empty($recipientAddress)) {
    http_response_code(400);
    echo json_encode(['error' => 'Recipient name, phone, and delivery address are required.']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Calculate total and validate stock for each item
    $totalAmount = 0.00;
    $orderItemsToInsert = [];
    
    foreach ($input['items'] as $item) {
        $productId = intval($item['id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        
        if ($productId <= 0 || $quantity <= 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product reference or quantity.']);
            exit;
        }
        
        // Fetch product details from DB
        $stmt = $pdo->prepare("SELECT ProductName, UnitPrice, StockQuantity FROM Products WHERE ProductID = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => "Product ID $productId does not exist in catalog."]);
            exit;
        }
        
        if ($product['StockQuantity'] < $quantity) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                'error' => "Insufficient stock for product '" . $product['ProductName'] . "'. Available: " . $product['StockQuantity'] . ", requested: $quantity"
            ]);
            exit;
        }
        
        $subtotal = $product['UnitPrice'] * $quantity;
        $totalAmount += $subtotal;
        
        $orderItemsToInsert[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $product['UnitPrice'],
            'subtotal' => $subtotal
        ];
    }
    
    // 1. Insert into Orders
    $orderStmt = $pdo->prepare("
        INSERT INTO Orders (CustomerID, TotalAmount, Status)
        VALUES (?, ?, 'Pending')
    ");
    $orderStmt->execute([$_SESSION['user_id'], $totalAmount]);
    $orderId = $pdo->lastInsertId();
    
    // 2. Insert into OrderItems and decrement stock
    $itemStmt = $pdo->prepare("
        INSERT INTO OrderItems (OrderID, ProductID, Quantity, Subtotal)
        VALUES (?, ?, ?, ?)
    ");
    
    $stockStmt = $pdo->prepare("
        UPDATE Products
        SET StockQuantity = StockQuantity - ?
        WHERE ProductID = ?
    ");
    
    foreach ($orderItemsToInsert as $item) {
        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['subtotal']]);
        $stockStmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // 3. Update customer's address and phone in users table if needed for future purchases
    $userStmt = $pdo->prepare("
        UPDATE Users
        SET Phone = ?, Address = ?
        WHERE UserID = ?
    ");
    $userStmt->execute([$recipientPhone, $recipientAddress, $_SESSION['user_id']]);
    
    // Update active session variables too
    $_SESSION['user_phone'] = $recipientPhone;
    $_SESSION['user_address'] = $recipientAddress;
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => "Order #$orderId has been placed successfully."
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process checkout transaction: ' . $e->getMessage()]);
}
