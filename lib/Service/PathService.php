<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Service;

use InvalidArgumentException;

class PathService {
    public function normalizeDir(string $dir): string {
        $dir = trim($dir);

        if ($dir === '') {
            return '/';
        }

        $dir = str_replace("\0", '', $dir);
        $dir = rawurldecode($dir);
        $dir = str_replace('\\', '/', $dir);

        if (!str_starts_with($dir, '/')) {
            $dir = '/' . $dir;
        }

        $parts = [];

        foreach (explode('/', $dir) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                continue;
            }

            $parts[] = $part;
        }

        if ($parts === []) {
            return '/';
        }

        return '/' . implode('/', $parts);
    }

    public function normalizePrefix(string $prefix): string {
        $prefix = $this->normalizeDir($prefix);

        if ($prefix === '/') {
            throw new InvalidArgumentException('The root folder cannot be used as an allowed prefix.');
        }

        return $prefix;
    }

    public function normalizePrefixes(array $prefixes): array {
        $result = [];

        foreach ($prefixes as $prefix) {
            $prefix = trim((string)$prefix);

            if ($prefix === '') {
                continue;
            }

            $result[] = $this->normalizePrefix($prefix);
        }

        return array_values(array_unique($result));
    }

    public function isInsideAllowedPrefixes(string $dir, array $allowedPrefixes): bool {
        $dir = $this->normalizeDir($dir);
        $allowedPrefixes = $this->normalizePrefixes($allowedPrefixes);

        foreach ($allowedPrefixes as $prefix) {
            if ($dir === $prefix || str_starts_with($dir, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    public function buildFilesScanPath(string $userId, string $dir): string {
        $dir = $this->normalizeDir($dir);

        if ($dir === '/') {
            throw new InvalidArgumentException('Scanning the Files root is not allowed.');
        }

        if (str_contains($userId, '/')) {
            throw new InvalidArgumentException('Invalid user id.');
        }

        return '/' . $userId . '/files' . $dir;
    }
}
