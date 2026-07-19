/* ==========================================================================
   FeatherFlow - Frontend Interactivity Engine
   ========================================================================== */

// Global Shopping Cart State - Load from LocalStorage if available
let shoppingCart = JSON.parse(localStorage.getItem('featherflow_cart')) || [];

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize E-Commerce Functionality if on the Shop page
    initShoppingCart();

    // 2. Initialize Contact Form Validation if on the Contact page
    initContactForm();

    // 3. Highlight navigation items dynamically (fallback)
    applyActiveNavigation();
});

/**
 * Authentication Popup Modal Handlers
 */
function openLoginModal(e) {
    if (e) e.preventDefault();
    document.getElementById('auth-modal').style.display = 'flex';
    document.getElementById('auth-alert').style.display = 'none';
}

function closeLoginModal() {
    document.getElementById('auth-modal').style.display = 'none';
}

function switchAuthTab(tab) {
    const tabLogin = document.getElementById('tab-login');
    const tabRegister = document.getElementById('tab-register');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    if (tab === 'login') {
        tabLogin.classList.add('active');
        tabRegister.classList.remove('active');
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        tabLogin.classList.remove('active');
        tabRegister.classList.add('active');
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
    document.getElementById('auth-alert').style.display = 'none';
}

function handleAuthSubmit(event, action) {
    event.preventDefault();
    const alertBox = document.getElementById('auth-alert');
    alertBox.style.display = 'none';
    
    let url = 'backend/login.php';
    let payload = {};
    
    if (action === 'login') {
        payload = {
            email: document.getElementById('login-email').value.trim(),
            password: document.getElementById('login-password').value
        };
    } else {
        url = 'backend/register.php';
        payload = {
            name: document.getElementById('register-name').value.trim(),
            email: document.getElementById('register-email').value.trim(),
            password: document.getElementById('register-password').value,
            phone: document.getElementById('register-phone').value.trim(),
            address: document.getElementById('register-address').value.trim()
        };
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200 || res.status === 201) {
            alertBox.className = 'alert-box alert-success';
            alertBox.innerText = action === 'login' ? 'Successfully logged in! Reloading...' : 'Registration successful! Reloading...';
            alertBox.style.display = 'block';
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alertBox.className = 'alert-box alert-danger';
            alertBox.innerText = res.body.error || 'Authentication failed. Please try again.';
            alertBox.style.display = 'block';
        }
    })
    .catch(err => {
        console.error('Auth Error:', err);
        alertBox.className = 'alert-box alert-danger';
        alertBox.innerText = 'Server communication error. Please check your database connection.';
        alertBox.style.display = 'block';
    });
}

// Close modals when clicking outside content area
window.onclick = function(event) {
    const authModal = document.getElementById('auth-modal');
    const checkoutModal = document.getElementById('checkout-modal');
    if (event.target === authModal) {
        closeLoginModal();
    }
    if (event.target === checkoutModal) {
        closeCheckoutModal();
    }
}

/**
 * Shopping Cart Logic (E-Commerce Storefront)
 */
function initShoppingCart() {
    // Generate floating cart indicator inside navbar
    const navUl = document.getElementById('nav-links');
    if (!navUl) return;

    // Check if indicator already exists
    let cartIndicator = document.getElementById('cart-indicator');
    if (!cartIndicator) {
        const cartLi = document.createElement('li');
        cartLi.innerHTML = `<a href="#" id="cart-indicator" style="color: var(--secondary-color); font-weight: bold;" onclick="openCheckoutModal(event)">🛒 Cart (0)</a>`;
        navUl.appendChild(cartLi);
    }
    
    updateCartUI();

    // Attach listeners to "Add to Basket" buttons on the store
    const basketButtons = document.querySelectorAll('.product-card .add-to-cart-btn');
    basketButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            // ENFORCE AUTHENTICATION: Require login when item is placed in a cart
            if (!window.isLoggedIn) {
                openLoginModal(e);
                const alertBox = document.getElementById('auth-alert');
                alertBox.className = 'alert-box alert-danger';
                alertBox.innerText = 'Please log in to add products to your cart and place orders.';
                alertBox.style.display = 'block';
                return;
            }

            const card = e.target.closest('.product-card');
            const id = parseInt(card.getAttribute('data-id'));
            const title = card.getAttribute('data-name');
            const price = parseFloat(card.getAttribute('data-price'));
            const stock = parseInt(card.getAttribute('data-stock'));

            addItemToCart(id, title, price, stock);
        });
    });
}

function addItemToCart(id, title, price, maxStock) {
    const existingItem = shoppingCart.find(item => item.id === id);

    if (existingItem) {
        if (existingItem.quantity >= maxStock) {
            alert(`Cannot add more. Limit of ${maxStock} units reached (available stock).`);
            return;
        }
        existingItem.quantity += 1;
    } else {
        shoppingCart.push({
            id: id,
            title: title,
            price: price,
            quantity: 1
        });
    }

    // Save cart state
    localStorage.setItem('featherflow_cart', JSON.stringify(shoppingCart));
    updateCartUI();
}

