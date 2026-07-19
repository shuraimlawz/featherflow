<?php
$page_title = "Contact Us - FeatherFlow";
include 'header.php';
?>

    <main class="container">
        <div class="form-box">
            <h2 style="color: var(--primary-color); margin-bottom: 0.5rem; text-align: center;">Get in Touch</h2>
            <p style="color: #718096; text-align: center; margin-bottom: 2rem;">Have questions regarding wholesale bulk pricing or scheduled delivery tracking configurations?</p>
            
            <div id="contact-alert" class="alert-box" style="display: none;"></div>

            <form id="contact-form" action="backend/send_inquiry.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name here" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter e-mail here" required>
                </div>

                <div class="form-group">
                    <label for="subject">Inquiry Subject</label>
                    <select id="subject" name="subject">
                        <option value="general">General Support Information</option>
                        <option value="wholesale">Bulk Custom Wholesale Orders</option>
                        <option value="delivery">Logistics Operations & Deliveries</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message Payload</label>
                    <textarea id="message" name="message" rows="5" placeholder="Draft details here..." required></textarea>
                </div>

                <button type="submit" class="btn" style="width: 100%; font-size: 1.05rem;">Transmit Inquiry Message</button>
            </form>
        </div>
    </main>

<?php
include 'footer.php';
?>
