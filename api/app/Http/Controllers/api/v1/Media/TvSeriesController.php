<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\TvSeries\TvSeriesStoreRequest;
use App\Http\Requests\Media\TvSeries\TvSeriesUpdateRequest;
use App\Models\Media\TvSeries;
use App\Services\Media\TvSeriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TvSeriesController extends Controller
{

    protected TvSeriesService $tvSeriesService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(TvSeriesService $tvSeriesService)
    {   
        $this->tvSeriesService = $tvSeriesService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isAdmin = Gate::check('isAdmin');

        $records = TvSeries::getTvSeriesWithFilters(
            $request->query('name'),
            $request->query('category'),
            (int) $request->query('year'),
            $isAdmin
        );
        $tvSeries = $this->tvSeriesService->getTvSeriesCollectionInfo($records, $isAdmin);

        return AppHelpers::jsonResponse(
            empty($tvSeries) ? 'There are no TV Series at the moment' : 'TV Series Successfully Retrieved',
            200,
            $tvSeries ?: []
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TvSeriesStoreRequest $request)
    {
        $files    = collect($request->file('files'))->all();
        $tvSeries = $this->tvSeriesService->createTvSeries($request->validated(), $files);

        return AppHelpers::jsonResponse('TV Series Successfully Created', 201, $tvSeries);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $tvSeriesId)
    {
        $isAdmin = Gate::check('isAdmin');
        $record  = TvSeries::findSeries($tvSeriesId, $isAdmin);

        $resource = $this->tvSeriesService->getTvSeriesInfo($record, $isAdmin);
        return AppHelpers::jsonResponse('TV Series Successfully Retrieved', 200, $resource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TvSeriesUpdateRequest $request, int $tvSeriesId)
    {
        $record = TvSeries::findSeries($tvSeriesId, Gate::check('isAdmin'));
        $data = $request->validated();

        MediaFileRulesHelper::checkRoleValidity($record, $data['files_meta']);

        $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
        $files  = $record->getManyFilesByLabels($labels);

        $updated = $this->tvSeriesService->updateTvSeries($record, $data, $files);
        return AppHelpers::jsonResponse('TV Series Successfully Updated', 200, $updated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $tvSeriesId)
    {
        $record = TvSeries::findSeries($tvSeriesId);
        abort_if($record->trashed(), 409, 'The Selected TV Series is already Deleted');

        $this->tvSeriesService->deleteTvSeries($record);
        return AppHelpers::jsonResponse('TV Series Successfully Deleted', 200);
    }

    public function restore(int $tvSeriesId)
    {
        $record = TvSeries::findSeries($tvSeriesId, true);
        abort_unless($record->trashed(), 409, 'TV Series is Not Deleted');

        $resource = $this->tvSeriesService->restoreTvSeries($record);
        return AppHelpers::jsonResponse('TV Series Successfully Restored', 200, $resource);
    }

    public function forceDestroy(int $tvSeriesId)
    {
        $record = TvSeries::findSeries($tvSeriesId);
        abort_unless($record->trashed(), 409, 'TV Series is Not (Soft) Deleted');

        $this->tvSeriesService->forceDeleteTvSeries($record);
        return AppHelpers::jsonResponse('TV Series has been Permanently Deleted', 200);
    }
}
