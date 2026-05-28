<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Controller;

use OCA\ExternalFolderRefresh\Service\PathService;
use OCA\ExternalFolderRefresh\Service\RateLimitService;
use OCA\ExternalFolderRefresh\Service\ScannerService;
use OCA\ExternalFolderRefresh\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;

class ScanController extends Controller {
    public function __construct(
        string $AppName,
        IRequest $request,
        private SettingsService $settingsService,
        private PathService $pathService,
        private RateLimitService $rateLimitService,
        private ScannerService $scannerService,
        private IGroupManager $groupManager,
        private IUserManager $userManager,
        private LoggerInterface $logger,
        private ?string $UserId
    ) {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     */
    public function config(): DataResponse {
        return new DataResponse([
            'ok' => true,
            'allowed_prefixes' => $this->settingsService->getAllowedPrefixes(),
            'user_cooldown_seconds' => $this->settingsService->getUserCooldownSeconds(),
            'folder_cooldown_seconds' => $this->settingsService->getFolderCooldownSeconds(),
        ]);
    }

    /**
     * @NoAdminRequired
     */
    public function scan(string $dir = '/'): DataResponse {
        if ($this->UserId === null || $this->UserId === '') {
            return new DataResponse([
                'ok' => false,
                'message' => 'User is not authenticated.',
            ], Http::STATUS_UNAUTHORIZED);
        }

        if (!$this->isUserAllowed($this->UserId)) {
            return new DataResponse([
                'ok' => false,
                'message' => 'This user is not allowed to refresh external folders.',
            ], Http::STATUS_FORBIDDEN);
        }

        try {
            $normalizedDir = $this->pathService->normalizeDir($dir);
            $allowedPrefixes = $this->settingsService->getAllowedPrefixes();

            if (!$this->pathService->isInsideAllowedPrefixes($normalizedDir, $allowedPrefixes)) {
                return new DataResponse([
                    'ok' => false,
                    'message' => 'Folder refresh is allowed only inside configured external folders.',
                    'dir' => $normalizedDir,
                    'allowed_prefixes' => $allowedPrefixes,
                ], Http::STATUS_FORBIDDEN);
            }

            $rateLimit = $this->rateLimitService->checkAndTouch($this->UserId, $normalizedDir);

            if (!$rateLimit['ok']) {
                return new DataResponse([
                    'ok' => false,
                    'message' => $rateLimit['message'],
                ], Http::STATUS_TOO_MANY_REQUESTS);
            }

            $scanPath = $this->pathService->buildFilesScanPath($this->UserId, $normalizedDir);
            $lockPath = sys_get_temp_dir() . '/external-folder-refresh-' . sha1($this->UserId . ':' . $normalizedDir) . '.lock';

            $lockHandle = fopen($lockPath, 'c');

            if ($lockHandle === false) {
                return new DataResponse([
                    'ok' => false,
                    'message' => 'Unable to create scan lock.',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                fclose($lockHandle);

                return new DataResponse([
                    'ok' => false,
                    'message' => 'This folder is already being refreshed.',
                ], Http::STATUS_TOO_MANY_REQUESTS);
            }

            $startedAt = microtime(true);

            try {
                $result = $this->scannerService->scanShallow($scanPath);
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }

            $elapsedSeconds = round(microtime(true) - $startedAt, 2);

            if ($result['exit_code'] !== 0) {
                $this->logger->warning('External folder shallow scan failed', [
                    'app' => 'external_folder_refresh',
                    'user' => $this->UserId,
                    'dir' => $normalizedDir,
                    'scanPath' => $scanPath,
                    'exitCode' => $result['exit_code'],
                    'stderr' => $result['stderr'],
                ]);

                return new DataResponse([
                    'ok' => false,
                    'message' => 'Folder refresh failed.',
                    'dir' => $normalizedDir,
                    'scan_path' => $scanPath,
                    'exit_code' => $result['exit_code'],
                    'stdout' => $result['stdout'],
                    'stderr' => $result['stderr'],
                    'elapsed_seconds' => $elapsedSeconds,
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return new DataResponse([
                'ok' => true,
                'message' => 'Folder refreshed.',
                'dir' => $normalizedDir,
                'scan_path' => $scanPath,
                'elapsed_seconds' => $elapsedSeconds,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('External folder refresh error', [
                'app' => 'external_folder_refresh',
                'exception' => $e,
            ]);

            return new DataResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    private function isUserAllowed(string $userId): bool {
        $allowedGroups = $this->settingsService->getAllowedGroups();

        if ($allowedGroups === []) {
            return true;
        }

        $user = $this->userManager->get($userId);

        if ($user === null) {
            return false;
        }

        foreach ($allowedGroups as $groupId) {
            $group = $this->groupManager->get($groupId);

            if ($group !== null && $group->inGroup($user)) {
                return true;
            }
        }

        return false;
    }
}
