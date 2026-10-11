<?php

namespace App\Services\User;

use App\Enums\StateEnum;
use App\Helpers\AppHelpers;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\Authorization\Role;
use App\Models\User\User;
use App\Models\User\UserProfile;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService {

    protected AddressService $addressService;
    protected PasswordService $passwordService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(AddressService $addressService, PasswordService $passwordService)
    {
        $this->addressService = $addressService;
        $this->passwordService = $passwordService;
    }


    /**
     * Ritorna le informazioni associate all'utente passato.
     */
    public function getUserInfo(User $user, ?int $currUserId = null) : UserResource
    {
        return new UserResource($user, $currUserId);
    }

    /**
     * Ritorna le informazioni della collection di Utenti passati
     */
    public function getUserCollectionInfo(Collection|array $users) : UserCollection
    {
        return new UserCollection($users);
    }

    /**
     * Crea un nuovo utente e ne ritorna il Model.
     * Attenzione: i dati non sono validati all'interno della funzione
     */
    public function createUser(array $data) : UserResource
    {
        return DB::transaction(function () use ($data) {

            //Creazione Record Utente
            $userHash = User::getUsernameHash($data['username']);
            // Log::alert("Username in creation: " . $data['username']);
            // Log::alert("Hash in creation: " . $userHash);
            $newUser = User::create([
                'username' => $userHash,
            ]);

            
            $this->passwordService->createPassword($newUser, $data['password']);

            //Creazione Record Profilo Utente
            UserProfile::create([
                'user_id' => $newUser->id,
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'birthdate' => $data['birthdate'],
                'gender' => $data['gender'],
            ]);

            //Aggiunta Ruoli all'Utente
            if (!isset($data['role']) && empty($data['role'])) {
                $data['role'] = 'user';
            }
            $this->assignRolesToUser($newUser, $data['role']);

            //Creazione Record Credito
            $newUser->credit()->create(['value' => $data['credit']]);

            //Creazione Record Indirizzo
            $this->addressService->createAddress($newUser, $data);

            return $this->getUserInfo($newUser);
        });
    }

    public function assignRolesToUser(User $user, string $role) : bool 
    {
        $roles = [];

        switch ($role) {
            case 'admin':
                $roles[] = Role::getRoleId(User::ADMIN);
            case 'user':
                $roles[] = Role::getRoleId(User::USER);
            case 'guest':
                $roles[] = Role::getRoleId(User::GUEST);
                break;
            default:
                throw new HttpException('Invalid User Role', 400);
                break;
        }

        $user->roles()->sync($roles);
        return true;
    }

    /**
     * Aggiorna le informazioni dell'utente passato
     */
    public function updateUser(User $user, array $data, ?int $currUserId = null) : UserResource
    {
        $data = array_filter($data);
    
        //Aggiorna Username se presente
        if (!empty($data['username'])) {
            $user->update(['username' => User::getUsernameHash($data['username'])]);
        }

        //Update Ruoli
        if (isset($data['role'])) {
            $this->assignRolesToUser($user, $data['role']);
        }

        return $this->getUserInfo($user, $currUserId);
    }

    public function softDeleteUser(User $user)
    {
        $user->delete();
    }

    public function restoreUser(User $user)
    {
        $user->restore();
        return $this->getUserInfo($user);
    }

    public function forceDeleteUser(User $user)
    {
        return $user->forceDelete();
    }

    public function banUser(User $user)
    {
        $user->setState(StateEnum::Banned);
    }

    public function suspendUser(User $user, Carbon|int $until)
    {
        $user->setState(StateEnum::Suspended, $until);
    }

    public function lockUser(User $user, Carbon|int|null $until = null)
    {
        $user->setState(StateEnum::Locked, $until);
    }

    public function activateUser(User $user)
    {
        $user->setState(StateEnum::Active, null);
    }
}