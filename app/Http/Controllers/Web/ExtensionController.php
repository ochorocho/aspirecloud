<?php
declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\PackageType;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\Packages\PackageSearchService;
use App\Values\Packages\PackageSearchRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExtensionController extends Controller
{
    public function __construct(
        private readonly PackageSearchService $searchService,
    ) {}

    public function index(Request $request): Response
    {
        $type = $request->input('type', PackageType::TYPO3_EXTENSION->value);
        $query = $request->input('q', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(1, (int) $request->input('per_page', 24)));

        $searchRequest = new PackageSearchRequest(
            type: $type,
            q: $query ?: null,
            requires: $request->input('requires'),
            page: $page,
            per_page: $perPage,
        );

        $paginator = $this->searchService->search($searchRequest);

        $packages = collect($paginator->items())->map(fn (Package $package) => [
            'id' => $package->id,
            'slug' => $package->slug,
            'name' => $package->name,
            'description' => $package->description,
            'type' => $package->type,
            'origin' => $package->origin,
            'license' => $package->license,
            'created_at' => $package->created_at?->toIso8601String(),
            'authors' => $package->authors->map(fn ($a) => [
                'name' => $a->display_name ?: $a->user_nicename,
                'url' => $a->author_url,
            ])->all(),
            'tags' => $package->tags->pluck('name')->all(),
            'latest_version' => $package->releases->sortByDesc('version')->first()?->version,
            'releases_count' => $package->releases->count(),
        ])->all();

        $typesWithCount = Package::query()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $types = collect(PackageType::cases())
            ->filter(fn (PackageType $t) => ($typesWithCount[$t->value] ?? 0) > 0)
            ->map(fn (PackageType $t) => [
                'value' => $t->value,
                'label' => match ($t) {
                    PackageType::TYPO3_EXTENSION => 'TYPO3 Extensions',
                    PackageType::TYPO3_CORE => 'TYPO3 Core',
                    PackageType::PLUGIN => 'WordPress Plugins',
                    PackageType::THEME => 'WordPress Themes',
                    PackageType::CORE => 'WordPress Core',
                },
                'count' => $typesWithCount[$t->value],
            ])
            ->values()
            ->all();

        return Inertia::render('Extensions/Index', [
            'packages' => $packages,
            'filters' => [
                'q' => $query,
                'type' => $type,
                'page' => $page,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'types' => $types,
        ]);
    }

    public function show(string $type, string $slug): Response
    {
        $package = Package::with(['authors', 'releases', 'tags', 'metas'])
            ->where('type', $type)
            ->where('slug', $slug)
            ->firstOrFail();

        $releases = $package->releases->sortByDesc('version')->map(fn ($r) => [
            'version' => $r->version,
            'download_url' => $r->download_url,
            'requires' => $r->requires,
            'created_at' => $r->created_at?->toIso8601String(),
        ])->values()->all();

        $sections = $package->metas?->metadata['sections'] ?? [];

        return Inertia::render('Extensions/Show', [
            'package' => [
                'id' => $package->id,
                'did' => $package->did,
                'slug' => $package->slug,
                'name' => $package->name,
                'description' => $package->description,
                'type' => $package->type,
                'origin' => $package->origin,
                'license' => $package->license,
                'created_at' => $package->created_at?->toIso8601String(),
                'authors' => $package->authors->map(fn ($a) => [
                    'name' => $a->display_name ?: $a->user_nicename,
                    'url' => $a->author_url,
                ])->all(),
                'tags' => $package->tags->pluck('name')->all(),
                'releases' => $releases,
                'sections' => $sections,
            ],
        ]);
    }
}
