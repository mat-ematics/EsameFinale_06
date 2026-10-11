<?php

namespace App\Http\Controllers\api\v1;

use App\Enums\StateEnum;
use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\LockRequest;
use App\Http\Requests\User\SuspendRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Models\User\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Gate::allows('isAdmin')) {
            $users = $this->userService->getUserCollectionInfo(User::all());

            if (empty($users))
            {
                $users = 'There are no Users Present at the moment';
            }
            return AppHelpers::jsonResponse('Users Successfully Retrieved', 200, $users);
        } 

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();

            try {
                $userProfile =  $this->userService->createUser($data);
                return AppHelpers::jsonResponse('User Successfully Created', 201, $userProfile);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $userId)
    {
        if (Gate::allows('isAdmin')) {
            $user = User::getUser($userId);

            if (!$user) {
                return AppHelpers::jsonResponse('User Not Found', 404);
            }

            $userProfile = $this->userService->getUserInfo($user);
            return AppHelpers::jsonResponse('User Successfully Retrieved', 200, $userProfile);
        }
        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, string $userId)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();

            try {
                 $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                $userProfile = $this->userService->updateUser($user, $data);
                return AppHelpers::jsonResponse('User Successfully Updated', 200, $userProfile);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                $this->userService->softDeleteUser($user);
                return AppHelpers::jsonResponse('User Successfully Soft Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function restore(int $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 $user = User::getUser($userId, true);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                if (!$user->trashed()) {
                    throw new HttpException(400, 'User is Not Deleted');
                }

                $userProfile = $this->userService->restoreUser($user);
                return AppHelpers::jsonResponse('User Successfully Restored', 201, $userProfile);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function forceDestroy(string $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $user = User::getUser($userId, true);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                if (!$user->trashed()) {
                    throw new HttpException(400, 'User is Not Deleted');
                }

                $this->userService->forceDeleteUser($user);
                return AppHelpers::jsonResponse('User has been Permanently Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /* --------- ENDPOINT STATI --------- */

    public function ban(string $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                if ($user->state->name === StateEnum::Banned->value) {
                    throw new HttpException(400, 'User already Banned!');
                }

                return AppHelpers::jsonResponse("User {$user->username} has been Banned!", 200);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }

        }
        return AppHelpers::jsonResponseForbidden();
    }

    public function suspend(SuspendRequest $request, string $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                $stateUntil = $request->validated()['state_until'];

                $this->userService->suspendUser($user, $stateUntil);
                return AppHelpers::jsonResponse("User $user->username has been Suspended until $user->state_until!", 200);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }
        return AppHelpers::jsonResponseForbidden();
    }

    public function lock(LockRequest $request, string $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                $stateUntil = $request->validated()['state_until'] ?? null;

                $this->userService->lockUser($user, $stateUntil);

                $message = "User $user->username has been Locked";
                if ($stateUntil) {
                    $message .= " until $user->state_until";
                }
                return AppHelpers::jsonResponse($message . "!", 200);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }
        return AppHelpers::jsonResponseForbidden();
    }

    public function activate(string $userId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $user = User::getUser($userId);

                if (!$user) {
                    throw new HttpException(404, 'User Not Found');
                }

                if ($user->state->name === StateEnum::Active->value) {
                    throw new HttpException(400, 'User already Active!');
                }

                $this->userService->activateUser($user);
                return AppHelpers::jsonResponse("User {$user->username} has been Activated!", 200);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }
        return AppHelpers::jsonResponseForbidden();
    }
}
