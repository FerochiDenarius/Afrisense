<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Payment | AfriSense';
$activePage = 'menu';
$publicHeaderMode = 'shop';
$customerName = 'Kofi Mensah';
$extraStyles = [$frontendBase . '/assets/css/order-payment.css'];

ob_start();
?>
<section class="af-payment-page">
    <ol class="af-checkout-steps">
        <li class="done"><span><i class="bi bi-cart3"></i></span>1. Cart <i class="bi bi-check-circle-fill"></i></li>
        <li class="done"><span><i class="bi bi-truck"></i></span>2. Delivery <i class="bi bi-check-circle-fill"></i></li>
        <li class="active"><span><i class="bi bi-credit-card"></i></span>3. Payment</li>
        <li><span><i class="bi bi-check-lg"></i></span>4. Confirmation</li>
    </ol>

    <div class="af-payment-grid">
        <main class="af-payment-card">
            <header class="af-payment-heading"><i class="bi bi-lock"></i><div><h1>Secure Payment</h1><p>Complete your payment to confirm your order.</p></div></header>
            <p class="af-payment-alert"><i class="bi bi-shield-check"></i> Your payment is 100% secure and encrypted.</p>

            <section class="af-payment-section">
                <h2>Choose Payment Method</h2>
                <label class="af-payment-method is-active"><input type="radio" name="method" checked><i class="bi bi-phone"></i><span><strong>Mobile Money</strong><small>Pay using MTN Mobile Money, Vodafone Cash or AirtelTigo Money</small></span><em>MTN</em><em>Vodafone</em><em>airteltigo</em></label>
                <label class="af-payment-method"><input type="radio" name="method"><i class="bi bi-credit-card"></i><span><strong>Card Payment</strong><small>Pay securely using your debit or credit card</small></span><em>VISA</em><em>Mastercard</em></label>
                <label class="af-payment-method"><input type="radio" name="method"><i class="bi bi-bank"></i><span><strong>Bank Transfer</strong><small>Make payment directly to our bank account</small></span><i class="bi bi-bank"></i></label>
            </section>

            <section class="af-payment-section">
                <h2>Pay with Mobile Money</h2>
                <div class="af-billing-grid single"><label>Select Network<select><option>MTN Mobile Money</option><option>Vodafone Cash</option><option>AirtelTigo Money</option></select></label></div>
                <label class="af-payment-input">Mobile Money Number<span><i class="bi bi-telephone"></i><input type="tel" placeholder="Enter mobile money number"></span></label>
                <p class="af-payment-warning"><i class="bi bi-info-circle"></i> You will receive a prompt on your phone to complete the payment. Please enter the Mobile Money number registered in your name.</p>
            </section>

            <section class="af-payment-section">
                <h2>Billing Information</h2>
                <div class="af-billing-grid">
                    <label>Full Name<input type="text" value="Kofi Mensah"></label>
                    <label>Email Address<input type="email" value="kofi.mensah@email.com"></label>
                    <label>Phone Number<input type="tel" value="+233 24 123 4567"></label>
                </div>
                <label class="af-terms"><input type="checkbox" checked> I have read and agree to the <a href="#">Terms &amp; Conditions</a></label>
                <button class="af-pay-now" type="button"><i class="bi bi-lock"></i> Pay Now</button>
                <a class="af-back-delivery" href="order.php"><i class="bi bi-arrow-left"></i> Back to Delivery</a>
            </section>
        </main>

        <aside class="af-payment-side">
            <section class="af-summary-card">
                <h2>Order Summary</h2>
                <?php foreach ([['Jollof Rice', 'GHc 60.00'], ['Grilled Chicken', 'GHc 70.00'], ['Coca Cola (50cl)', 'GHc 10.00']] as $item): ?>
                    <article><img src="<?php echo htmlspecialchars($frontendBase . '/assets/images/foodimage.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt=""><span><strong><?php echo htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8'); ?></strong><small>Qty: 1</small></span><b><?php echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8'); ?></b></article>
                <?php endforeach; ?>
                <dl><div><dt>Subtotal</dt><dd>GHc 140.00</dd></div><div><dt>Delivery Fee</dt><dd>GHc 10.00</dd></div><div class="total"><dt>Total Amount</dt><dd>GHc 150.00</dd></div></dl>
                <div class="af-promo"><p><i class="bi bi-tag"></i><strong>Have a promo code?</strong><br><small>Enter code at checkout to apply discount</small></p><div><input type="text" placeholder="Enter promo code"><button>Apply</button></div></div>
            </section>

            <section class="af-summary-card">
                <h2>Why Pay with AfriSense?</h2>
                <ul class="af-pay-reasons">
                    <li><i class="bi bi-shield-check"></i><span><strong>100% Secure Payments</strong>Your payment details are safe with us.</span></li>
                    <li><i class="bi bi-hand-thumbs-up"></i><span><strong>Fast &amp; Reliable</strong>Quick payment confirmation and order processing.</span></li>
                    <li><i class="bi bi-credit-card"></i><span><strong>Multiple Payment Options</strong>Choose the payment method that works for you.</span></li>
                    <li><i class="bi bi-shield-check"></i><span><strong>Money Back Guarantee</strong>Get a full refund if your order is not delivered.</span></li>
                </ul>
            </section>

            <section class="af-summary-card af-payment-help">
                <i class="bi bi-headset"></i><div><h2>Need Help?</h2><p>Our support team is here to assist you.</p><strong>Call / WhatsApp: +233 24 123 4567</strong><strong>Email: support@afrisense.com</strong><strong>Mon - Sun: 8:00 AM - 10:00 PM</strong></div>
            </section>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/public_layout.php';
?>
