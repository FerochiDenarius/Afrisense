        <?php
        $socialRows = [
            ['key' => 'facebook_url', 'name' => 'Facebook', 'hint' => 'Facebook Page URL', 'icon' => 'bi-facebook', 'class' => 'facebook', 'placeholder' => 'https://facebook.com/afrisensefoods'],
            ['key' => 'instagram_url', 'name' => 'Instagram', 'hint' => 'Instagram Profile URL', 'icon' => 'bi-instagram', 'class' => 'instagram', 'placeholder' => 'https://instagram.com/afrisense_gh'],
            ['key' => 'twitter_url', 'name' => 'Twitter (X)', 'hint' => 'Twitter Profile URL', 'icon' => 'bi-twitter-x', 'class' => 'twitter', 'placeholder' => 'https://twitter.com/afrisense_gh'],
            ['key' => 'linkedin_url', 'name' => 'LinkedIn', 'hint' => 'LinkedIn Company URL', 'icon' => 'bi-linkedin', 'class' => 'linkedin', 'placeholder' => 'https://linkedin.com/company/afrisense-foods'],
            ['key' => 'youtube_url', 'name' => 'YouTube', 'hint' => 'YouTube Channel URL', 'icon' => 'bi-youtube', 'class' => 'youtube', 'placeholder' => 'https://youtube.com/@afrisensefoods'],
            ['key' => 'tiktok_url', 'name' => 'TikTok', 'hint' => 'TikTok Profile URL', 'icon' => 'bi-tiktok', 'class' => 'tiktok', 'placeholder' => 'https://tiktok.com/@afrisense_gh'],
        ];
        ?>
        <section class="af-settings-card af-settings-card-wide af-social-accounts-card" id="social-media">
            <h2>Social Media Accounts</h2>
            <p>Add and manage your social media profiles. These links display on the public website footer.</p>

            <div class="af-social-account-list">
                <?php foreach ($socialRows as $row): ?>
                    <?php $socialValue = (string) ($company[$row['key']] ?? ''); ?>
                    <?php $socialDisabled = (bool) ($row['disabled'] ?? false); ?>
                    <article class="af-social-account <?php echo $socialDisabled ? 'is-disabled' : ''; ?>" <?php echo $socialDisabled ? '' : 'data-social-row'; ?> data-social-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>" data-social-icon="<?php echo htmlspecialchars($row['icon'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="af-social-platform <?php echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?php echo htmlspecialchars($row['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        </span>
                        <div class="af-social-meta">
                            <strong><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo htmlspecialchars($row['hint'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>
                        <input id="<?php echo htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? '' : 'name="' . htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8') . '"'; ?> type="url" value="<?php echo htmlspecialchars($socialValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($row['placeholder'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                        <label class="af-social-toggle" aria-label="Enable <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" <?php echo $socialDisabled ? '' : 'name="social_enabled[' . htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8') . ']" value="1"'; ?> data-social-enabled <?php echo $socialValue !== '' ? 'checked' : ''; ?> <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                            <span class="af-switch" aria-hidden="true"></span>
                        </label>
                        <button type="button" class="af-social-clear" data-clear-input="<?php echo htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Clear <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $socialDisabled ? 'disabled' : ''; ?>>
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </article>
                <?php endforeach; ?>

                <button type="button" class="af-settings-add-line" disabled>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Add New Social Media
                </button>
            </div>
        </section>

        <section class="af-settings-card af-social-preview-card">
            <h2>Social Media Preview</h2>
            <p>This is how your social media links will appear on your website.</p>
            <div class="af-social-preview-box">
                <strong>Follow Us</strong>
                <small>Stay connected with us on social media for updates and offers.</small>
                <div data-social-preview>
                    <?php foreach (afrisense_public_social_links() as $social): ?>
                        <span title="<?php echo htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo htmlspecialchars($social['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <h2>Display Settings</h2>
            <div class="af-settings-two">
                <label class="af-settings-field">
                    <span>Display Position</span>
                    <select disabled><option>Footer</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Display Style</span>
                    <select disabled><option>Icon Only</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Icon Shape</span>
                    <select disabled><option>Rounded</option></select>
                </label>
                <label class="af-settings-field">
                    <span>Icon Size</span>
                    <select disabled><option>Medium</option></select>
                </label>
            </div>
            <label class="af-toggle-row">
                <input type="checkbox" checked disabled>
                <span class="af-switch" aria-hidden="true"></span>
                <strong>Open links in new tab</strong>
                <small>Links will open in a new browser tab.</small>
            </label>
        </section>

        <section class="af-settings-card">
            <h2>Social Share Settings</h2>
            <p>Allow users to share your content on social media.</p>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Enable Social Share Buttons</strong><small>Show social share buttons on public pages.</small></label>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on Facebook</strong><small>Allow sharing on Facebook.</small></label>
            <label class="af-toggle-row"><input type="checkbox" disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on Twitter (X)</strong><small>Allow sharing on Twitter (X).</small></label>
            <label class="af-toggle-row"><input type="checkbox" checked disabled><span class="af-switch" aria-hidden="true"></span><strong>Share on WhatsApp</strong><small>Allow sharing on WhatsApp.</small></label>
        </section>

        <section class="af-settings-card af-settings-card-wide">
            <h2>Custom Links</h2>
            <p>Add any additional social media or custom links.</p>
            <div class="af-custom-social-table">
                <div><strong>Platform Name</strong><strong>Icon Class / URL</strong><strong>Link URL</strong><strong>Status</strong><strong>Action</strong></div>
                <div>
                    <input type="text" value="WhatsApp" readonly>
                    <input type="text" value="bi bi-whatsapp" readonly>
                    <input type="url" value="<?php echo htmlspecialchars('https://wa.me/' . preg_replace('/\D+/', '', (string) ($company['phone_number_1'] ?? '+233241234567')), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    <span class="af-switch is-on" aria-hidden="true"></span>
                    <button type="button" class="af-social-clear" disabled><i class="bi bi-trash" aria-hidden="true"></i></button>
                </div>
            </div>
            <button type="button" class="af-settings-add-line" disabled>
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add Custom Link
            </button>
        </section>
        <p class="af-settings-note af-settings-card-wide"><i class="bi bi-info-circle" aria-hidden="true"></i> Enabled social media links are displayed automatically on the public homepage and footer after saving.</p>

