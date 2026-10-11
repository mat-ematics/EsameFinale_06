<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Episode\EpisodeStoreRequest;
use App\Http\Requests\Episode\EpisodeUpdateRequest;
use App\Models\Media\Episode;
use App\Models\Media\TvSeries;
use App\Services\Media\EpisodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $isAdmin = Gate::check('isAdmin');

        $tvSeries = $isAdmin ? TvSeries::withTrashed()->find($tvSeriesId) : TvSeries::find($tvSeriesId);

        if (!$tvSeries) {
            return AppHelpers::jsonResponse('TV Series Not Found', 404);
        }

        $season = $request->query('season');

        $episodeRecords = TvSeries::getEpisodesWithFilters((int) $season, $isAdmin);
        $episodes = $this->episodeService->getEpisodeCollectionInfo($episodeRecords, $isAdmin);

        if (empty($episodes))
        {
            $episodes = 'There are no Episodes Present in this TV Series at the moment';
        }

        return AppHelpers::jsonResponse('Episodes Successfully Retrieved', 200, $episodes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EpisodeStoreRequest $request, int $tvSeriesId)
    {
        if (Gate::allows('isAdmin')) {

            $tvSeries = TvSeries::withTrashed()->find($tvSeriesId);

            if (!$tvSeries) {
                return AppHelpers::jsonResponse('TV Series Not Found', 404);
            }

            try {
                $data = $request->validated();

                if ($this->episodeService->isEpisodeTaken($tvSeries->id, $data['season_number'], $data['episode_number'])) {
                    throw new HttpException(422, 'An episode with this number already exists in the given season.');
                }

                $uploadedFiles = collect($request->file('files'));
                $episode = $this->episodeService->createEpisode($tvSeries->id, $data, $uploadedFiles);

                return AppHelpers::jsonResponse('Episode Successfully Created', 201, $episode);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(int $tvSeriesId, int $episodeId)
    {
        $isAdmin = Gate::check('isAdmin');

        $tvSeries = $isAdmin ? TvSeries::withTrashed()->find($tvSeriesId) : TvSeries::find($tvSeriesId);

        if (!$tvSeries) {
            return AppHelpers::jsonResponse('TV Series Not Found', 404);
        }

        $episode = $isAdmin ? Episode::withTrashed()->find($episodeId) : Episode::find($episodeId);

        if (!$episode || $episode->tv_series_id !== $tvSeries->id) {
            return AppHelpers::jsonResponse('Episode Not Found', 404);
        }

        $episodeResource = $this->episodeService->getEpisodeInfo($episode, $isAdmin);
        return AppHelpers::jsonResponse('Episode Successfully Retrieved', 200, $episodeResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EpisodeUpdateRequest $request, int $tvSeriesId, int $episodeId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $tvSeries = TvSeries::withTrashed()->find($tvSeriesId);
                
                if (!$tvSeries) {
                    throw new HttpException(404, 'TV Series Not Found');
                }
                
                $episode = Episode::withTrashed()->find($episodeId);
                
                if (!$episode || $episode->tv_series_id !== $tvSeries->id) {
                    throw new HttpException(404, 'Episode Not Found');
                }

                $data = $request->validated();

                if (isset($data['episode_number']) && isset($data['season_number'])) {

                    $episodeNumber = $data['episode_number'] ?? $episode->episode_number;
                    $seasonNumber = $data['season_number'] ?? $episode->season_number;
    
                    if (
                        $this->episodeService->isEpisodeTaken(
                            $tvSeries->id, 
                            $seasonNumber, 
                            $episodeNumber, 
                            $episode->id
                            )
                        ) 
                        {
                        throw new HttpException(422, 'An episode with this number already exists in the given season.');
                    } 
                }

                MediaFileRulesHelper::checkRoleValidity($episode, $data['files_meta']);

                $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
                $files = $episode->getManyFilesByLabels($labels);

                $episodeResource = $this->episodeService->updateEpisode($tvSeries->id, $episode, $data, $files);

                return AppHelpers::jsonResponse('Episode Successfully Updated', 200, $episodeResource);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $tvSeriesId, int $episodeId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $tvSeries = TvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeries) {
                    throw new HttpException(404, 'TV Series Not Found');
                }
                
                $episode = Episode::withTrashed()->find($episodeId);

                if (!$episode || $episode->tv_series_id !== $tvSeries->id) {
                    throw new HttpException(404, 'Episode Not Found');
                }

                if ($episode->trashed()) {
                    throw new HttpException(409, 'Episode is Already Deleted');
                }

                $this->episodeService->deleteEpisode($episode);
                return AppHelpers::jsonResponse('Episode Successfully Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function restore(int $tvSeriesId, int $episodeId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $tvSeries = TvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeries) {
                    throw new HttpException(404, 'TV Series Not Found');
                }
                
                $episode = Episode::withTrashed()->find($episodeId);

                if (!$episode || $episode->tv_series_id !== $tvSeries->id) {
                    throw new HttpException(404, 'Episode Not Found');
                }

                if (!$episode->trashed()) {
                    throw new HttpException(400, 'Episode is Not Deleted');
                }

                $episodeResource = $this->episodeService->restoreepisode($episode);
                return AppHelpers::jsonResponse('Episode Successfully Restored', 200, $episodeResource);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function forceDestroy(int $tvSeriesId, int $episodeId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $tvSeries = TvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeries) {
                    throw new HttpException(404, 'TV Series Not Found');
                }
                
                $episode = Episode::withTrashed()->find($episodeId);

                if (!$episode || $episode->tv_series_id !== $tvSeries->id) {
                    throw new HttpException(404, 'Episode Not Found');
                }

                if (!$episode->trashed()) {
                    throw new HttpException(400, 'Episode is Not Deleted');
                }

                $this->episodeService->forceDeleteepisode($episode);
                return AppHelpers::jsonResponse('Episode has been Permanently Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }
}
