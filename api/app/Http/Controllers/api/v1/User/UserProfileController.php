<?php

namespace App\Http\Controllers\api\v1\User;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Credit\CreditStoreRequest;
use App\Http\Requests\User\UserProfile\AddressUpdateRequest;
use App\Http\Requests\User\UserProfile\UserProfileUpdateRequest;
use App\Models\Location\Country;
use App\Models\User\Credit;
use App\Models\User\User;
use App\Services\Authentication\AuthService;
use App\Services\User\AddressService;
use App\Services\User\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    protected UserProfileService $profileService;
    protected AuthService $authService;
    protected AddressService $addressService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(
        UserProfileService $profileService,
        AuthService $authService,
        AddressService $addressService,
    )
    {
        $this->profileService = $profileService;
        $this->authService = $authService;
        $this->addressService = $addressService;
    }

    /**
     * Accesso alle informazioni dell'utente autenticato
     */
    public function showCurrentUser()
    {
        $user     = Auth::user();
        $resource = $this->profileService->getProfile($user);
        $base     = $resource instanceof JsonResource ? $resource->resolve() : (array) $resource;

        $countryId = $user->address?->country_id;

        return AppHelpers::jsonResponse('Profile Successfully Retrieved', 200, array_merge($base, [
            'tax_code'      => $user->userProfile?->tax_code,
            'residence_iso' => $countryId ? Country::whereKey($countryId)->value('iso') : null,
        ]));
    }
    public function updateCurrentUser(UserProfileUpdateRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $userProfile = $this->profileService->updateProfile($user, $data);
        // A new password invalidates every session, including this one
        if (!empty($data['password'])) {
            $this->authService->endUserSession($user->id);
        }
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

    public function showAddress() {
        return AppHelpers::jsonResponse(
            'Address Successfully Retrieved',
            200,
            $this->addressService->getUserAddressData(Auth::user()),
        );
    }

    public function updateAddress(AddressUpdateRequest $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $this->addressService->replaceAddress($user, $request->validated());

        return AppHelpers::jsonResponse(
            'Address Successfully Updated', 200,
            $this->addressService->getUserAddressData($user->fresh())
        );
    }
}