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

        return AppHelpers::jsonResponse(
            empty($roles) ? 'Roles Successfully Retrieved' : 'There are no Roles Present at the moment',
            200,
            $roles ?: [],
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
    public function show(int|string $role)
    {
        $record = AppHelpers::findByKey(Role::class, $role, 'getRole');

        $roleResource = $this->roleService->getRoleInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Role Successfully Retrieved', 200, $roleResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request, int|string $role)
    {
        $record = AppHelpers::findByKey(Role::class, $role, 'getRole');

        $updatedRole = $this->roleService->updateRole($record, $request->validated());
        return AppHelpers::jsonResponse('Role Successfully Updated', 200, $updatedRole);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
