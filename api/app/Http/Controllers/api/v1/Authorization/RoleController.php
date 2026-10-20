<?php

namespace App\Http\Controllers\api\v1\Authorization;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authorization\RoleUpdateRequest;
use App\Models\Authorization\Role;
use App\Services\Global\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleController extends Controller
{
    protected RoleService $roleService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = $this->roleService->getRoleCollectionInfo(Role::all(), Gate::check('isAdmin'));
        if (empty($roles))
        {
            $roles = 'There are no Roles Present at the moment';
        }
        return AppHelpers::jsonResponse('Roles Successfully Retrieved', 200, $roles);
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
    public function show(int|string $role)
    {
        if (ctype_digit((string) $role)) {
            $roleRecord = Role::find((int) $role);
        } else {
            $roleRecord = Role::getRole($role);
        }
        if (!$roleRecord) {
            return AppHelpers::jsonResponse('Role Not Found', 404);
        }

        $roleResource = $this->roleService->getRoleInfo($roleRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Role Successfully Retrieved', 200, $roleResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request, int|string $role)
    {
        if (Gate::denies('isAdmin')) {
            return AppHelpers::jsonResponseForbidden();
        }
        try {
            if (ctype_digit((string) $role)) {
                $roleRecord = Role::find((int) $role);
            } else {
                $roleRecord = Role::getRole($role);
            }
            if (!$roleRecord) {
                throw new HttpException(404, 'Role Not Found');
            }

            $updatedRole = $this->roleService->updateRole($roleRecord, $request->validated());
            return AppHelpers::jsonResponse('Role Successfully Updated', 200, $updatedRole);
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
