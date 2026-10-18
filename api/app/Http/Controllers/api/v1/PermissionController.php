<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\PermissionUpdateRequest;
use App\Models\Authorization\Permission;
use App\Services\Global\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        if (empty($permissions))
        {
            $permissions = 'There are no Permissions Present at the moment';
        }
        return AppHelpers::jsonResponse('Permissions Successfully Retrieved', 200, $permissions);
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
        if (ctype_digit((string) $permission)) {
            $permissionRecord = Permission::find((int) $permission);
        } else {
            $permissionRecord = Permission::getPermission($permission);
        }
        if (!$permissionRecord) {
            return AppHelpers::jsonResponse('Permission Not Found', 404);
        }

        $permissionResource = $this->permissionService->getPermissionInfo($permissionRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Permission Successfully Retrieved', 200, $permissionResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionUpdateRequest $request, int|string $permission)
    {
        if (Gate::denies('isAdmin')) {
            return AppHelpers::jsonResponseForbidden();
        }
        try {
            if (ctype_digit((string) $permission)) {
                $permissionRecord = Permission::find((int) $permission);
            } else {
                $permissionRecord = Permission::getPermission($permission);
            }
            if (!$permissionRecord) {
                throw new HttpException(404, 'Permission Not Found');
            }

            $updatedPermission = $this->permissionService->updatePermission($permissionRecord, $request->validated());
            return AppHelpers::jsonResponse('Permission Successfully Updated', 200, $updatedPermission);
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
