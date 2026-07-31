        <section class="af-settings-card af-settings-card-wide" id="company-information">
            <h2>Company Information</h2>
            <p>Update your company details and contact information shown across the website.</p>

            <div class="af-settings-two">
                <label class="af-settings-field" for="company_name">
                    <span>Company / Business Name</span>
                    <input id="company_name" name="company_name" type="text" value="<?php echo htmlspecialchars((string) ($company['company_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="company_tagline">
                    <span>Tagline</span>
                    <input id="company_tagline" type="text" value="<?php echo htmlspecialchars((string) ($website['site_tagline'] ?? 'Delicious meals, delivered with love.'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="company_email">
                    <span>Business Email</span>
                    <input id="company_email" name="company_email" type="email" value="<?php echo htmlspecialchars((string) ($company['company_email'] ?? 'info@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="support_email">
                    <span>Customer Support Email</span>
                    <input id="support_email" name="support_email" type="email" value="<?php echo htmlspecialchars((string) ($company['support_email'] ?? 'support@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="phone_number_1">
                    <span>Business Phone</span>
                    <input id="phone_number_1" name="phone_number_1" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_1'] ?? '+233 24 123 4567'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="phone_number_2">
                    <span>Alternative Phone</span>
                    <input id="phone_number_2" name="phone_number_2" type="tel" value="<?php echo htmlspecialchars((string) ($company['phone_number_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="address">
                    <span>Business Address</span>
                    <input id="address" name="address" type="text" value="<?php echo htmlspecialchars((string) ($company['address'] ?? 'East Legon, Accra, Ghana'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label class="af-settings-field" for="city">
                    <span>City</span>
                    <input id="city" name="city" type="text" value="<?php echo htmlspecialchars((string) ($company['city'] ?? 'Accra'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div class="af-settings-two">
                <label class="af-settings-field" for="region">
                    <span>Region</span>
                    <input id="region" name="region" type="text" value="<?php echo htmlspecialchars((string) ($company['region'] ?? 'Greater Accra Region'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label class="af-settings-field" for="country">
                    <span>Country</span>
                    <input id="country" name="country" type="text" value="<?php echo htmlspecialchars((string) ($company['country'] ?? 'Ghana'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <label class="af-settings-field" for="google_map_iframe">
                <span>Google Map Embed</span>
                <textarea id="google_map_iframe" name="google_map_iframe" rows="3" placeholder="Paste Google Maps iframe code or map URL"><?php echo htmlspecialchars((string) ($company['google_map_iframe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>

        <section class="af-settings-card af-company-assets-card">
            <?php
            $logoUrl = afrisense_settings_asset_url($frontendBase, (string) ($website['logo'] ?? ''));
            $faviconUrl = afrisense_settings_asset_url($frontendBase, (string) ($website['favicon'] ?? ''));
            ?>
            <h2>Company Logo</h2>
            <p>Upload your company logo. Recommended size: 300 x 100px.</p>
            <div class="af-logo-preview">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Company logo">
                <?php else: ?>
                    <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                    <strong>Afri<span>Sense</span></strong>
                    <small>Food Services</small>
                <?php endif; ?>
            </div>
            <div class="af-settings-actions">
                <label class="af-settings-upload-btn" for="logo_file">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    Change Logo
                </label>
                <input id="logo_file" name="logo_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                <button class="danger" name="remove_logo" value="1" type="submit" formnovalidate>
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    Remove
                </button>
            </div>

            <h2>Favicon</h2>
            <p>Upload favicon for your website. Recommended size: 32 x 32px.</p>
            <div class="af-favicon-preview">
                <?php if ($faviconUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Website favicon">
                <?php else: ?>
                    <span class="af-brand-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
                <?php endif; ?>
            </div>
            <div class="af-settings-actions">
                <label class="af-settings-upload-btn" for="favicon_file">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    Change Favicon
                </label>
                <input id="favicon_file" name="favicon_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon">
                <button class="danger" name="remove_favicon" value="1" type="submit" formnovalidate>
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    Remove
                </button>
            </div>
        </section>

        <section class="af-settings-card af-settings-card-wide">
            <h2>Business Hours</h2>
            <p>Set your business operating hours as they appear on the website.</p>
            <div class="af-business-hours-editor">
                <?php
                $businessHoursText = (string) ($company['business_hours'] ?? "Monday - Friday: 8:00 AM - 10:00 PM\nSaturday: 9:00 AM - 11:00 PM\nSunday: 10:00 AM - 9:00 PM");
                ?>
                <textarea id="business_hours" name="business_hours" rows="7"><?php echo htmlspecialchars($businessHoursText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="af-business-hours-guide" aria-hidden="true">
                    <span><strong>Monday - Friday</strong><em>08:00 AM - 10:00 PM</em></span>
                    <span><strong>Saturday</strong><em>09:00 AM - 11:00 PM</em></span>
                    <span><strong>Sunday</strong><em>10:00 AM - 09:00 PM</em></span>
                </div>
            </div>
        </section>

        <section class="af-settings-card">
            <h2>Other Information</h2>
            <p>Additional company information available in the current database.</p>
            <label class="af-settings-field" for="other_currency">
                <span>Currency</span>
                <?php $companyCurrency = (string) ($system['default_currency'] ?? 'GHS'); ?>
                <select id="other_currency" name="default_currency">
                    <option value="GHS" <?php echo $companyCurrency === 'GHS' ? 'selected' : ''; ?>>GHS (GHc) - Ghana Cedi</option>
                    <option value="USD" <?php echo $companyCurrency === 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                </select>
            </label>
            <label class="af-settings-field" for="other_timezone">
                <span>Default Timezone</span>
                <?php $companyTimezone = (string) ($system['timezone'] ?? 'Africa/Accra'); ?>
                <select id="other_timezone" name="timezone">
                    <option value="Africa/Accra" <?php echo $companyTimezone === 'Africa/Accra' ? 'selected' : ''; ?>>(GMT+00:00) Accra, Ghana</option>
                    <option value="UTC" <?php echo $companyTimezone === 'UTC' ? 'selected' : ''; ?>>(GMT+00:00) UTC</option>
                </select>
            </label>
            <label class="af-settings-field" for="other_support_email">
                <span>Support Email</span>
                <input id="other_support_email" type="email" value="<?php echo htmlspecialchars((string) ($company['support_email'] ?? 'support@afrisense.com'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </label>
        </section>

