        <section class="af-settings-card af-settings-card-wide" id="seo-settings">
            <h2>SEO &amp; Analytics</h2>
            <p>Manage the SEO values currently supported by the existing website settings schema.</p>

            <div class="af-seo-settings-layout">
                <div class="af-seo-form-panel">
                    <h3>SEO Settings</h3>
                    <div class="af-settings-two">
                        <label class="af-settings-field" for="seo_site_title">
                            <span>Site Title</span>
                            <input id="seo_site_title" type="text" value="<?php echo htmlspecialchars((string) ($website['site_name'] ?? 'AfriSense Food Services'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            <small>Uses Website Name from General Settings.</small>
                        </label>

                        <label class="af-settings-field" for="seo_meta_description">
                            <span>Meta Description</span>
                            <textarea id="seo_meta_description" rows="3" readonly><?php echo htmlspecialchars((string) ($website['hero_subtitle'] ?? $website['site_tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <small>Uses Homepage Hero Subtitle from General Settings.</small>
                        </label>
                    </div>

                    <div class="af-settings-two">
                        <label class="af-settings-field" for="seo_canonical">
                            <span>Canonical URL</span>
                            <input id="seo_canonical" type="text" value="/Afrisense/frontend/landing/index.php" readonly>
                        </label>

                        <label class="af-settings-field" for="seo_theme_color">
                            <span>Theme Color</span>
                            <input id="seo_theme_color" type="text" value="<?php echo htmlspecialchars((string) ($website['primary_color'] ?? '#b77b1a'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </label>
                    </div>

                    <label class="af-settings-field" for="seo_head_preview">
                        <span>Current Homepage Head Output</span>
                        <textarea id="seo_head_preview" rows="5" readonly><?php echo htmlspecialchars('<title>' . (string) ($website['site_name'] ?? 'AfriSense Food Services') . '</title>' . "\n" . '<meta name="description" content="' . (string) ($website['hero_subtitle'] ?? $website['site_tagline'] ?? '') . '">' . "\n" . '<meta name="theme-color" content="' . (string) ($website['primary_color'] ?? '#b77b1a') . '">', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                </div>

                <aside class="af-seo-analytics-panel">
                    <h3>Analytics Overview</h3>
                    <p>Live analytics storage is not present in the current database schema.</p>
                    <div class="af-seo-stats">
                        <article><i class="bi bi-people"></i><strong>0</strong><span>Total Visitors</span></article>
                        <article><i class="bi bi-eye"></i><strong>0</strong><span>Page Views</span></article>
                        <article><i class="bi bi-activity"></i><strong>N/A</strong><span>Bounce Rate</span></article>
                        <article><i class="bi bi-clock"></i><strong>N/A</strong><span>Avg. Session</span></article>
                    </div>
                    <div class="af-seo-chart-placeholder">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span>Add analytics storage or a Google Analytics integration to populate this chart.</span>
                    </div>
                </aside>
            </div>

            <p class="af-settings-note"><i class="bi bi-info-circle" aria-hidden="true"></i> The database has no dedicated SEO columns, so this section maps to existing website fields instead of creating unsupported settings.</p>
        </section>

