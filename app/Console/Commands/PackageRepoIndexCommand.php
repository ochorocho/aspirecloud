<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Package;
use App\Values\Packages\FairMetadata;
use App\Values\Packages\PackageData;
use Closure;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use function Safe\ini_set;

class PackageRepoIndexCommand extends Command
{
    protected $signature = 'package:repo-index {--stop-on-first-error}';

    protected $description = 'Import packages from FAIR repositories';

    /** @var array{url: string, auth?: array{username: string, password: string}, packages_path?: string} */
    private array $currentRepo = ['url' => ''];

    private bool $useSlug = false;

    private int $errors = 0;

    private int $loaded = 0;

    public function handle(Pipeline $pipeline): void
    {
        ini_set('memory_limit', '-1');

        $repos = config('fair.repos', []);

        if (empty($repos)) {
            $this->fail('No FAIR repositories configured. Update the FAIR_REPOS environment variable.');
        }

        $stages = [
            $this->readPackageMetadata(...),
            $this->createPackage(...),
        ];

        assert(is_iterable($repos));
        foreach ($repos as $repo) {
            assert(is_array($repo));
            $this->currentRepo = $repo;
            $this->useSlug = false;
            try {
                $packages = $this->getRepoPackages();
                foreach ($packages as $did) {
                    try {
                        DB::transaction(
                            fn () => $pipeline
                                ->send($did)
                                ->through($stages)
                                ->thenReturn(),
                        );
                    } catch (Exception $e) {
                        $this->errors++;
                        $this->error("Package $did: {$e->getMessage()}");
                        $this->option('stop-on-first-error') and $this->fail('Errors encountered -- aborting.');
                    }
                }
            } catch (Exception $e) {
                $this->errors++;
                $this->error("Repo {$this->repoUrl()}: {$e->getMessage()}");
                $this->option('stop-on-first-error') and $this->fail('Errors encountered -- aborting.');
            }
        }

        if ($this->errors > 0) {
            $this->fail("Indexed $this->loaded packages; $this->errors errors");
        }

        $this->info("Indexed $this->loaded packages.");
    }

    private function repoUrl(): string
    {
        return rtrim($this->currentRepo['url'], '/');
    }

    private function packagesPath(): string
    {
        $path = $this->currentRepo['packages_path']
            ?? config('fair.paths.packages', '/packages');
        assert(is_string($path));

        return trim($path, '/');
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::withHeaders(['Accept' => 'application/json']);

        if (isset($this->currentRepo['auth']['username'], $this->currentRepo['auth']['password'])) {
            $client = $client->withBasicAuth(
                $this->currentRepo['auth']['username'],
                $this->currentRepo['auth']['password'],
            );
        }

        return $client;
    }

    /** @return array<string, string> */
    private function getRepoPackages(): array
    {
        $url = $this->repoUrl().'/'.$this->packagesPath();
        $this->info("Fetching packages from $url");

        $response = $this->httpClient()->get($url);

        if ($response->failed()) {
            throw new Exception("Failed to fetch packages from $url (HTTP {$response->status()})");
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new Exception("Invalid JSON from $url");
        }

        return $data;
    }

    /**
     * Extract the slug from a DID string.
     * e.g. "did:web:extensions.typo3.org:tw_shop" -> "tw_shop"
     */
    private function extractSlug(string $did): string
    {
        $parts = explode(':', $did);

        return end($parts);
    }

    private function readPackageMetadata(string $did, Closure $next): void
    {
        $baseUrl = $this->repoUrl().'/'.$this->packagesPath();

        if ($this->useSlug) {
            $identifier = $this->extractSlug($did);
        } else {
            $identifier = $did;
        }

        $this->info("Fetching package $did from {$this->repoUrl()}");

        $response = $this->httpClient()->get($baseUrl.'/'.$identifier);

        // Auto-detect: if DID-based fetch returns 404, retry with slug
        if ($response->status() === 404 && ! $this->useSlug && $identifier !== $this->extractSlug($did)) {
            $slug = $this->extractSlug($did);
            $this->warn("DID lookup failed, retrying with slug '$slug' (will use slugs for remaining packages)");
            $response = $this->httpClient()->get($baseUrl.'/'.$slug);
            if ($response->successful()) {
                $this->useSlug = true;
            }
        }

        if ($response->failed()) {
            throw new Exception("Failed to fetch package metadata for $did (HTTP {$response->status()})");
        }
        $metadata = $response->json();
        if (! is_array($metadata)) {
            throw new Exception("Invalid JSON for package $did");
        }
        $next($metadata);
    }

    /** @param array<string, mixed> $metadata */
    private function createPackage(array $metadata, Closure $next): void
    {
        $fairMetadata = FairMetadata::from($metadata);
        $packageData = PackageData::from($fairMetadata);
        Package::fromPackageData($packageData);
        $this->loaded++;
        $next($packageData);
    }
}
