<?php 

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Media\Film;
use App\Models\Media\TvSeries;
use App\Services\Media\MediaPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaFeedController extends Controller
{
    public function __construct(protected MediaPresenter $presenter) {}

    /** The latest titles that have a banner, optionally of one type or genre */
    public function featured(Request $request)
    {
        $data = $request->validate([
            'type'     => 'nullable|in:film,tv-series',
            'category' => 'nullable|string|max:100',
            'limit'    => 'nullable|integer|min:1|max:10',
        ]);

        $isAdmin = Gate::check('isAdmin');
        $limit   = $data['limit'] ?? 5;
        $type    = $data['type'] ?? null;

        $fetch = fn (string $model) => ($isAdmin ? $model::withTrashed() : $model::query())
            ->with(['categories', 'files'])
            ->when($data['category'] ?? null, fn ($q, $name) => $q->whereHas('categories', fn ($c) => $c->where('name', $name)))
            ->latest()->limit(20)->get();

        $pool = collect();
        if ($type !== 'tv-series') $pool = $pool->concat($fetch(Film::class));
        if ($type !== 'film')      $pool = $pool->concat($fetch(TvSeries::class));

        $has = fn ($media, string $role) => $media->files->contains(fn ($file) => $file->pivot->role === $role);

        // Banner first, then cover, then the rest; newest first inside each group
        $ranked = $pool->sortBy([
            fn ($a, $b) => $has($b, 'banner') <=> $has($a, 'banner'),
            fn ($a, $b) => $has($b, 'cover') <=> $has($a, 'cover'),
            fn ($a, $b) => $b->created_at <=> $a->created_at,
        ])->take($limit);

        return AppHelpers::jsonResponse('Featured titles', 200, $this->presenter->collection($ranked, $isAdmin));
    }
}