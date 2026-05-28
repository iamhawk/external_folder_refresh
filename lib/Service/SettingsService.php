<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Service;

use OCA\ExternalFolderRefresh\AppInfo\Application;
use OCP\IConfig;

class SettingsService {
    public function __construct(
        private IConfig $config
    ) {
    }

    public function getAllowedPrefixesRaw(): string {
        return $this->config->getAppValue(Application::APP_ID, 'allowed_prefixes', "/Yandex\n");
    }

    public function getAllowedPrefixes(): array {
        return $this->linesToArray($this->getAllowedPrefixesRaw());
    }

    public function getAllowedGroupsRaw(): string {
        return $this->config->getAppValue(Application::APP_ID, 'allowed_groups', "");
    }

    public function getAllowedGroups(): array {
        return $this->linesToArray($this->getAllowedGroupsRaw());
    }

    public function getPhpCli(): string {
        return $this->config->getAppValue(Application::APP_ID, 'php_cli', '/usr/local/bin/php');
    }

    public function getOccPath(): string {
        return $this->config->getAppValue(Application::APP_ID, 'occ_path', '/var/www/html/occ');
    }

    public function getUserCooldownSeconds(): int {
        return $this->getIntValue('user_cooldown_seconds', 30, 0, 3600);
    }

    public function getFolderCooldownSeconds(): int {
        return $this->getIntValue('folder_cooldown_seconds', 60, 0, 3600);
    }

    public function getScanTimeoutSeconds(): int {
        return $this->getIntValue('scan_timeout_seconds', 120, 10, 900);
    }

    public function save(
        string $allowedPrefixes,
        string $allowedGroups,
        string $phpCli,
        string $occPath,
        int $userCooldownSeconds,
        int $folderCooldownSeconds,
        int $scanTimeoutSeconds
    ): void {
        $allowedPrefixes = $this->normalizeMultiline($allowedPrefixes, "/Yandex\n");
        $allowedGroups = $this->normalizeMultiline($allowedGroups, "");
        $phpCli = trim($phpCli);
        $occPath = trim($occPath);

        if ($phpCli === '') {
            $phpCli = '/usr/local/bin/php';
        }

        if ($occPath === '') {
            $occPath = '/var/www/html/occ';
        }

        $this->config->setAppValue(Application::APP_ID, 'allowed_prefixes', $allowedPrefixes);
        $this->config->setAppValue(Application::APP_ID, 'allowed_groups', $allowedGroups);
        $this->config->setAppValue(Application::APP_ID, 'php_cli', $phpCli);
        $this->config->setAppValue(Application::APP_ID, 'occ_path', $occPath);
        $this->config->setAppValue(Application::APP_ID, 'user_cooldown_seconds', (string)$this->clamp($userCooldownSeconds, 0, 3600));
        $this->config->setAppValue(Application::APP_ID, 'folder_cooldown_seconds', (string)$this->clamp($folderCooldownSeconds, 0, 3600));
        $this->config->setAppValue(Application::APP_ID, 'scan_timeout_seconds', (string)$this->clamp($scanTimeoutSeconds, 10, 900));
    }

    private function getIntValue(string $key, int $default, int $min, int $max): int {
        $value = (int)$this->config->getAppValue(Application::APP_ID, $key, (string)$default);
        return $this->clamp($value, $min, $max);
    }

    private function clamp(int $value, int $min, int $max): int {
        return max($min, min($max, $value));
    }

    private function normalizeMultiline(string $value, string $default): string {
        $items = $this->linesToArray($value);

        if ($items === [] && $default !== '') {
            return $default;
        }

        if ($items === []) {
            return "";
        }

        return implode("\n", $items) . "\n";
    }

    private function linesToArray(string $value): array {
        $items = [];

        foreach (preg_split('/\R/', $value) as $line) {
            $line = trim((string)$line);

            if ($line === '') {
                continue;
            }

            $items[] = $line;
        }

        return array_values(array_unique($items));
    }
}
