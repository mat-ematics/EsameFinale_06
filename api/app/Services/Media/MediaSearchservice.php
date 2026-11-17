<?php

namespace App\Services\Media;

use App\Models\Media\Film;
use App\Models\Media\TvSeries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaSearchService
{
    /**
     * Ranking: each field dominates every field below it (see the weights table in the docs).
     * Tune the numbers here; keep every tier above the sum of the tiers under it.
     */
    private const TITLE = ['exact' => 4000, 'prefix' => 3000, 'phrase' => 2000, 'words' => 1000];

    private const FIELD_LABELS = [
        's_title' => 'title', 's_description' => 'description', 's_genre' => 'genre',
        's_director' => 'director', 's_cast' => 'cast', 's_year' => 'year',
        's_duration' => 'duration', 's_episodes' => 'episodes',
    ];

    public function __construct(
        protected FilmService $filmService,
        protected TvSeriesService $tvSeriesService,
    ) {}

    private function weights(bool $numeric): array
    {
        return [
            'description' => ['phrase' => 600, 'words' => 400],
            'genre'    => 100,
            'director' => 60,
            'cast'     => 40,
            'episodes' => 20,
            // A number is mostly a year or a length: it matters more than prose
            'year'     => $numeric ? 800 : 80,
            'duration' => $numeric ? 700 : 30,
        ];
    }

    /** @return array<int, array> ranked results, best first */
    public function search(string $q, ?string $type, int $limit, bool $isAdmin): array
    {
        $phrase = trim(preg_replace('/\s+/u', ' ', $q));
        $tokens = array_slice(array_values(array_unique(array_filter(
            explode(' ', $phrase), fn ($t) => mb_strlen($t) >= 2
        ))), 0, 5);
        $number = preg_match('/^(\d{1,4})(?:\s*(?:min|mins|minutes))?$/i', $phrase, $m) ? (int) $m[1] : null;
        $w = $this->weights($number !== null);

        $queries = [];
        if ($type !== 'tv-series') $queries[] = $this->filmQuery($phrase, $tokens, $number, $w, $isAdmin);
        if ($type !== 'film')      $queries[] = $this->seriesQuery($phrase, $tokens, $number, $w, $isAdmin);

        $union = array_shift($queries);
        foreach ($queries as $next) $union->unionAll($next);

        $sum = '(r.s_title + r.s_description + r.s_genre + r.s_director + r.s_cast + r.s_year + r.s_duration + r.s_episodes)';

        $rows = DB::query()
            ->fromSub($union, 'r')
            ->select('r.*')
            ->selectRaw("$sum AS score")
            ->whereRaw("$sum > 0")
            ->orderByDesc('score')
            ->orderBy('r.title')
            ->limit($limit)
            ->get();

        return $this->hydrate($rows, $phrase, $tokens, $isAdmin);
    }

    /* --------- The two queries: same columns, so they can be united --------- */

    private function filmQuery(string $phrase, array $tokens, ?int $number, array $w, bool $isAdmin): Builder
    {
        [$title, $titleB]   = $this->titleScore('t.name', $phrase, $tokens);
        [$desc, $descB]     = $this->textScore('t.description', $w['description']['phrase'], $w['description']['words'], $phrase, $tokens);
        [$genre, $genreB]   = $this->genreScore('category_film', 'film_id', $w['genre'], $phrase);
        [$dir, $dirB]       = $this->textScore('t.directors', $w['director'], (int) round($w['director'] * .6), $phrase, $tokens);
        [$cast, $castB]     = $this->textScore('t.actors', $w['cast'], (int) round($w['cast'] * .6), $phrase, $tokens);
        $year     = $number === null ? ['0', []] : ["CASE WHEN t.year = ? THEN {$w['year']} ELSE 0 END", [$number]];
        $duration = $number === null ? ['0', []] : ["CASE WHEN TIME_TO_SEC(t.length) DIV 60 = ? THEN {$w['duration']} ELSE 0 END", [$number]];

        // The ? marks follow the order of the select list, so the bindings must too
        $select = "t.id, 'film' AS type, t.name AS title, t.description, t.year AS year,
                   ($title) AS s_title, ($desc) AS s_description, ($genre) AS s_genre,
                   ($dir) AS s_director, ($cast) AS s_cast,
                   ($year[0]) AS s_year, ($duration[0]) AS s_duration, 0 AS s_episodes";

        return DB::table('films as t')
            ->selectRaw($select, [...$titleB, ...$descB, ...$genreB, ...$dirB, ...$castB, ...$year[1], ...$duration[1]])
            ->when(!$isAdmin, fn ($q) => $q->whereNull('t.deleted_at'));
    }

    private function seriesQuery(string $phrase, array $tokens, ?int $number, array $w, bool $isAdmin): Builder
    {
        [$title, $titleB]   = $this->titleScore('t.name', $phrase, $tokens);
        [$desc, $descB]     = $this->textScore('t.description', $w['description']['phrase'], $w['description']['words'], $phrase, $tokens);
        [$genre, $genreB]   = $this->genreScore('category_tv_series', 'tv_series_id', $w['genre'], $phrase);
        [$dir, $dirB]       = $this->textScore('t.directors', $w['director'], (int) round($w['director'] * .6), $phrase, $tokens);
        [$cast, $castB]     = $this->textScore('t.actors', $w['cast'], (int) round($w['cast'] * .6), $phrase, $tokens);
        [$episodes, $epB]   = $this->episodesScore($w['episodes'], $phrase, $isAdmin);

        $inside = (int) round($w['year'] * .4);
        $year = $number === null ? ['0', []] : [
            "CASE WHEN t.start_year = ? OR t.end_year = ? THEN {$w['year']} WHEN ? BETWEEN t.start_year AND t.end_year THEN $inside ELSE 0 END",
            [$number, $number, $number],
        ];

        $select = "t.id, 'tv-series' AS type, t.name AS title, t.description, t.start_year AS year,
                   ($title) AS s_title, ($desc) AS s_description, ($genre) AS s_genre,
                   ($dir) AS s_director, ($cast) AS s_cast,
                   ($year[0]) AS s_year, 0 AS s_duration, ($episodes) AS s_episodes";

        return DB::table('tv_series as t')
            ->selectRaw($select, [...$titleB, ...$descB, ...$genreB, ...$dirB, ...$castB, ...$year[1], ...$epB])
            ->when(!$isAdmin, fn ($q) => $q->whereNull('t.deleted_at'));
    }

    /* --------- Score pieces. Columns come from our own code, user input only as bindings --------- */

    private function like(string $value, string $prefix = '', string $suffix = ''): string
    {
        return $prefix . addcslashes($value, '%_\\') . $suffix;   // the user's % and _ are literals
    }

    private function titleScore(string $column, string $phrase, array $tokens): array
    {
        $sql = "CASE WHEN $column = ? THEN " . self::TITLE['exact']
             . " WHEN $column LIKE ? THEN " . self::TITLE['prefix']
             . " WHEN $column LIKE ? THEN " . self::TITLE['phrase'];
        $bindings = [$phrase, $this->like($phrase, suffix: '%'), $this->like($phrase, '%', '%')];

        if (count($tokens) > 1) {                                   // every word, in any order
            $sql .= ' WHEN (' . implode(' AND ', array_fill(0, count($tokens), "$column LIKE ?")) . ') THEN ' . self::TITLE['words'];
            foreach ($tokens as $token) $bindings[] = $this->like($token, '%', '%');
        }

        return [$sql . ' ELSE 0 END', $bindings];
    }

    private function textScore(string $column, int $phraseWeight, int $wordsWeight, string $phrase, array $tokens): array
    {
        $sql = "CASE WHEN $column LIKE ? THEN $phraseWeight";
        $bindings = [$this->like($phrase, '%', '%')];

        if (count($tokens) > 1) {
            $sql .= ' WHEN (' . implode(' AND ', array_fill(0, count($tokens), "$column LIKE ?")) . ") THEN $wordsWeight";
            foreach ($tokens as $token) $bindings[] = $this->like($token, '%', '%');
        }

        return [$sql . ' ELSE 0 END', $bindings];
    }

    private function genreScore(string $pivot, string $foreignKey, int $weight, string $phrase): array
    {
        $like = $this->like($phrase, '%', '%');

        return [
            "CASE WHEN EXISTS (SELECT 1 FROM $pivot px JOIN categories cx ON cx.id = px.category_id
                               WHERE px.$foreignKey = t.id AND (cx.name LIKE ? OR cx.label LIKE ?)) THEN $weight ELSE 0 END",
            [$like, $like],
        ];
    }

    private function episodesScore(int $weight, string $phrase, bool $isAdmin): array
    {
        $like = $this->like($phrase, '%', '%');
        $live = $isAdmin ? '' : ' AND ex.deleted_at IS NULL';

        return [
            "CASE WHEN EXISTS (SELECT 1 FROM episodes ex WHERE ex.tv_series_id = t.id$live
                               AND (ex.name LIKE ? OR ex.description LIKE ?)) THEN $weight ELSE 0 END",
            [$like, $like],
        ];
    }

    /* --------- Turn the ranked rows into results, with the standard resource of each title --------- */

    private function hydrate(mixed $rows, string $phrase, array $tokens, bool $isAdmin): array
    {
        $ids = fn (string $type) => $rows->where('type', $type)->pluck('id')->all();

        $films  = ($isAdmin ? Film::withTrashed() : Film::query())->whereIn('id', $ids('film'))->get()->keyBy('id');
        $series = ($isAdmin ? TvSeries::withTrashed() : TvSeries::query())->whereIn('id', $ids('tv-series'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($films, $series, $isAdmin, $phrase, $tokens) {
            $isFilm = $row->type === 'film';
            $model  = $isFilm ? $films->get($row->id) : $series->get($row->id);
            if (!$model) return null;

            $resource = $isFilm
                ? $this->filmService->getFilmInfo($model, $isAdmin)
                : $this->tvSeriesService->getTvSeriesInfo($model, $isAdmin);

            return [
                'type'    => $row->type,
                'id'      => (int) $row->id,
                'title'   => $row->title,
                'year'    => (int) $row->year,
                'score'   => (int) $row->score,
                'matched' => $this->matched($row),
                'snippet' => $this->snippet($row, $phrase, $tokens),
                'media'   => $resource instanceof JsonResource ? $resource->resolve() : (array) $resource,
            ];
        })->filter()->values()->all();
    }

    /** Fields that matched, most important first */
    private function matched(object $row): array
    {
        return collect(self::FIELD_LABELS)
            ->filter(fn ($label, $column) => $row->$column > 0)
            ->values()
            ->all();
    }

    private function snippet(object $row, string $phrase, array $tokens): ?string
    {
        if ($row->s_description <= 0 || !$row->description) return null;

        foreach ([$phrase, ...$tokens] as $needle) {
            $excerpt = Str::excerpt($row->description, $needle, ['radius' => 70]);
            if ($excerpt) return $excerpt;
        }

        return Str::limit($row->description, 140);       // matched ignoring accents: no exact excerpt
    }
}