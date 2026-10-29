<?php

namespace App\Http\Controllers\api\v1\Authorization;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authorization\PermissionUpdateRequest;
use App\Models\Authorization\Permission;
use App\Services\Global\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = $this->permissionService->getPermissionCollectionInfo(Permission::all(), Gate::check('isAdmin'));

        return AppHelpers::jsonResponse(
            empty($permissions) ? 'Permissions Successfully Retrieved' : 'There are no Permissions Present at the moment',
            200,
            $permissions ?: [],
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
    public function show(int|string $permission)
    {
        $record = AppHelpers::findByKey(Permission::class, $permission, 'getPermission');

        $permissionResource = $this->permissionService->getPermissionInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Permission Successfully Retrieved', 200, $permissionResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionUpdateRequest $request, int|string $permission)
    {
        $record = AppHelpers::findByKey(Permission::class, $permission, 'getPermission');

        $updatedPermission = $this->permissionService->updatePermission($record, $request->validated());
        return AppHelpers::jsonResponse('Permission Successfully Updated', 200, $updatedPermission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
