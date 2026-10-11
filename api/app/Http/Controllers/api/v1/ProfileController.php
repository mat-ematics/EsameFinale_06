<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreditRequest;
use App\Http\Requests\User\ProfileUpdateRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Services\User\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected ProfileService $profileService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ProfileService $profileService)
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

    public function updateCurrentUser(ProfileUpdateRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $userProfile = $this->profileService->updateProfile($user, $data);
        return AppHelpers::jsonResponse('Profile Successfully Updated', 200, $userProfile);
    }

    public function destroyCurrentUser()
    {
        $user = Auth::user();
        $this->profileService->deleteProfile($user);
        return AppHelpers::jsonResponse('User Successfully Soft Deleted', 204);
    }

    public function showCredit()
    {
        $user= Auth::user();
        $credit = number_format($this->profileService->getCreditValue($user), 2);
        return AppHelpers::jsonResponse('Your Total Credit: ' . $credit, 200);
    }

    public function addCredit(CreditRequest $request)
    {
        $user = Auth::user();

        $creditToAdd = $request->validated()['credit'];

        $newCredit = number_format($this->profileService->addCredit($user, $creditToAdd), 2);

        return AppHelpers::jsonResponse('Credit Added! Your Credit is now ' . $newCredit);
    }

    public function removeCredit(CreditRequest $request)
    {
        $user = Auth::user();

        $creditToSub = $request->validated()['credit'];

        $currCredit = $this->profileService->getCreditValue($user);

        if ($currCredit < $creditToSub) {
            return AppHelpers::jsonResponse('You cannot remove more credit than what you have!', 422);
        }

        $newCredit = number_format($this->profileService->removeCredit($user, $creditToSub), 2);

        return AppHelpers::jsonResponse('Credit Removed! Your Credit is now ' . $newCredit);
    }
}
