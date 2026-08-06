        <?php
        $baseDeliveryFee = (float) ($system['delivery_fee'] ?? 10.00);
        $serviceFee = (float) ($system['service_fee'] ?? 5.00);
        $freeDeliveryOver = (float) ($system['free_delivery_over'] ?? 275.00);
        $defaultDeliveryTime = (string) ($system['default_delivery_time'] ?? '30 - 45 minutes');
        $maximumDeliveryTime = (string) ($system['maximum_delivery_time'] ?? '90 minutes');
        $orderCutoffTime = (string) ($system['order_cutoff_time'] ?? '22:00');
        $deliveryInstructions = (string) ($system['delivery_instructions'] ?? 'Please ensure someone is available to receive the order at the delivery address. We will contact you when we are on our way.');
        $deliveryZones = afrisense_delivery_zones($system);
        ?>
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card af-settings-card-wide" id="delivery-settings">
            <h2>Delivery Settings</h2>
            <p>Configure the delivery rules used by carts, checkout, payment pages and order totals.</p>

            <div class="af-delivery-settings-layout">
                <div class="af-delivery-zones">
                    <div class="af-settings-section-heading">
                        <h3>Delivery Zones</h3>
                        <button type="button" data-add-delivery-zone><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Zone</button>
                    </div>
                    <div class="af-delivery-zone-table" data-delivery-zone-table>
                        <div><strong>Zone Name</strong><strong>Areas / Locations</strong><strong>Delivery Fee</strong><strong>Min. Order</strong><strong>Status</strong><strong>Action</strong></div>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($deliveryZones as $zone): ?>
                            <div data-delivery-zone-row>
                                <span><input name="delivery_zones[name][]" type="text" value="<?php echo htmlspecialchars((string) $zone['name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Zone name"></span>
                                <span><input name="delivery_zones[areas][]" type="text" value="<?php echo htmlspecialchars((string) $zone['areas'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Areas / locations"></span>
                                <span><input name="delivery_zones[fee][]" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format((float) $zone['fee'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"></span>
                                <span><input name="delivery_zones[min_order][]" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format((float) $zone['min_order'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"></span>
                                <span>
                                    <select name="delivery_zones[status][]">
                                        <option value="Active" <?php echo (string) $zone['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo (string) $zone['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </span>
                                <span><button type="button" class="af-icon-action danger" data-remove-delivery-zone title="Remove zone"><i class="bi bi-trash" aria-hidden="true"></i></button></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="af-delivery-options">
                    <h3>Delivery Options</h3>
                    <label class="af-toggle-row">
                        <input type="checkbox" name="standard_delivery_enabled" value="1" <?php echo (int) ($system['standard_delivery_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Standard Delivery</strong>
                        <small>Regular delivery within estimated time.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" name="express_delivery_enabled" value="1" <?php echo (int) ($system['express_delivery_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Express Delivery</strong>
                        <small>Faster delivery in a shorter time.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" name="scheduled_delivery_enabled" value="1" <?php echo (int) ($system['scheduled_delivery_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Scheduled Delivery</strong>
                        <small>Allow customers to schedule delivery.</small>
                    </label>
                    <label class="af-toggle-row">
                        <input type="checkbox" name="pickup_enabled" value="1" <?php echo (int) ($system['pickup_enabled'] ?? 1) === 1 ? 'checked' : ''; ?>>
                        <span class="af-switch" aria-hidden="true"></span>
                        <strong>Pickup / Self Collection</strong>
                        <small>Allow customers to pick up their orders.</small>
                    </label>
                </div>
            </div>

            <div class="af-settings-two">
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-delivery-mini-card">
                    <h3>Delivery Settings</h3>
                    <div class="af-settings-two">
                        <label class="af-settings-field">
                            <span>Default Delivery Time</span>
                            <input type="text" name="default_delivery_time" value="<?php echo htmlspecialchars($defaultDeliveryTime, ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="af-settings-field">
                            <span>Maximum Delivery Time</span>
                            <input type="text" name="maximum_delivery_time" value="<?php echo htmlspecialchars($maximumDeliveryTime, ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>
                    <div class="af-settings-two">
                        <label class="af-settings-field">
                            <span>Order Cut-off Time</span>
                            <input type="time" name="order_cutoff_time" value="<?php echo htmlspecialchars($orderCutoffTime, ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="af-settings-field">
                            <span>Free Delivery Over</span>
                            <input type="number" name="free_delivery_over" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format($freeDeliveryOver, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>
                    <div class="af-settings-two">
                        <label class="af-settings-field">
                            <span>Base Delivery Fee</span>
                            <input type="number" name="delivery_fee" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format($baseDeliveryFee, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="af-settings-field">
                            <span>Service Fee</span>
                            <input type="number" name="service_fee" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format($serviceFee, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>
                    <label class="af-toggle-row"><input type="checkbox" name="same_day_delivery" value="1" <?php echo (int) ($system['same_day_delivery'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Same Day Delivery</strong><small>Allow same-day delivery for orders.</small></label>
                    <label class="af-toggle-row"><input type="checkbox" name="weekend_delivery" value="1" <?php echo (int) ($system['weekend_delivery'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Weekend Delivery</strong><small>Enable delivery on weekends.</small></label>
                    <label class="af-toggle-row"><input type="checkbox" name="real_time_tracking" value="1" <?php echo (int) ($system['real_time_tracking'] ?? 1) === 1 ? 'checked' : ''; ?>><span class="af-switch" aria-hidden="true"></span><strong>Real-time Tracking</strong><small>Enable order tracking for customers.</small></label>
                </section>

                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-delivery-mini-card">
                    <h3>Delivery Instructions</h3>
                    <textarea name="delivery_instructions" rows="5"><?php echo htmlspecialchars($deliveryInstructions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </section>
            </div>

            <p class="af-settings-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Delivery changes take effect immediately on cart, checkout, order and payment pages after saving.</p>
        </section>

