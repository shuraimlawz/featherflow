-- FeatherFlow Relational Database Schema
-- Target Database: MySQL
-- Date: 2026-07-19

CREATE DATABASE IF NOT EXISTS featherflow;
USE featherflow;

-- 1. Users Table (Admin & Customers)
CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Phone VARCHAR(20),
    Address TEXT,
    Role VARCHAR(15) NOT NULL DEFAULT 'Customer',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_role CHECK (Role IN ('Admin', 'Customer'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Flocks Table (Poultry Batches)
CREATE TABLE IF NOT EXISTS Flocks (
    FlockID INT AUTO_INCREMENT PRIMARY KEY,
    Breed VARCHAR(50) NOT NULL,
    HatchDate DATE NOT NULL,
    InitialCount INT NOT NULL,
    CurrentCount INT NOT NULL,
    Status VARCHAR(20) NOT NULL DEFAULT 'Brooding',
    CONSTRAINT chk_count CHECK (CurrentCount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. EggLogs Table (Daily Production Yields)
CREATE TABLE IF NOT EXISTS EggLogs (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    FlockID INT NOT NULL,
    LogDate DATE NOT NULL,
    GoodQuantity INT NOT NULL,
    DamagedQuantity INT NOT NULL,
    FOREIGN KEY (FlockID) REFERENCES Flocks(FlockID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Products Table (Catalog for Sale)
CREATE TABLE IF NOT EXISTS Products (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    ProductName VARCHAR(100) NOT NULL,
    Category VARCHAR(50) NOT NULL,
    UnitPrice DECIMAL(10,2) NOT NULL,
    StockQuantity INT NOT NULL DEFAULT 0,
    Description TEXT,
    ImageURL VARCHAR(255) NOT NULL DEFAULT 'images/fresh_eggs.png',
    CONSTRAINT chk_price CHECK (UnitPrice >= 0.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders Table (Sales Transactions)
CREATE TABLE IF NOT EXISTS Orders (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10,2) NOT NULL,
    Status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    FOREIGN KEY (CustomerID) REFERENCES Users(UserID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. OrderItems Table (Transactional Line Items)
CREATE TABLE IF NOT EXISTS OrderItems (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    Subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data --

-- Default Administrator (Password: admin123)
INSERT INTO Users (Name, Email, PasswordHash, Phone, Address, Role)
VALUES ('Farmer Joe', 'admin@featherflow.com', '$2y$10$TRO3T0tFv0XwoCJ.6fNqgOV19ZIR9NRIn2Bp8dGKkuorX19umP6yW', '+1234567890', '123 Farm Lane, Poultry Valley', 'Admin')
ON DUPLICATE KEY UPDATE UserID=UserID;

-- Default Customer (Password: customer123)
INSERT INTO Users (Name, Email, PasswordHash, Phone, Address, Role)
VALUES ('Jane Smith', 'customer@featherflow.com', '$2y$10$Wcu9Uhg1igm1sGNK0wOwgu4kdGqfbMioqUyUe6FyLOtnlP5xY05Ky', '+1987654321', '456 Town Street, Market City', 'Customer')
ON DUPLICATE KEY UPDATE UserID=UserID;

-- Flocks Seeding
INSERT INTO Flocks (FlockID, Breed, HatchDate, InitialCount, CurrentCount, Status) VALUES
(1, 'White Leghorn', '2025-01-10', 500, 480, 'Laying'),
(2, 'Rhode Island Red', '2025-03-15', 400, 390, 'Laying'),
(3, 'Premium Broiler', '2026-05-01', 300, 290, 'Brooding')
ON DUPLICATE KEY UPDATE FlockID=FlockID;

-- Products Seeding with Images
INSERT INTO Products (ProductID, ProductName, Category, UnitPrice, StockQuantity, Description, ImageURL) VALUES
(1, 'Fresh Grade-A Farm Crates', 'Eggs', 4.50, 100, 'Freshly collected healthy daily yields directly sourced from clean laying cycles.', 'images/fresh_eggs.png'),
(2, 'Premium Broiler Chicken (Dressed)', 'Meat', 12.00, 50, 'Hygienically clean processed broiler options optimized for standard culinary use.', 'images/dressed_chicken.png'),
(3, 'Healthy Layers (Active Mature)', 'Live Birds', 15.50, 30, 'Fully matured standard layout poultry choices perfect for local husbandry initialization.', 'images/laying_hens.png')
ON DUPLICATE KEY UPDATE ProductID=ProductID;

-- Egg Logs Seeding
INSERT INTO EggLogs (FlockID, LogDate, GoodQuantity, DamagedQuantity) VALUES
(1, '2026-07-18', 345, 12),
(2, '2026-07-18', 290, 8),
(1, '2026-07-19', 350, 10),
(2, '2026-07-19', 285, 7)
ON DUPLICATE KEY UPDATE LogID=LogID;

-- Sample Order Seeding
INSERT INTO Orders (OrderID, CustomerID, TotalAmount, Status) VALUES
(1, 2, 21.00, 'Pending')
ON DUPLICATE KEY UPDATE OrderID=OrderID;

INSERT INTO OrderItems (OrderItemID, OrderID, ProductID, Quantity, Subtotal) VALUES
(1, 1, 1, 2, 9.00), -- 2 Egg Crates @ 4.50
(2, 1, 2, 1, 12.00) -- 1 Dressed Chicken @ 12.00
ON DUPLICATE KEY UPDATE OrderItemID=OrderItemID;
