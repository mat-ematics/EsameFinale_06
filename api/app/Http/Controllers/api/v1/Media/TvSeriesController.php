<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\TvSeries\TvSeriesStoreRequest;
use App\Models\Media\TvSeries;
use App\Services\Media\TvSeriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TvSeriesController extends Controller
{
    public function __construct(protected TvSeriesService $TvSeriesService) {}

    public function index(Request $request)
    {
        $isAdmin = Gate::check('isAdmin');

        $records = TvSeries::getTvSeriesWithFilters(
            $request->query('name'), $request->query('category'), (int) $request->query('year'),
            $isAdmin, $this->limit($request),
        );
        $films = $this->TvSeriesService->getTvSeriesCollectionInfo($records, $isAdmin);

        return AppHelpers::jsonResponse(
            $films ? 'Films Successfully Retrieved' : 'There are no Films present at the moment', 200, $films
        );
    }

    public function show(int $tvSeriesId)
    {
        $isAdmin = Gate::check('isAdmin');
        $TvSeries = TvSeries::findSeries($tvSeriesId, $isAdmin);

        return AppHelpers::jsonResponse('TvSeries Successfully Retrieved', 200, $this->TvSeriesService->getTvSeriesInfo($TvSeries, $isAdmin));
    }

    public function store(TvSeriesStoreRequest $request)
    {
        $TvSeries = $this->TvSeriesService->createTvSeries($request->validated(), $this->uploads($request));
        return AppHelpers::jsonResponse('TvSeries Successfully Created', 201, $TvSeries);
    }

    public function update(TvSeriesStoreRequest $request, int $tvSeriesId)
    {
        $TvSeries = TvSeries::findSeries($tvSeriesId, true);

        $updated = $this->TvSeriesService->updateTvSeries(
            $TvSeries, $request->validated(), $this->uploads($request), $request->boolean('remove_banner')
        );
        return AppHelpers::jsonResponse('TvSeries Successfully Updated', 200, $updated);
    }

    public function destroy(int $tvSeriesId)
    {
        $TvSeries = TvSeries::findSeries($tvSeriesId, true);
        abort_if($TvSeries->trashed(), 409, 'The Selected TvSeries is already Deleted');

        $this->TvSeriesService->deleteTvSeries($TvSeries);
        return AppHelpers::jsonResponse('TvSeries Successfully Deleted', 200);
    }

    public function restore(int $tvSeriesId)
    {
        $TvSeries = TvSeries::findSeries($tvSeriesId, true);
        abort_unless($TvSeries->trashed(), 409, 'TvSeries is Not Deleted');

        return AppHelpers::jsonResponse('TvSeries Successfully Restored', 200, $this->TvSeriesService->restoreTvSeries($TvSeries));
    }

    public function forceDestroy(int $tvSeriesId)
    {
        $TvSeries = TvSeries::findSeries($tvSeriesId, true);
        abort_unless($TvSeries->trashed(), 409, 'TvSeries is Not (Soft) Deleted');

        $this->TvSeriesService->forceDeleteTvSeries($TvSeries);
        return AppHelpers::jsonResponse('TvSeries has been Permanently Deleted', 200);
    }
}