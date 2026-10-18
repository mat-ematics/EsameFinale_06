<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\Role\RoleCollection;
use App\Http\Resources\Role\RoleResource;
use App\Models\Authorization\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleService {
 
    /**
     * Ritorna le informazioni associate alla traduzione passata
     */
    public function getRoleInfo(Role $role, bool $isAdmin = false) : RoleResource
    {
        return new RoleResource($role, $isAdmin);
    }
 
    /**
     * Ritorna le informazioni della collection di traduzioni passate
     */
    public function getRoleCollectionInfo(Collection|array $roles, bool $isAdmin = false) : array
    {
        return (new RoleCollection($roles, $isAdmin))->toArray();
    }
 
 
    /**
     * Aggiorna il valore della traduzione passata.
     * Lingua e chiave identificano la traduzione, quindi non sono modificabili
     */
    public function updateRole(Role $role, array $data) : RoleResource
    {
        $role->updateOrFail(Arr::only($data, ['label', 'description']));
 
        return $this->getRoleInfo($role, true);
    }
}