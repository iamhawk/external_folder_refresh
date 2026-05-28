<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Settings;

use OCA\ExternalFolderRefresh\AppInfo\Application;
use OCA\ExternalFolderRefresh\Service\SettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function getForm(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'admin');
        Util::addStyle(Application::APP_ID, 'admin');

        return new TemplateResponse(Application::APP_ID, 'admin', [
            'allowed_prefixes' => $this->settingsService->getAllowedPrefixesRaw(),
            'allowed_groups' => $this->settingsService->getAllowedGroupsRaw(),
            'php_cli' => $this->settingsService->getPhpCli(),
            'occ_path' => $this->settingsService->getOccPath(),
            'user_cooldown_seconds' => $this->settingsService->getUserCooldownSeconds(),
            'folder_cooldown_seconds' => $this->settingsService->getFolderCooldownSeconds(),
            'scan_timeout_seconds' => $this->settingsService->getScanTimeoutSeconds(),
        ], '');
    }

    public function getSection(): string {
        return 'external_folder_refresh';
    }

    public function getPriority(): int {
        return 10;
    }
}
