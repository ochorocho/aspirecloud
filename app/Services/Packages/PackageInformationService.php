<?php

declare(strict_types=1);

namespace App\Services\Packages;

use App\Models\Package;

class PackageInformationService
{
    public function findByDID(string $did): ?Package
    {
        return Package::query()->where('did', $did)->first();
    }

    public function find(string $type, string $slug): ?Package
    {
        return Package::query()
            ->where('type', $type)
            ->where('slug', $slug)
            ->first();
    }

    public function getPackageMetadataUrl(string $did): string
    {
        return route('package.fairMetadata', ['did' => $did], true);
    }

    public function findBySlug(string $slug): ?Package
    {
        return Package::query()->where('slug', $slug)->first();
    }
}