function updateCartUI() {
    const cartIndicator = document.getElementById('cart-indicator');
    if (!cartIndicator) return;

    const totalItems = shoppingCart.reduce((total, item) => total + item.quantity, 0);
    const totalPrice = shoppingCart.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    if (totalItems > 0) {
        cartIndicator.innerHTML = `🛒 Cart <span class="badge">${totalItems}</span> - $${totalPrice.toFixed(2)}`;
    } else {
        cartIndicator.innerHTML = `🛒 Cart (0)`;
    }
}

/**
 * Checkout Modal Controls
 */
function openCheckoutModal(e) {
    if (e) e.preventDefault();
    const modal = document.getElementById('checkout-modal');
    if (!modal) {
        // If not on shop front (home.php) or main dashboard (index.php), redirect to shop front
        window.location.href = 'home.php';
        return;
    }
    
    modal.style.display = 'flex';
    document.getElementById('checkout-alert').style.display = 'none';
    
    renderCheckoutItems();
}

function closeCheckoutModal() {
    const modal = document.getElementById('checkout-modal');
    if (modal) modal.style.display = 'none';
}

function renderCheckoutItems() {
    const listContainer = document.getElementById('checkout-items-list');
    const totalSpan = document.getElementById('checkout-total-price');
    if (!listContainer) return;
    
    listContainer.innerHTML = '';
    let total = 0;
    
    if (shoppingCart.length === 0) {
        listContainer.innerHTML = '<p style="text-align: center; color: #718096; padding: 2rem;">Your cart is empty. Browse stocks and add products.</p>';
        totalSpan.innerText = '$0.00';
        return;
    }
    
    shoppingCart.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        const itemRow = document.createElement('div');
        itemRow.className = 'checkout-item';
        itemRow.innerHTML = `
            <div>
                <strong class="checkout-item-title">${item.title}</strong><br>
                <span class="checkout-item-details">$${item.price.toFixed(2)} x ${item.quantity} = $${subtotal.toFixed(2)}</span>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.85rem; background-color: var(--accent-color);" onclick="removeCartItem(${index})">Remove</button>
            </div>
        `;
        listContainer.appendChild(itemRow);
    });
    
    totalSpan.innerText = `$${total.toFixed(2)}`;
}

function removeCartItem(index) {
    shoppingCart.splice(index, 1);
    localStorage.setItem('featherflow_cart', JSON.stringify(shoppingCart));
    updateCartUI();
    renderCheckoutItems();
}

function handleCheckoutSubmit(event) {
    event.preventDefault();
    const alertBox = document.getElementById('checkout-alert');
    alertBox.style.display = 'none';
    
    const name = document.getElementById('checkout-name').value.trim();
    const phone = document.getElementById('checkout-phone').value.trim();
    const address = document.getElementById('checkout-address').value.trim();
    
    const payload = {
        name: name,
        phone: phone,
        address: address,
        items: shoppingCart
    };
    
    fetch('backend/place_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200 || res.status === 201) {
            alert(`Thank you! Order placed successfully! (Order #${res.body.order_id})`);
            
            // Clear cart
            shoppingCart = [];
            localStorage.removeItem('featherflow_cart');
            updateCartUI();
            closeCheckoutModal();
            window.location.reload();
        } else {
            alertBox.className = 'alert-box alert-danger';
            alertBox.innerText = res.body.error || 'Failed to place order.';
            alertBox.style.display = 'block';
        }
    })
    .catch(err => {
        console.error('Checkout Error:', err);
        alertBox.className = 'alert-box alert-danger';
        alertBox.innerText = 'Communication failure with checkout processor.';
        alertBox.style.display = 'block';
    });
}

/**
 * Client-Side E-Commerce Product Catalog Search Filter
 */
function filterProducts() {
    const searchBar = document.getElementById('product-search');
    if (!searchBar) return;
    
    const query = searchBar.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-grid .product-card');
    
    cards.forEach(card => {
        const title = card.getAttribute('data-name').toLowerCase();
        const category = card.querySelector('.product-category').innerText.toLowerCase();
        const description = card.querySelector('p') ? card.querySelector('p').innerText.toLowerCase() : '';
        
        if (title.includes(query) || category.includes(query) || description.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Form Interactivity & Backend Transmission (Contact Page)
 */
function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return; 

    contactForm.addEventListener('submit', (e) => {
        e.preventDefault(); 
        const alertBox = document.getElementById('contact-alert');
        alertBox.style.display = 'none';

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value.trim();

        if (!name || !email || !message) {
            alertBox.className = 'alert-box alert-danger';
            alertBox.innerText = 'Please fill out all fields.';
            alertBox.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('subject', subject);
        formData.append('message', message);

        fetch('backend/send_inquiry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertBox.className = 'alert-box alert-success';
                alertBox.innerText = data.message;
                alertBox.style.display = 'block';
                contactForm.reset();
            } else {
                alertBox.className = 'alert-box alert-danger';
                alertBox.innerText = data.error || 'Failed to send inquiry.';
                alertBox.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Contact Inquiry Error:', err);
            alertBox.className = 'alert-box alert-danger';
            alertBox.innerText = 'Transmission error. Please try again later.';
            alertBox.style.display = 'block';
        });
    });
}

/**
 * Layout UI Helpers
 */
function applyActiveNavigation() {
    const currentPath = window.location.pathname.split("/").pop();
    const navLinks = document.querySelectorAll('nav ul li a');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (currentPath === '' && href === 'index.php')) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });
}
