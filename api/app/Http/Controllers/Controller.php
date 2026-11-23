<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelpers;
use App\Models\File\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

abstract class Controller
{
    /**
     * Lightweight lookup for typeaheads: minimum 3 characters, at most 25 rows,
     * prefix matches first. $column is always passed by our own code, never by the client.
     */
    protected function lookupSearch(
        string $model,
        Request $request,
        string $column = 'name',
        string $id = 'id',
        array $extra = [],
        int $minCharLength = 3,
        int $maxRecordNum = 25,
        int $defaultRecordNum = 10,
    )
    {
        $term = trim((string) $request->query('search', ''));
        abort_if(mb_strlen($term) < $minCharLength, 422, "The search term must be at least " . $minCharLength . " characters");

        $limit = min(max((int) $request->query('limit', $defaultRecordNum), 1), $maxRecordNum);
        $like  = addcslashes($term, '%_\\');   // the user's % and _ are literals, not wildcards

        $rows = $model::query()
            ->where($column, 'like', "%{$like}%")
            ->orderByRaw("CASE WHEN {$column} LIKE ? THEN 0 ELSE 1 END", ["{$like}%"])
            ->orderBy($column)
            ->limit($limit)
            ->get(["{$id}", "{$column} as name", ...$extra]);

        return AppHelpers::jsonResponse('Results Successfully Retrieved', 200, $rows->toArray());
    }

    /** ?limit=N (1-100); null when absent */
    protected function limit(Request $request): ?int
    {
        $limit = (int) $request->query('limit', 0);
        return $limit > 0 ? min($limit, 100) : null;
    }

    /** Files and finished uploads sent with a media request: cover and banner (files), video (a chunked upload) */
    protected function uploads(Request $request): array
    {
        $sources = Arr::only($request->allFiles(), ['cover', 'banner']);

        if ($id = $request->input('video_upload_id')) {
            $sources['video'] = Upload::where('user_id', $request->user()->id)->where('status', 'complete')->findOrFail($id);
        }

        return $sources;
    }
}
