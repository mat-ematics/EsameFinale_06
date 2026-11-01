<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelpers;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Lightweight lookup for typeaheads: minimum 3 characters, at most 25 rows,
     * prefix matches first. $column is always passed by our own code, never by the client.
     */
    protected function lookupSearch(string $model, Request $request, string $column = 'name')
    {
        $term = trim((string) $request->query('search', ''));
        abort_if(mb_strlen($term) < 3, 422, 'The search term must be at least 3 characters');

        $limit = min(max((int) $request->query('limit', 10), 1), 25);
        $like  = addcslashes($term, '%_\\');   // the user's % and _ are literals, not wildcards

        $rows = $model::query()
            ->where($column, 'like', "%{$like}%")
            ->orderByRaw("CASE WHEN {$column} LIKE ? THEN 0 ELSE 1 END", ["{$like}%"])
            ->orderBy($column)
            ->limit($limit)
            ->get(['id', "{$column} as name"]);

        return AppHelpers::jsonResponse('Results Successfully Retrieved', 200, $rows->toArray());
    }
}
