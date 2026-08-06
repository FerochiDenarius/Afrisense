        <?php
        $paymentGateway = (string) ($system['payment_gateway'] ?? 'Paystack');
        $paymentInstruction = (string) ($system['payment_instruction'] ?? 'You can make payments securely using any of the available payment methods. Your payment is protected with 256-bit SSL encryption.');
        $refundProcessMessage = (string) ($system['refund_process_message'] ?? 'Refunds are processed within 3-5 working days to your original payment method.');
        $paymentGateways = [
            ['key' => 'paystack_enabled', 'label' => 'Paystack', 'hint' => 'Accept card payments, Mobile Money and bank transfers.', 'logo' => '<i class="bi bi-stack" aria-hidden="true"></i>', 'class' => 'paystack'],
            ['key' => 'mtn_momo_enabled', 'label' => 'MTN Mobile Money', 'hint' => 'Accept payments via MTN Mobile Money.', 'logo' => 'MTN', 'class' => 'mtn'],
            ['key' => 'vodafone_cash_enabled', 'label' => 'Vodafone Cash', 'hint' => 'Accept payments via Vodafone Cash.', 'logo' => '<i class="bi bi-circle-fill" aria-hidden="true"></i>', 'class' => 'vodafone'],
            ['key' => 'flutterwave_enabled', 'label' => 'Flutterwave', 'hint' => 'Accept international card payments and more.', 'logo' => '<i class="bi bi-wind" aria-hidden="true"></i>', 'class' => 'flutterwave'],
        ];
        ?>
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card af-settings-card-wide af-payment-gateways-card" id="payment-settings">
            <div class="af-settings-section-heading">
                <div>
                    <h2>Payment Gateways</h2>
                    <p>Enable and manage payment gateways on your website.</p>
                </div>
                <button type="button" disabled><i class="bi bi-plus-lg" aria-hidden="true"></i> Add New Gateway</button>
            </div>

            <div class="af-payment-gateway-list">
                <?php // Render this conditional/dynamic template block. ?>
                <?php foreach ($paymentGateways as $gateway): ?>
                    <?php $gatewayEnabled = (int) ($system[$gateway['key']] ?? 0) === 1; ?>
                    <article>
                        <span class="af-payment-logo <?php echo htmlspecialchars($gateway['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $gateway['logo']; ?></span>
                        <div>
                            <strong><?php echo htmlspecialchars($gateway['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars($gateway['hint'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>
                        <em class="<?php echo $gatewayEnabled ? '' : 'muted'; ?>"><?php echo $gatewayEnabled ? 'Enabled' : 'Disabled'; ?></em>
                        <label class="af-settings-inline-switch">
                            <input type="checkbox" name="<?php echo htmlspecialchars($gateway['key'], ENT_QUOTES, 'UTF-8'); ?>" value="1" <?php echo $gatewayEnabled ? 'checked' : ''; ?>>
                            <span class="af-switch" aria-hidden="true"></span>
                        </label>
                        <button type="button" class="af-icon-action" title="Configure gateway"><i class="bi bi-gear" aria-hidden="true"></i></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card af-payment-config-card">
            <h2>Gateway Configuration</h2>
            <p>Configure the selected payment gateway.</p>
            <label class="af-settings-field">
                <span>Select Gateway</span>
                <select name="payment_gateway">
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php foreach (['Paystack', 'MTN Mobile Money', 'Vodafone Cash', 'Flutterwave'] as $gatewayName): ?>
                        <option value="<?php echo htmlspecialchars($gatewayName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $paymentGateway === $gatewayName ? 'selected' : ''; ?>><?php echo htmlspecialchars($gatewayName, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="af-settings-field">
                <span>Public Key</span>
                <input type="text" name="payment_public_key" value="<?php echo htmlspecialchars((string) ($system['payment_public_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="pk_test_...">
            </label>
            <label class="af-settings-field">
                <span>Secret Key</span>
                <input type="password" name="payment_secret_key" value="<?php echo htmlspecialchars((string) ($system['payment_secret_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="sk_test_...">
            </label>
            <label class="af-settings-field">
                <span>Webhook Secret (Optional)</span>
                <input type="password" name="payment_webhook_secret" value="<?php echo htmlspecialchars((string) ($system['payment_webhook_secret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Webhook signing secret">
            </label>
            <button type="button" class="af-test-connection-btn" title="Credentials are saved locally. Gateway API test is not implemented yet.">
                <i class="bi bi-broadcast" aria-hidden="true"></i>
                Test Connection
            </button>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card">
            <h2>Currency Settings</h2>
            <p>Manage the currency used on your website.</p>
            <label class="af-settings-field" for="payment_default_currency">
                <span>Default Currency</span>
                <select id="payment_default_currency" name="default_currency">
                    <?php $paymentCurrency = (string) ($system['default_currency'] ?? 'GHS'); ?>
                    <option value="GHS" <?php echo $paymentCurrency === 'GHS' ? 'selected' : ''; ?>>GHS (GHc) - Ghana Cedi</option>
                    <option value="USD" <?php echo $paymentCurrency === 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                </select>
            </label>
            <div class="af-payment-radio-row">
                <span>Display Currency Position</span>
                <label><input type="radio" checked disabled> Left (GHc 100.00)</label>
                <label><input type="radio" disabled> Right (100.00 GHc)</label>
                <label><input type="radio" disabled> Left with space (GHc 100.00)</label>
            </div>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card">
            <h2>Payment Settings</h2>
            <p>Configure how payments work on your website.</p>
            <label class="af-toggle-row"><input type="checkbox" name="guest_checkout_enabled" value="1" <?php echo (int) ($system['guest_checkout_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Enable guest checkout</strong><small>Allow customers to checkout without an account.</small></label>
            <label class="af-toggle-row"><input type="checkbox" name="auto_confirm_paid_orders" value="1" <?php echo (int) ($system['auto_confirm_paid_orders'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Auto confirm paid orders</strong><small>Automatically confirm orders after successful payment.</small></label>
            <label class="af-toggle-row"><input type="checkbox" name="mobile_money_enabled" value="1" <?php echo (int) ($system['mobile_money_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Mobile Money checkout</strong><small>Show Mobile Money as a customer payment option.</small></label>
            <label class="af-toggle-row"><input type="checkbox" name="card_payment_enabled" value="1" <?php echo (int) ($system['card_payment_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Card checkout</strong><small>Show Card as a customer payment option.</small></label>
            <label class="af-toggle-row"><input type="checkbox" name="cash_payment_enabled" value="1" <?php echo (int) ($system['cash_payment_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Cash on delivery</strong><small>Allow customers to pay when food arrives.</small></label>
            <label class="af-toggle-row">
                <input type="checkbox" name="email_notifications" value="1" <?php echo (int) ($system['email_notifications'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Send payment confirmation email</strong>
                <small>Send email to customer after successful payment.</small>
            </label>
            <label class="af-toggle-row"><input type="checkbox" name="payment_test_mode" value="1" <?php echo (int) ($system['payment_test_mode'] ?? 0) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Enable test mode</strong><small>Use gateway in test/sandbox mode.</small></label>
            <input type="hidden" name="order_notifications" value="<?php echo (int) ($system['order_notifications'] ?? 1) === 1 ? '1' : '0'; ?>">
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card">
            <h2>Payment Instructions</h2>
            <p>Add instructions for customers during checkout.</p>
            <label class="af-settings-field">
                <span>Instruction Message</span>
                <textarea name="payment_instruction" rows="5"><?php echo htmlspecialchars($paymentInstruction, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card af-settings-card-wide">
            <h2>Refund &amp; Cancellation Policy</h2>
            <p>Manage refund and cancellation settings.</p>
            <div class="af-settings-two">
                <label class="af-settings-field">
                    <span>Refund Policy</span>
                    <?php $refundPolicy = (string) ($system['refund_policy'] ?? 'Allow refund within 7 days'); ?>
                    <select name="refund_policy">
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach (['Allow refund within 7 days', 'Allow refund within 3 days', 'No automatic refund'] as $policy): ?>
                            <option value="<?php echo htmlspecialchars($policy, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $refundPolicy === $policy ? 'selected' : ''; ?>><?php echo htmlspecialchars($policy, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="af-settings-field">
                    <span>Cancellation Policy</span>
                    <?php $cancellationPolicy = (string) ($system['cancellation_policy'] ?? 'Allow cancellation before delivery'); ?>
                    <select name="cancellation_policy">
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach (['Allow cancellation before delivery', 'Allow cancellation before preparation', 'No customer cancellation'] as $policy): ?>
                            <option value="<?php echo htmlspecialchars($policy, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $cancellationPolicy === $policy ? 'selected' : ''; ?>><?php echo htmlspecialchars($policy, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label class="af-settings-field"><span>Refund Process Message</span><textarea name="refund_process_message" rows="3"><?php echo htmlspecialchars($refundProcessMessage, ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        </section>

        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card">
            <h2>Recent Transactions</h2>
            <p>Use the Orders page for live payment records in the current schema.</p>
            <div class="af-recent-transactions">
                <article><span>Paid Orders</span><strong>Orders table</strong><em>Active</em></article>
                <article><span>Pending Payments</span><strong>Orders table</strong><em class="pending">Review</em></article>
                <article><span>Failed Payments</span><strong>No gateway logs</strong><em class="muted">N/A</em></article>
            </div>
        </section>
        <p class="af-settings-note af-settings-card-wide"><i class="bi bi-info-circle" aria-hidden="true"></i> Saved payment methods control the options shown on cart, checkout and payment pages.</p>

