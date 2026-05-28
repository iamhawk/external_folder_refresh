<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Controller;

use OCA\ExternalFolderRefresh\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use Throwable;

class SettingsController extends Controller {
    public function __construct(
        string $AppName,
        IRequest $request,
        private SettingsService $settingsService,
        private IGroupManager $groupManager,
        private ?string $UserId
    ) {
        parent::__construct($AppName, $request);
    }

    public function save(
        string $allowedPrefixes = "/Yandex\n",
        string $allowedGroups = "",
        string $phpCli = "/usr/local/bin/php",
        string $occPath = "/var/www/html/occ",
        int $userCooldownSeconds = 30,
        int $folderCooldownSeconds = 60,
        int $scanTimeoutSeconds = 120
    ): DataResponse {
        if ($this->UserId === null || !$this->groupManager->isAdmin($this->UserId)) {
            return new DataResponse([
                'ok' => false,
                'message' => 'Admin privileges are required.',
            ], Http::STATUS_FORBIDDEN);
        }

        try {
            $this->settingsService->save(
                $allowedPrefixes,
                $allowedGroups,
                $phpCli,
                $occPath,
                $userCooldownSeconds,
                $folderCooldownSeconds,
                $scanTimeoutSeconds
            );

            return new DataResponse([
                'ok' => true,
                'message' => 'Settings saved.',
            ]);
        } catch (Throwable $e) {
            return new DataResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
