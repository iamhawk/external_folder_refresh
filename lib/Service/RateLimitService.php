<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Service;

use OCA\ExternalFolderRefresh\AppInfo\Application;
use OCP\IConfig;

class RateLimitService {
    public function __construct(
        private IConfig $config,
        private SettingsService $settingsService
    ) {
    }

    public function checkAndTouch(string $userId, string $dir): array {
        $now = time();

        $userCooldown = $this->settingsService->getUserCooldownSeconds();
        $folderCooldown = $this->settingsService->getFolderCooldownSeconds();

        $userKey = 'rate_user_' . sha1($userId);
        $folderKey = 'rate_folder_' . sha1($userId . ':' . $dir);

        $lastUserRun = (int)$this->config->getAppValue(Application::APP_ID, $userKey, '0');
        $lastFolderRun = (int)$this->config->getAppValue(Application::APP_ID, $folderKey, '0');

        if ($userCooldown > 0 && ($now - $lastUserRun) < $userCooldown) {
            return [
                'ok' => false,
                'message' => 'Too many refresh requests from this user. Try again in ' . ($userCooldown - ($now - $lastUserRun)) . ' seconds.',
            ];
        }

        if ($folderCooldown > 0 && ($now - $lastFolderRun) < $folderCooldown) {
            return [
                'ok' => false,
                'message' => 'This folder was refreshed recently. Try again in ' . ($folderCooldown - ($now - $lastFolderRun)) . ' seconds.',
            ];
        }

        $this->config->setAppValue(Application::APP_ID, $userKey, (string)$now);
        $this->config->setAppValue(Application::APP_ID, $folderKey, (string)$now);

        return [
            'ok' => true,
            'message' => 'Allowed.',
        ];
    }
}
