<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Episode\EpisodeStoreRequest;
use App\Http\Requests\Media\Episode\EpisodeUpdateRequest;
use App\Models\Media\Episode;
use App\Models\Media\TvSeries;
use App\Services\Media\EpisodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EpisodeController extends Controller
{

    protected EpisodeService $episodeService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(EpisodeService $episodeService)
    {
        $this->episodeService = $episodeService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, int $tvSeriesId)
    {
        $isAdmin  = Gate::check('isAdmin');
        $tvSeries = TvSeries::findSeries($tvSeriesId, $isAdmin);

        $records  = $tvSeries->getEpisodesWithFilters((int) $request->query('season'), $isAdmin);
        $episodes = $this->episodeService->getEpisodeCollectionInfo($records, $isAdmin);

        return AppHelpers::jsonResponse(
            empty($episodes) ? 'There are no Episodes in this TV Series at the moment' : 'Episodes Successfully Retrieved',
            200,
            $episodes ?: []
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EpisodeStoreRequest $request, int $tvSeriesId)
    {
        $tvSeries = TvSeries::findSeries($tvSeriesId, true);
        $data     = $request->validated();

        abort_if(
            $this->episodeService->isEpisodeTaken($tvSeries->id, $data['season_number'], $data['episode_number']),
            422,
            'An episode with this number already exists in the given season.'
        );

        $files   = collect($request->file('files'))->all();
        $episode = $this->episodeService->createEpisode($tvSeries->id, $data, $files);

        return AppHelpers::jsonResponse('Episode Successfully Created', 201, $episode);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $tvSeriesId, int $episodeId)
    {
        $isAdmin = Gate::check('isAdmin');
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, $isAdmin);

        $resource = $this->episodeService->getEpisodeInfo($episode, $isAdmin);
        return AppHelpers::jsonResponse('Episode Successfully Retrieved', 200, $resource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EpisodeUpdateRequest $request, int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        $data    = $request->validated();

        // Controlla unicità se ALMENO una delle due cambia, riempendo l'altra eventualmente mancante dal record
        if (isset($data['season_number']) || isset($data['episode_number'])) {
            abort_if(
                $this->episodeService->isEpisodeTaken(
                    $tvSeriesId,
                    $data['season_number']  ?? $episode->season_number,
                    $data['episode_number'] ?? $episode->episode_number,
                    $episode->id
                ),
                422,
                'An episode with this number already exists in the given season.'
            );
        }

        MediaFileRulesHelper::checkRoleValidity($episode, $data['files_meta']);

        $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
        $files  = $episode->getManyFilesByLabels($labels);

        $updated = $this->episodeService->updateEpisode($tvSeriesId, $episode, $data, $files);
        return AppHelpers::jsonResponse('Episode Successfully Updated', 200, $updated);
    }

    /**
     * Remove the specified resource from storage.
     */
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

        $resource = $this->episodeService->restoreEpisode($episode);
        return AppHelpers::jsonResponse('Episode Successfully Restored', 200, $resource);
    }

    public function forceDestroy(int $tvSeriesId, int $episodeId)
    {
        $episode = Episode::findEpisode($tvSeriesId, $episodeId, true);
        abort_unless($episode->trashed(), 409, 'Episode is Not Deleted');

        $this->episodeService->forceDeleteEpisode($episode);
        return AppHelpers::jsonResponse('Episode has been Permanently Deleted', 200);
    }
}
