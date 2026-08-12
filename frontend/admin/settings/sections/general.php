        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card" id="general-settings">
            <h2>General Settings</h2>
            <p>Manage your website general preferences and configurations.</p>

            <label class="af-settings-field" for="site_name">
                <span>Website Name</span>
                <input id="site_name" name="site_name" type="text" value="<?php echo htmlspecialchars((string) ($website['site_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>

            <label class="af-settings-field" for="site_tagline">
                <span>Website Tagline</span>
                <input id="site_tagline" name="site_tagline" type="text" value="<?php echo htmlspecialchars((string) ($website['site_tagline'] ?? 'Delicious meals, delivered with love.'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="hero_title">
                <span>Homepage Hero Title</span>
                <input id="hero_title" name="hero_title" type="text" value="<?php echo htmlspecialchars((string) ($website['hero_title'] ?? 'Exceptional Food Memorable Moments'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="hero_subtitle">
                <span>Homepage Hero Subtitle</span>
                <textarea id="hero_subtitle" name="hero_subtitle" rows="3"><?php echo htmlspecialchars((string) ($website['hero_subtitle'] ?? 'We provide delicious meals and professional catering services for all occasions.'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <div class="af-settings-color-grid">
                <label class="af-settings-field" for="primary_color">
                    <span>Primary Gold</span>
                    <input id="primary_color" name="primary_color" type="color" value="<?php echo htmlspecialchars(afrisense_settings_color_value($website, 'primary_color', '#b77b1a'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label class="af-settings-field" for="secondary_color">
                    <span>Secondary Gold</span>
                    <input id="secondary_color" name="secondary_color" type="color" value="<?php echo htmlspecialchars(afrisense_settings_color_value($website, 'secondary_color', '#cc8f25'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label class="af-settings-field" for="forest_green">
                    <span>Forest Green</span>
                    <input id="forest_green" name="forest_green" type="color" value="<?php echo htmlspecialchars(afrisense_settings_color_value($website, 'forest_green', '#0d241e'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label class="af-settings-field" for="forest_green_2">
                    <span>Deep Forest Green</span>
                    <input id="forest_green_2" name="forest_green_2" type="color" value="<?php echo htmlspecialchars(afrisense_settings_color_value($website, 'forest_green_2', '#0c231d'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>
        </section>


        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card">
            <h2>Footer Text</h2>
            <p>This text will be displayed in the website footer.</p>
            <label class="af-settings-field" for="footer_text">
                <span>Footer Text</span>
                <textarea id="footer_text" name="footer_text" rows="6"><?php echo htmlspecialchars((string) ($website['footer_text'] ?? '(c) 2026 AfriSense Food Services. All rights reserved.'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </section>
