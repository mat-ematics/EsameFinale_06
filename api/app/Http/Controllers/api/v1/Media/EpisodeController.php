<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Episode\EpisodeStoreRequest;
use App\Models\Media\Episode;
use App\Models\Media\TvSeries;
use App\Services\Media\EpisodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EpisodeController extends Controller
{
    public function __construct(protected EpisodeService $episodeService) {}

    public function index(Request $request, int $tvSeriesId)
    {
        $isAdmin  = Gate::check('isAdmin');
        $series   = TvSeries::findSeries($tvSeriesId, $isAdmin);
        $episodes = $this->episodeService->getEpisodeCollectionInfo(
            $series->getEpisodesWithFilters((int) $request->query('season'), $isAdmin), $isAdmin
        );

        return AppHelpers::jsonResponse(
            $episodes ? 'Episodes Successfully Retrieved' : 'There are no Episodes in this TV Series at the moment', 200, $episodes
        );
    }

    public function show(int $tvSeriesId, int $episodeId)
    {
        $isAdmin = Gate::check('isAdmin');
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, $isAdmin);

        return AppHelpers::jsonResponse('Episode Successfully Retrieved', 200, $this->episodeService->getEpisodeInfo($episode, $isAdmin));
    }

    public function store(EpisodeStoreRequest $request, int $tvSeriesId)
    {
        $series = TvSeries::findSeries($tvSeriesId, true);
        $data   = $request->validated();

        abort_if(
            $this->episodeService->isEpisodeTaken($series->id, $data['season_number'], $data['episode_number']),
            422, 'An episode with this number already exists in the given season.'
        );

        $episode = $this->episodeService->createEpisode($series->id, $data, $this->uploads($request));
        return AppHelpers::jsonResponse('Episode Successfully Created', 201, $episode);
    }

    public function update(EpisodeStoreRequest $request, int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        $data    = $request->validated();

        // The unique check applies if EITHER number changes: fill the missing one from the record
        if (isset($data['season_number']) || isset($data['episode_number'])) {
            abort_if(
                $this->episodeService->isEpisodeTaken(
                    $tvSeriesId,
                    $data['season_number']  ?? $episode->season_number,
                    $data['episode_number'] ?? $episode->episode_number,
                    $episode->id
                ),
                422, 'An episode with this number already exists in the given season.'
            );
        }

        $updated = $this->episodeService->updateEpisode($episode, $data, $this->uploads($request));
        return AppHelpers::jsonResponse('Episode Successfully Updated', 200, $updated);
    }

    public function destroy(int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        abort_if($episode->trashed(), 409, 'Episode is Already Deleted');

        $this->episodeService->deleteEpisode($episode);
        return AppHelpers::jsonResponse('Episode Successfully Deleted', 200);
    }

    public function restore(int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        abort_unless($episode->trashed(), 409, 'Episode is Not Deleted');

        return AppHelpers::jsonResponse('Episode Successfully Restored', 200, $this->episodeService->restoreEpisode($episode));
    }

    public function forceDestroy(int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        abort_unless($episode->trashed(), 409, 'Episode is Not (Soft) Deleted');

        $this->episodeService->forceDeleteEpisode($episode);
        return AppHelpers::jsonResponse('Episode has been Permanently Deleted', 200);
    }
}