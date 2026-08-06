        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-settings-card" id="email-settings">
            <h2>Email Settings</h2>
            <p>Configure SMTP values used by system emails and verification messages.</p>

            <label class="af-settings-field" for="smtp_host">
                <span>SMTP Host</span>
                <input id="smtp_host" name="smtp_host" type="text" value="<?php echo htmlspecialchars((string) ($system['smtp_host'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_port">
                <span>SMTP Port</span>
                <input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" value="<?php echo htmlspecialchars((string) ($system['smtp_port'] ?? '587'), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_username">
                <span>SMTP Username</span>
                <input id="smtp_username" name="smtp_username" type="text" value="<?php echo htmlspecialchars((string) ($system['smtp_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_password">
                <span>SMTP Password</span>
                <input id="smtp_password" name="smtp_password" type="password" value="<?php echo htmlspecialchars((string) ($system['smtp_password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label class="af-settings-field" for="smtp_encryption">
                <span>SMTP Encryption</span>
                <?php $smtpEncryption = (string) ($system['smtp_encryption'] ?? 'tls'); ?>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
                </select>
            </label>
        </section>

