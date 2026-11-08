<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\Authorization\Permission\PermissionCollection;
use App\Http\Resources\Authorization\Permission\PermissionResource;
use App\Models\Authorization\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class PermissionService {
 
    /**
     * Ritorna le informazioni associate alla traduzione passata
     */
    public function getPermissionInfo(Permission $permission, bool $isAdmin = false) : PermissionResource
    {
        return new PermissionResource($permission, $isAdmin);
    }
 
    /**
     * Ritorna le informazioni della collection di traduzioni passate
     */
    public function getPermissionCollectionInfo(Collection|array $permissions, bool $isAdmin = false) : array
    {
        return (new PermissionCollection($permissions, $isAdmin))->toArray();
    }
 
 
    /**
     * Aggiorna il valore della traduzione passata.
     * Lingua e chiave identificano la traduzione, quindi non sono modificabili
     */
    public function updatePermission(Permission $permission, array $data) : PermissionResource
    {
        $permission->updateOrFail(Arr::only($data, ['label', 'description']));
 
        return $this->getPermissionInfo($permission, true);
    }
}