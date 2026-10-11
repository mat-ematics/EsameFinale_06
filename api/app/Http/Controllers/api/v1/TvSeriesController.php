<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TvSeries\TvSeriesStoreRequest;
use App\Http\Requests\TvSeries\TvSeriesUpdateRequest;
use App\Models\Media\TvSeries;
use App\Services\Media\TvSeriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $name = $request->query('name');
        $category = $request->query('category');
        $year = $request->query('year');

        $tvSeriesRecords = TvSeries::getTvSeriesWithFilters($name, $category, (int) $year, $isAdmin);
        $tvSeries = $this->tvSeriesService->getTvSeriesCollectionInfo($tvSeriesRecords, $isAdmin);

        if (empty($tvSeries))
        {
            $tvSeries = 'There are no TV Series Present at the moment';
        }
        return AppHelpers::jsonResponse('Tv Series Successfully Retrieved', 200, $tvSeries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TvSeriesStoreRequest $request)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $uploadedFiles = collect($request->file('files'));
                $tvSeries = $this->tvSeriesService->createTvSeries($request->validated(), $uploadedFiles);

                return AppHelpers::jsonResponse('Tv Series Successfully Created', 201, $tvSeries);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(int $tvSeriesId)
    {
        $isAdmin = Gate::check('isAdmin');

        $tvSeriesRecord = $isAdmin ? TvSeries::withTrashed()->find($tvSeriesId) : TvSeries::find($tvSeriesId);

        if (!$tvSeriesRecord) return AppHelpers::jsonResponse('TV Series Not Found', 404);

        $tvSeriesResource = $this->tvSeriesService->getTvSeriesInfo($tvSeriesRecord, $isAdmin);
        return AppHelpers::jsonResponse('TV Series Successfully Retrieved', 200, $tvSeriesResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TvSeriesUpdateRequest $request, int $tvSeriesId)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();

            try {
                //Ricerca su ID/Nome o Label
                $tvSeriesRecord = tvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeriesRecord) {
                    throw new HttpException(404, 'Tv Series Not Found');
                }

                MediaFileRulesHelper::checkRoleValidity($tvSeriesRecord, $data['files_meta']);

                $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
                $files = $tvSeriesRecord->getManyFilesByLabels($labels);

                $updatedtvSeries = $this->tvSeriesService->updateTvSeries($tvSeriesRecord, $data, $files);
                return AppHelpers::jsonResponse('Tv Series Successfully Updated', 200, $updatedtvSeries);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $tvSeriesId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 //Ricerca su ID/Nome o Label
                $tvSeriesRecord = tvSeries::withTrashed()->find($tvSeriesId);
                
                if (!$tvSeriesRecord) {
                    throw new HttpException(404, 'Tv Series Not Found');
                }

                if ($tvSeriesRecord->trashed()) {
                    throw new HttpException(400, 'The Selected Tv Series is already Deleted');
                }

                $this->tvSeriesService->deleteTvSeries($tvSeriesRecord);
                return AppHelpers::jsonResponse('TvSeries Successfully Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function restore(int $tvSeriesId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                //Ricerca su ID/Nome o Label
                $tvSeriesRecord = tvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeriesRecord) {
                    throw new HttpException(404, 'TV Series Not Found');
                }

                if (!$tvSeriesRecord->trashed()) {
                    throw new HttpException(400, 'TV Series is Not Deleted');
                }

                $tvSeriesResource = $this->tvSeriesService->restoretvSeries($tvSeriesRecord);
                return AppHelpers::jsonResponse('TV Series Successfully Restored', 200, $tvSeriesResource);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function forceDestroy(int $tvSeriesId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                //Ricerca su ID/Nome o Label
                $tvSeriesRecord = TvSeries::withTrashed()->find($tvSeriesId);

                if (!$tvSeriesRecord) {
                    throw new HttpException(404, 'TV Series Not Found');
                }

                if (!$tvSeriesRecord->trashed()) {
                    throw new HttpException(400, 'TV Series is Not (Soft) Deleted');
                }

                $this->tvSeriesService->forceDeletetvSeries($tvSeriesRecord);
                return AppHelpers::jsonResponse('TV Series has been Permanently Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }
}
