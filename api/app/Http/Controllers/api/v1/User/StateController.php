<?php

namespace App\Http\Controllers\api\v1\User;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\State\StateUpdateRequest;
use App\Models\User\State;
use App\Services\Global\StateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        return AppHelpers::jsonResponse(
            empty($state) ? 'States Successfully Retrieved' : 'There are no States Present at the moment',
            200,
            $state ?: [],
        );
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
        $record = AppHelpers::findByKey(State::class, $state, 'getState');

        $stateResource = $this->stateService->getStateInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('State Successfully Retrieved', 200, $stateResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StateUpdateRequest $request, int|string $state)
    {
        $record = AppHelpers::findByKey(State::class, $state, 'getState');

        $updatedState = $this->stateService->updateState($record, $request->validated());
        return AppHelpers::jsonResponse('State Successfully Updated', 200, $updatedState);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
