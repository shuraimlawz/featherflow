/* ==========================================================================
   FeatherFlow - Frontend Interactivity Engine
   ========================================================================== */

// Global Shopping Cart State
let shoppingCart = [];

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize E-Commerce Functionality if on the Shop page
    initShoppingCart();

    // 2. Initialize Contact Form Validation if on the Contact page
    initContactForm();

    // 3. Dynamic Navigation Highlight Safety check
    applyActiveNavigation();
});

/**
 * Shopping Cart Logic (E-Commerce Storefront)
 */
function initShoppingCart() {
    const basketButtons = document.querySelectorAll('.product-grid .btn');
    if (basketButtons.length === 0) return; // Exit if not on the home/shop page

    // Create a mini cart indicator floating in the header navigation dynamically
    const navUl = document.querySelector('nav ul');
    const cartLi = document.createElement('li');
    cartLi.innerHTML = `<a href="#" id="cart-indicator" style="color: #e6a100; font-weight: bold;">🛒 Cart (0)</a>`;
    navUl.appendChild(cartLi);

    basketButtons.forEach((button, index) => {
        button.addEventListener('click', (e) => {
            // Traverse the DOM to get the specific product details
            const card = e.target.closest('.product-card');
            const title = card.querySelector('.product-title').innerText;
            const priceText = card.querySelector('.product-price').innerText;
            
            // Extract the numerical price (e.g., "$4.50 / Per Crate" -> 4.50)
            const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));

            addItemToCart(title, price);
        });
    });
}

function addItemToCart(title, price) {
    // Check if item already exists in the cart structure
    const existingItem = shoppingCart.find(item => item.title === title);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        shoppingCart.push({
            title: title,
            price: price,
            quantity: 1
        });
    }

    updateCartUI();
}

function updateCartUI() {
    const cartIndicator = document.getElementById('cart-indicator');
    
    // Calculate total item units via array reduce
    const totalItems = shoppingCart.reduce((total, item) => total + item.quantity, 0);
    const totalPrice = shoppingCart.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    cartIndicator.innerText = `🛒 Cart (${totalItems}) - $${totalPrice.toFixed(2)}`;

    // Optional visual confirmation popup
    console.log("Current Cart Payload:", shoppingCart);
}

/**
 * Form Interactivity & Mock Backend Transmission (Contact Page)
 */
function initContactForm() {
    const contactForm = document.querySelector('.form-box form');
    if (!contactForm) return; // Exit if not on contact page

    contactForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevent page from doing a hard reload

        // Grabbing operational values
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value.trim();

        if (!name || !email || !message) {
            alert('Please fill out all mandatory fields before transmission.');
            return;
        }

        // Simulating the transaction block layout for your backend API matching requirements
        const payload = {
            senderName: name,
            senderEmail: email,
            inquirySubject: subject,
            messageBody: message,
            timestamp: new Date().toISOString()
        };

        console.log('Transmitting data packet to backend API...', payload);

        // Visual success alert structure
        alert(`Thank you, ${name}! Your inquiries regarding "${subject}" have been successfully structured and queued.`);
        
        contactForm.reset();
    });
}

/**
 * Layout UI Helpers
 */
function applyActiveNavigation() {
    // Automatically highlights current page if filename changes slightly
    const currentPath = window.location.pathname.split("/").pop();
    const navLinks = document.querySelectorAll('nav ul li a');

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });
}
