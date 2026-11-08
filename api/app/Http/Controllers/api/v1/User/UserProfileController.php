<?php

namespace App\Http\Controllers\api\v1\User;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Credit\CreditStoreRequest;
use App\Http\Requests\User\UserProfile\UserProfileUpdateRequest;
use App\Models\User\Credit;
use App\Services\User\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    protected UserProfileService $profileService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(UserProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Accesso alle informazioni dell'utente autenticato
     */
    public function showCurrentUser()
    {
        $user = Auth::user();
        $userProfile = $this->profileService->getProfile($user);
        return AppHelpers::jsonResponse('Profile Successfully Retrieved', 200, $userProfile);
    }

    public function updateCurrentUser(UserProfileUpdateRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $userProfile = $this->profileService->updateProfile($user, $data);
        return AppHelpers::jsonResponse('Profile Successfully Updated', 200, $userProfile);
    }

    public function destroyCurrentUser()
    {
        $this->profileService->deleteProfile(Auth::user());
        return AppHelpers::jsonResponse('User Successfully Soft Deleted', 200);
    }

    public function showCredit()
    {
        $credit = (float) $this->profileService->getCredit(Auth::user());
        return AppHelpers::jsonResponse('Credit Successfully Retrieved', 200, ['credit' => $credit]);
    }

    public function addCredit(CreditStoreRequest $request)
    {
        $user = Auth::user();
        $amount = $request->validated()['credit'];

        abort_if(
            $this->profileService->getCredit($user) + $amount > Credit::MAX_COUNT,
            422,
            "You cannot have more credit the the maximum allowed (" . Credit::MAX_COUNT . ")"
        );

        $credit = (float) $this->profileService->addCredit(Auth::user(), $amount);
        return AppHelpers::jsonResponse('Credit Added', 200, ['credit' => $credit]);
    }

    public function removeCredit(CreditStoreRequest $request)
    {
        $user   = Auth::user();
        $amount = $request->validated()['credit'];

        abort_if(
            $this->profileService->getCredit($user) < $amount,
            422,
            'You cannot remove more credit than what you have!'
        );

        $credit = (float) $this->profileService->removeCredit($user, $amount);
        return AppHelpers::jsonResponse('Credit Removed', 200, ['credit' => $credit]);
}
}