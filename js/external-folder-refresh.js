(function () {
    'use strict';

    const APP_ID = 'external_folder_refresh';
    const BUTTON_ID = 'external-folder-refresh-button';

    let appConfig = {
        allowed_prefixes: ['/Yandex'],
    };

    function isFilesApp() {
        return window.location.pathname.indexOf('/apps/files') !== -1;
    }

    function normalizeDir(dir) {
        if (!dir || dir.trim() === '') {
            return '/';
        }

        dir = dir.trim();

        try {
            dir = decodeURIComponent(dir);
        } catch (error) {
        }

        dir = dir.replace(/\\/g, '/');

        if (dir.charAt(0) !== '/') {
            dir = '/' + dir;
        }

        dir = dir.replace(/\/+/g, '/');

        if (dir.length > 1 && dir.endsWith('/')) {
            dir = dir.slice(0, -1);
        }

        return dir;
    }

    function getCurrentDir() {
        const url = new URL(window.location.href);
        const dirFromQuery = url.searchParams.get('dir');

        if (dirFromQuery) {
            return normalizeDir(dirFromQuery);
        }

        const hash = window.location.hash || '';
        const hashMatch = hash.match(/[?&]dir=([^&]+)/);

        if (hashMatch && hashMatch[1]) {
            return normalizeDir(hashMatch[1]);
        }

        return '/';
    }

    function isAllowedDir(dir) {
        const normalizedDir = normalizeDir(dir);

        return (appConfig.allowed_prefixes || []).some(function (prefix) {
            prefix = normalizeDir(prefix);
            return normalizedDir === prefix || normalizedDir.indexOf(prefix + '/') === 0;
        });
    }

    function showMessage(message, isError) {
        if (window.OC && OC.Notification && typeof OC.Notification.showTemporary === 'function') {
            OC.Notification.showTemporary(message);
            return;
        }

        if (isError) {
            window.alert(message);
        } else {
            console.log(message);
        }
    }

    function setButtonState(button, isBusy) {
        if (isBusy) {
            button.disabled = true;
            button.classList.add('external-folder-refresh-button-busy');
            button.textContent = 'Refreshing...';
        } else {
            button.disabled = false;
            button.classList.remove('external-folder-refresh-button-busy');
            button.textContent = 'Refresh folder';
        }
    }

    function updateButtonVisibility() {
        const button = document.getElementById(BUTTON_ID);

        if (!button) {
            return;
        }

        const dir = getCurrentDir();
        const visible = isFilesApp() && isAllowedDir(dir);

        button.style.display = visible ? 'inline-flex' : 'none';
        button.title = visible
            ? 'Refresh this folder without scanning subfolders'
            : 'Open an allowed external folder to use refresh';
    }

    async function loadConfig() {
        try {
            const response = await fetch(OC.generateUrl('/apps/' + APP_ID + '/config'), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'requesttoken': OC.requestToken || '',
                },
            });

            const data = await response.json();

            if (response.ok && data.ok && Array.isArray(data.allowed_prefixes)) {
                appConfig = data;
            }
        } catch (error) {
            appConfig = {
                allowed_prefixes: ['/Yandex'],
            };
        }
    }

    async function scanCurrentFolder(button) {
        const dir = getCurrentDir();

        if (!isAllowedDir(dir)) {
            showMessage('Folder refresh is available only inside configured external folders.', true);
            return;
        }

        setButtonState(button, true);

        try {
            const response = await fetch(OC.generateUrl('/apps/' + APP_ID + '/scan'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken || '',
                },
                body: JSON.stringify({
                    dir: dir,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                let message = data.message || 'Folder refresh failed.';

                if (data.stderr) {
                    message = message + '\n' + data.stderr;
                }

                throw new Error(message);
            }

            showMessage('Folder refreshed: ' + dir, false);

            window.setTimeout(function () {
                window.location.reload();
            }, 700);
        } catch (error) {
            showMessage(error.message || 'Unknown refresh error.', true);
            setButtonState(button, false);
        }
    }

    function createButton() {
        if (document.getElementById(BUTTON_ID)) {
            return;
        }

        const button = document.createElement('button');

        button.id = BUTTON_ID;
        button.type = 'button';
        button.textContent = 'Refresh folder';
        button.addEventListener('click', function () {
            scanCurrentFolder(button);
        });

        document.body.appendChild(button);
        updateButtonVisibility();
    }

    async function init() {
        if (!isFilesApp()) {
            return;
        }

        await loadConfig();
        createButton();
        updateButtonVisibility();

        window.setInterval(updateButtonVisibility, 1000);
    }

    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.setTimeout(init, 1200);
})();
