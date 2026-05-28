(function () {
    'use strict';

    const APP_ID = 'external_folder_refresh';

    function showStatus(message, isError) {
        const status = document.getElementById('external-folder-refresh-admin-status');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.toggle('external-folder-refresh-admin-status-error', Boolean(isError));
        status.classList.toggle('external-folder-refresh-admin-status-ok', !isError);
    }

    async function saveSettings(event) {
        event.preventDefault();

        const form = event.currentTarget;

        const payload = {
            allowedPrefixes: form.allowedPrefixes.value,
            allowedGroups: form.allowedGroups.value,
            phpCli: form.phpCli.value,
            occPath: form.occPath.value,
            userCooldownSeconds: Number(form.userCooldownSeconds.value),
            folderCooldownSeconds: Number(form.folderCooldownSeconds.value),
            scanTimeoutSeconds: Number(form.scanTimeoutSeconds.value),
        };

        showStatus('Saving...', false);

        try {
            const response = await fetch(OC.generateUrl('/apps/' + APP_ID + '/settings'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Unable to save settings.');
            }

            showStatus('Settings saved.', false);
        } catch (error) {
            showStatus(error.message || 'Unknown error.', true);
        }
    }

    function init() {
        const form = document.getElementById('external-folder-refresh-admin-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', saveSettings);
    }

    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
