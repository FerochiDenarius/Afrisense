        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card" id="maintenance-mode">
            <h2>Maintenance Mode</h2>
            <p>Control high-level public website availability and admin listing defaults.</p>

            <label class="af-settings-field" for="timezone">
                <span>Timezone</span>
                <?php $timezone = (string) ($system['timezone'] ?? 'Africa/Accra'); ?>
                <select id="timezone" name="timezone">
                    <option value="Africa/Accra" <?php echo $timezone === 'Africa/Accra' ? 'selected' : ''; ?>>(GMT+00:00) Accra, Ghana</option>
                    <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>(GMT+00:00) UTC</option>
                </select>
            </label>

            <label class="af-toggle-row">
                <input type="checkbox" name="booking_notifications" value="1" <?php echo (int) ($system['booking_notifications'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Booking Notifications</strong>
                <small>Notify administrators when customers book services.</small>
            </label>

            <label class="af-toggle-row">
                <input type="checkbox" name="maintenance_mode" value="1" <?php echo (string) ($system['site_status'] ?? 'Online') === 'Maintenance' ? 'checked' : ''; ?>>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Maintenance Mode</strong>
                <small>Temporarily pause public website activity.</small>
            </label>

            <label class="af-settings-field" for="items_per_page">
                <span>Admin Items Per Page</span>
                <input id="items_per_page" name="items_per_page" type="number" min="5" max="100" value="<?php echo htmlspecialchars((string) ($system['items_per_page'] ?? '10'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </section>

