<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Service;

use RuntimeException;

class ScannerService {
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    public function scanShallow(string $scanPath): array {
        if (!function_exists('proc_open')) {
            throw new RuntimeException('PHP function proc_open is disabled.');
        }

        $phpCli = $this->settingsService->getPhpCli();
        $occPath = $this->settingsService->getOccPath();
        $timeoutSeconds = $this->settingsService->getScanTimeoutSeconds();

        if (!is_file($occPath)) {
            throw new RuntimeException('occ path does not exist: ' . $occPath);
        }

        if (!is_file($phpCli) || !is_executable($phpCli)) {
            throw new RuntimeException('PHP CLI binary is not executable: ' . $phpCli);
        }

        $command = [
            $phpCli,
            $occPath,
            'files:scan',
            '--path=' . $scanPath,
            '--shallow',
            '--quiet',
            '--no-interaction',
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cwd = dirname($occPath);
        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start occ process.');
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = time();

        while (true) {
            $status = proc_get_status($process);

            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if (!$status['running']) {
                break;
            }

            if ((time() - $startedAt) > $timeoutSeconds) {
                proc_terminate($process);

                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                return [
                    'exit_code' => 124,
                    'stdout' => $this->truncate($stdout),
                    'stderr' => $this->truncate($stderr . "\nScan timeout after {$timeoutSeconds} seconds."),
                ];
            }

            usleep(200000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => $this->truncate($stdout),
            'stderr' => $this->truncate($stderr),
        ];
    }

    private function truncate(string $value): string {
        $value = trim($value);

        if (strlen($value) <= 4000) {
            return $value;
        }

        return substr($value, 0, 4000) . "\n... truncated ...";
    }
}
