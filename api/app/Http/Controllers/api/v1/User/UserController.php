<?php

namespace App\Http\Controllers\api\v1\User;

use App\Enums\StateEnum;
use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\State\LockRequest;
use App\Http\Requests\User\State\SuspendRequest;
use App\Http\Requests\User\User\UserStoreRequest;
use App\Http\Requests\User\User\UserUpdateRequest;
use App\Models\User\User;
use App\Services\User\UserService;

class UserController extends Controller
{
    protected UserService $userService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /* --------- ENDPOINT CLASSICI --------- */

     /* --------- CRUD (admin only, enforced by route middleware) --------- */

    public function index()
    {
        $users = $this->userService->getUserCollectionInfo(User::all());

        return AppHelpers::jsonResponse(
            empty($users) ? 'There are no Users at the moment' : 'Users Successfully Retrieved',
            200,
            $users ?: []
        );
    }

    public function show(int $userId)
    {
        $profile = $this->userService->getUserInfo(User::getUser($userId));
        return AppHelpers::jsonResponse('User Successfully Retrieved', 200, $profile);
    }

    public function store(UserStoreRequest $request)
    {
        $profile = $this->userService->createUser($request->validated());
        return AppHelpers::jsonResponse('User Successfully Created', 201, $profile);
    }

    public function update(UserUpdateRequest $request, int $userId)
    {
        $profile = $this->userService->updateUser(User::getUser($userId), $request->validated());
        return AppHelpers::jsonResponse('User Successfully Updated', 200, $profile);
    }

    public function destroy(int $userId)
    {
        $this->userService->softDeleteUser(User::getUser($userId));
        return AppHelpers::jsonResponse('User Successfully Soft Deleted', 200);
    }

    public function restore(int $userId)
    {
        $user = User::getUser($userId, true);
        abort_unless($user->trashed(), 409, 'User is Not Deleted');

        $profile = $this->userService->restoreUser($user);
        return AppHelpers::jsonResponse('User Successfully Restored', 200, $profile);
    }

    public function forceDestroy(int $userId)
    {
        $user = User::getUser($userId, true);
        abort_unless($user->trashed(), 409, 'User is Not (Soft) Deleted');

        $this->userService->forceDeleteUser($user);
        return AppHelpers::jsonResponse('User has been Permanently Deleted', 200);
    }

    /* --------- STATES --------- */

    public function ban(int $userId)
    {
        $user = User::getUser($userId);
        abort_if($user->state->name === StateEnum::Banned->value, 409, 'User already Banned');

        $this->userService->banUser($user);
        return AppHelpers::jsonResponse("User {$user->username} has been Banned!", 200);
    }

    public function suspend(SuspendRequest $request, int $userId)
    {
        $user = User::getUser($userId);

        $this->userService->suspendUser($user, $request->validated()['state_until']);
        return AppHelpers::jsonResponse("User {$user->username} has been Suspended until {$user->state_until}!", 200);
    }

    public function lock(LockRequest $request, int $userId)
    {
        $user       = User::getUser($userId);
        $stateUntil = $request->validated()['state_until'] ?? null;

        $this->userService->lockUser($user, $stateUntil);

        $message = "User {$user->username} has been Locked" . ($stateUntil ? " until {$user->state_until}" : '') . '!';
        return AppHelpers::jsonResponse($message, 200);
    }

    public function activate(int $userId)
    {
        $user = User::getUser($userId);
        abort_if($user->state->name === StateEnum::Active->value, 409, 'User already Active');

        $this->userService->activateUser($user);
        return AppHelpers::jsonResponse("User {$user->username} has been Activated!", 200);
    }
}
