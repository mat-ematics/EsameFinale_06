<?php

namespace App\Http\Controllers\api\v1\User;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\State\StateUpdateRequest;
use App\Models\User\State;
use App\Services\Global\StateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class StateController extends Controller
{
    protected StateService $stateService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(StateService $stateService)
    {
        $this->stateService = $stateService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $state = $this->stateService->getStateCollectionInfo(State::all(), Gate::check('isAdmin'));
        if (empty($state))
        {
            $state = 'There are no States Present at the moment';
        }
        return AppHelpers::jsonResponse('States Successfully Retrieved', 200, $state);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $state)
    {
        if (ctype_digit((string) $state)) {
            $stateRecord = State::find((int) $state);
        } else {
            $stateRecord = State::getState($state);
        }
        if (!$stateRecord) {
            return AppHelpers::jsonResponse('State Not Found', 404);
        }

        $stateResource = $this->stateService->getStateInfo($stateRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('State Successfully Retrieved', 200, $stateResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StateUpdateRequest $request, int|string $state)
    {
        if (Gate::denies('isAdmin')) {
            return AppHelpers::jsonResponseForbidden();
        }
        try {
            if (ctype_digit((string) $state)) {
                $stateRecord = State::find((int) $state);
            } else {
                $stateRecord = State::getState($state);
            }
            if (!$stateRecord) {
                throw new HttpException(404, 'State Not Found');
            }

            $updatedState = $this->stateService->updateState($stateRecord, $request->validated());
            return AppHelpers::jsonResponse('State Successfully Updated', 200, $updatedState);
        } catch (\Throwable $th) {
            return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
