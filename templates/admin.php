<div id="external-folder-refresh-admin" class="external-folder-refresh-admin">
    <h2>External Folder Refresh</h2>

    <p>
        Adds a refresh button to the Files interface. The button performs a shallow scan of the current folder only.
    </p>

    <form id="external-folder-refresh-admin-form">
        <div class="external-folder-refresh-field">
            <label for="external-folder-refresh-allowed-prefixes">
                Allowed folder prefixes
            </label>
            <textarea id="external-folder-refresh-allowed-prefixes" name="allowedPrefixes" rows="5"><?php p($_['allowed_prefixes']); ?></textarea>
            <p class="external-folder-refresh-help">
                One path per line. Default: /Yandex. The root folder is not allowed.
            </p>
        </div>

        <div class="external-folder-refresh-field">
            <label for="external-folder-refresh-allowed-groups">
                Allowed groups
            </label>
            <textarea id="external-folder-refresh-allowed-groups" name="allowedGroups" rows="4"><?php p($_['allowed_groups']); ?></textarea>
            <p class="external-folder-refresh-help">
                One group id per line. Leave empty to allow all authenticated users.
            </p>
        </div>

        <div class="external-folder-refresh-grid">
            <div class="external-folder-refresh-field">
                <label for="external-folder-refresh-php-cli">PHP CLI path</label>
                <input id="external-folder-refresh-php-cli" name="phpCli" type="text" value="<?php p($_['php_cli']); ?>">
            </div>

            <div class="external-folder-refresh-field">
                <label for="external-folder-refresh-occ-path">occ path</label>
                <input id="external-folder-refresh-occ-path" name="occPath" type="text" value="<?php p($_['occ_path']); ?>">
            </div>

            <div class="external-folder-refresh-field">
                <label for="external-folder-refresh-user-cooldown">User cooldown, seconds</label>
                <input id="external-folder-refresh-user-cooldown" name="userCooldownSeconds" type="number" min="0" max="3600" value="<?php p($_['user_cooldown_seconds']); ?>">
            </div>

            <div class="external-folder-refresh-field">
                <label for="external-folder-refresh-folder-cooldown">Folder cooldown, seconds</label>
                <input id="external-folder-refresh-folder-cooldown" name="folderCooldownSeconds" type="number" min="0" max="3600" value="<?php p($_['folder_cooldown_seconds']); ?>">
            </div>

            <div class="external-folder-refresh-field">
                <label for="external-folder-refresh-timeout">Scan timeout, seconds</label>
                <input id="external-folder-refresh-timeout" name="scanTimeoutSeconds" type="number" min="10" max="900" value="<?php p($_['scan_timeout_seconds']); ?>">
            </div>
        </div>

        <button type="submit" class="primary">
            Save settings
        </button>

        <span id="external-folder-refresh-admin-status"></span>
    </form>
</div>
