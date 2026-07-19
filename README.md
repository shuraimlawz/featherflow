# FeatherFlow: Poultry Management & E-Commerce System

FeatherFlow is a modernized, web-based poultry farming application designed to bridge the operational gap between agricultural logistics tracking and consumer sales. It features a secure relational database backend, a customer storefront with an interactive shopping cart, and an administrative dashboard panel to log production and track operations.

---

## 🚀 Technology Stack

- **Frontend**: HTML5, CSS3 (Vanilla CSS), Vanilla JavaScript
- **Backend**: PHP (Session management, secure password hashing, PDO driver)
- **Database**: MySQL (RDBMS, transaction-safe InnoDB tables)

---

## 📂 Project Structure

```text
featherflow/
├── backend/
│   ├── db.php             # Centralized PDO database connection
│   ├── login.php          # AJAX Customer login handler
│   ├── register.php       # AJAX Customer registration handler
│   ├── logout.php         # Destroys session and redirects to welcome page
│   ├── place_order.php    # Transactional storefront checkout processor
│   └── send_inquiry.php   # Contact inquiry message logger
├── schema.sql             # MySQL database schema definition and seeds
├── index.php              # Dynamic welcome/landing page
├── home.php               # Dynamic storefront page with checkout modal
├── contact.php            # Dynamic contact page
├── admin_login.php        # Standalone administrator login portal
├── dashboard.php          # Operational admin control panel (restricted access)
├── header.php             # Unified page header & customer login popup modal
├── footer.php             # Unified page footer
├── style.css              # Custom stylesheet (appended modal & alert styles)
├── app.js                 # Upgraded frontend logic (AJAX, Cart state, validations)
└── README.md              # Project documentation
```

---

## 🗄️ Relational Database Schema

The database relies on 6 relational tables configured in [schema.sql](file:///c:/Users/Justice/Downloads/featherflow/featherflow/schema.sql):

1. **Users**: Stores customer profiles and administrator credentials.
2. **Flocks**: Tracks poultry batches (breed, counts, hatch dates, and development status).
3. **EggLogs**: Records daily layer egg collections (good vs cracked quantity) to compute yield efficiency.
4. **Products**: Manages items available for purchase in the storefront.
5. **Orders**: Logs e-commerce sales transactions and order fulfillment tracking.
6. **OrderItems**: Holds transaction line-item quantities and prices.

---

## 🛠️ Installation & Setup

### 1. Database Configuration
1. Start your local MySQL database server (e.g., using XAMPP, WAMP, or standalone MySQL).
2. Create a database named `featherflow`.
3. Import the schema and seed data by running the following SQL script:
   ```sql
   SOURCE schema.sql;
   ```
4. Verify the database configurations in [backend/db.php](file:///c:/Users/Justice/Downloads/featherflow/featherflow/backend/db.php). Update the database username and password fields to match your local server environment.

### 2. Run the Application
1. Start a local PHP server in the project root directory:
   ```bash
   php -S localhost:8000
   ```
2. Open your web browser and navigate to `http://localhost:8000`.

---

## 🔑 Test Credentials

The database contains pre-configured accounts for testing:

- **Customer Login (Popup Modal)**:
  - **Email**: `customer@featherflow.com`
  - **Password**: `customer123`
- **Administrator Login (Admin Portal)**:
  - **Email**: `admin@featherflow.com`
  - **Password**: `admin123`
  - *Direct Link*: `http://localhost:8000/admin_login.php`

---

## 📝 Features & Workflows

### Client Storefront
- **Navbar Login Popup**: Click **Login / Register** on the navbar to sign in or sign up. All forms utilize AJAX fetch requests to log in/register without loading a separate page.
- **Dynamic Catalog**: The shop storefront dynamically fetches item details and quantities. Out-of-stock items are disabled in the storefront UI automatically.
- **Persistent Cart**: Adds products to the basket using a JavaScript state machine backed by persistent browser `localStorage`.
- **Checkout Modal**: Registered customers can click the cart to input details (automatically pre-filled if saved in the profile) and post the checkout transaction to [backend/place_order.php](file:///c:/Users/Justice/Downloads/featherflow/featherflow/backend/place_order.php).

### Administrative Panel
- **Operations Dashboard**: Restricts page access to administrators. Calculates dynamic statistics like total active flock size, daily yield efficiency percentages, and pending web orders.
- **Daily Egg Logs**: Logs flock egg yield parameters straight into the database.
- **Product Catalog Sync**: Posts new commercial items live onto the storefront.
- **Flock Lifecycle & Headcount tracking**: Adjusts bird count (log mortality or culling reductions) and registers new flocks.
- **Fulfillment Panel**: Administers customer web orders, allowing real-time status updates (Pending, Processing, Shipped, Completed, Cancelled).