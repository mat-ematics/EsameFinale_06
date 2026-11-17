<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\MediaSearchRequest;
use App\Services\Media\MediaSearchService;
use Illuminate\Support\Facades\Gate;

class MediaSearchController extends Controller
{
    public function __construct(protected MediaSearchService $searchService) {}

    public function index(MediaSearchRequest $request)
    {
        $data = $request->validated();

        $results = $this->searchService->search(
            $data['q'],
            $data['type'] ?? null,
            (int) ($data['limit'] ?? 24),
            Gate::check('isAdmin'),
        );

        return AppHelpers::jsonResponse($results ? 'Search results' : 'No results found', 200, $results);
    }
}