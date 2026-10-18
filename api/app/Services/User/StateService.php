<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\State\StateCollection;
use App\Http\Resources\State\StateResource;
use App\Models\User\State;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StateService {
 
    /**
     * Ritorna le informazioni associate alla traduzione passata
     */
    public function getStateInfo(State $state, bool $isAdmin = false) : StateResource
    {
        return new StateResource($state, $isAdmin);
    }
 
    /**
     * Ritorna le informazioni della collection di traduzioni passate
     */
    public function getStateCollectionInfo(Collection|array $states, bool $isAdmin = false) : array
    {
        return (new StateCollection($states, $isAdmin))->toArray();
    }
 
 
    /**
     * Aggiorna il valore della traduzione passata.
     * Lingua e chiave identificano la traduzione, quindi non sono modificabili
     */
    public function updateState(State $state, array $data) : StateResource
    {
        $state->updateOrFail(Arr::only($data, ['label', 'description']));
 
        return $this->getStateInfo($state, true);
    }
}