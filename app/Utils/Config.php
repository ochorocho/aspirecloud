<?php

declare(strict_types=1);

namespace App\Utils;

use function Safe\json_decode;

class Config
{
    /** @return string[] */
    public static function stringList(string $input, string $delimiter = ','): array
    {
        return collect(explode($delimiter, $input))
            ->map(fn (string $item) => trim($item))
            ->filter(fn (string $item) => $item !== '')
            ->values()
            ->toArray();
    }

    /**
     * Parse FAIR_REPOS into a list of repo config arrays.
     *
     * Supports two formats:
     * - Plain comma-separated URLs: "https://repo1.example.com, https://repo2.example.com"
     * - JSON array of objects: [{"url": "https://repo.example.com", "auth": {"username": "u", "password": "p"}, "packages_path": "/custom/path"}]
     *
     * @return array<int, array{url: string, auth?: array{username: string, password: string}, packages_path?: string}>
     */
    public static function repoList(string $input): array
    {
        $trimmed = trim($input);

        if (str_starts_with($trimmed, '[')) {
            /** @var array<int, mixed> $decoded */
            $decoded = json_decode($trimmed, true);

            return array_values(array_filter(
                array_map(fn (mixed $item) => is_array($item) && isset($item['url']) ? $item : null, $decoded),
            ));
        }

        return array_map(
            fn (string $url) => ['url' => $url],
            self::stringList($input),
        );
    }

    private function __construct()
    {
        // not instantiable
    }
}
