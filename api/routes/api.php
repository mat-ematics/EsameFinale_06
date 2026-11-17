<?php

use App\Http\Controllers\api\v1\Authentication\AuthController;
use App\Http\Controllers\api\v1\Authorization\PermissionController;
use App\Http\Controllers\api\v1\Authorization\RoleController;
use App\Http\Controllers\api\v1\Global\ConfigController;
use App\Http\Controllers\api\v1\Global\LanguageController;
use App\Http\Controllers\api\v1\Global\TranslationController;
use App\Http\Controllers\api\v1\Location\ContinentController;
use App\Http\Controllers\api\v1\Location\CountryController;
use App\Http\Controllers\api\v1\Location\ItalianMunicipalityController;
use App\Http\Controllers\api\v1\Location\ItalianProvinceController;
use App\Http\Controllers\api\v1\Location\ItalianRegionController;
use App\Http\Controllers\api\v1\Media\CategoryController;
use App\Http\Controllers\api\v1\Media\EpisodeController;
use App\Http\Controllers\api\v1\Media\FilmController;
use App\Http\Controllers\api\v1\Media\MediaSearchController;
use App\Http\Controllers\api\v1\Media\TvSeriesController;
use App\Http\Controllers\api\v1\User\StateController;
use App\Http\Controllers\api\v1\User\UserController;
use App\Http\Controllers\api\v1\User\UserProfileController;
use App\Http\Middleware\ProtectNewLogin;
use App\Http\Middleware\TokenAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/test', fn () => 'it works!');

    /* -------- AUTENTICAZIONE (pubblica) ---------- */

    //Testing locale dell'autenticazione
    if (app()->environment(['local', 'testing'])) {
        Route::get('/login/test/{username}/{password}', [AuthController::class, 'testLogin']);
    }

    //Registrazione (max 10 richieste al minuto)
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    //Login fase 1 (max 30 richieste al minuto)
    Route::get('/login/{userHash}', [AuthController::class, 'getLoginSalt'])->middleware('throttle:30,1');
    //Login fase 2 (max 10 richieste al minuto)
    Route::get('/login/{userHash}/{passwordHash}', [AuthController::class, 'newLogin'])
        ->middleware(['throttle:10,1', ProtectNewLogin::class]);

    /* -------- BEARER TOKEN OPZIONALE ---------- */

    Route::middleware(TokenAuth::class . ':optional')->group(function () {

        //Testing locale del Bearer
        if (app()->environment(['local', 'testing'])) {
            Route::get('/test/optional-bearer', fn () => 'optional bearer works!');
        }

        /* -------- CONFIGURAZIONI ---------- */

        Route::prefix('/configs')->group(function () {
            //READ
            Route::get('/', [ConfigController::class, 'index']);
            Route::get('/{config}', [ConfigController::class, 'show']);
            //UPDATE (richiede ruolo di amministratore)
            Route::put('/{config}', [ConfigController::class, 'update'])->middleware('admin');
        });

        /* -------- LINGUE ---------- */

        Route::prefix('/langs')->group(function () {
            //READ
            Route::get('/', [LanguageController::class, 'index']);
            Route::get('/{lang}', [LanguageController::class, 'show']);
            //Richiede ruolo di amministratore
            Route::middleware('admin')->group(function () {
                //CREATE
                Route::post('/', [LanguageController::class, 'store']);
                //UPDATE
                Route::put('/{lang}', [LanguageController::class, 'update']);
                //DELETE
                Route::delete('/{lang}', [LanguageController::class, 'destroy']);
            });
        });

        /* -------- TRADUZIONI ---------- */

        Route::prefix('/translations')->group(function () {
            /* EFFETTIVE */
            //READ
            Route::get('/', [TranslationController::class, 'index']);
            Route::get('/effective/{lang}', [TranslationController::class, 'indexByLanguage']);
            Route::get('/effective/{lang}/{key}', [TranslationController::class, 'show']);
            /* DEFAULT */
            //READ
            Route::get('/default', [TranslationController::class, 'indexDefault']);
            Route::get('/default/{lang}/{key}', [TranslationController::class, 'showDefault']);
            /* CUSTOM */
            //READ
            Route::get('/custom', [TranslationController::class, 'indexCustom']);
            Route::get('/custom/{lang}/{key}', [TranslationController::class, 'showCustom']);

            //Richiede ruolo di amministratore
            Route::middleware('admin')->group(function () {
                /* DEFAULT */
                //CREATE
                Route::post('/default/{lang}', [TranslationController::class, 'createDefault']);
                //UPDATE
                Route::put('/default/{lang}/{key}', [TranslationController::class, 'updateDefault']);
                //DELETE
                Route::delete('/default/{lang}/{key}', [TranslationController::class, 'deleteDefault']);
                /* CUSTOM */
                //CREATE o UPDATE
                Route::put('/custom/{lang}/{key}', [TranslationController::class, 'upsertCustom']);
                //DELETE
                Route::delete('/custom/{lang}/{key}', [TranslationController::class, 'deleteCustom']);
            });
        });

        /* -------- CONTINENTI, PAESI, REGIONI, PROVINCE, COMUNI ---------- */

        // READ-ONLY
        Route::get('/continents', [ContinentController::class, 'index']);
        Route::get('/continents/{continent}', [ContinentController::class, 'show']);
        Route::get('/countries', [CountryController::class, 'index']);
        Route::get('/countries/{country}', [CountryController::class, 'show']);
        Route::get('/italian-regions', [ItalianRegionController::class, 'index']);
        Route::get('/italian-regions/{region}', [ItalianRegionController::class, 'show']);
        Route::get('/italian-provinces', [ItalianProvinceController::class, 'index']);
        Route::get('/italian-provinces/{province}', [ItalianProvinceController::class, 'show']);
        Route::get('/italian-municipalities', [ItalianMunicipalityController::class, 'index']);
        Route::get('/italian-municipalities/{municipality}', [ItalianMunicipalityController::class, 'show']);

        /* -------- STATI UTENTE ---------- */

        Route::prefix('/states')->group(function () {
            //READ
            Route::get('/', [StateController::class, 'index']);
            Route::get('/{state}', [StateController::class, 'show']);
            //UPDATE (richiede ruolo di amministratore)
            Route::put('/{state}', [StateController::class, 'update'])->middleware('admin');
        });

        /* -------- PERMESSI UTENTE ---------- */

        Route::prefix('/permissions')->group(function () {
            //READ
            Route::get('/', [PermissionController::class, 'index']);
            Route::get('/{permission}', [PermissionController::class, 'show']);
            //UPDATE (richiede ruolo di amministratore)
            Route::put('/{permission}', [PermissionController::class, 'update'])->middleware('admin');
        });

        /* -------- RUOLI UTENTE ---------- */

        Route::prefix('/roles')->group(function () {
            //READ
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/{role}', [RoleController::class, 'show']);
            //UPDATE (richiede ruolo di amministratore)
            Route::put('/{role}', [RoleController::class, 'update'])->middleware('admin');
        });
    });

    /* -------- PROTETTE DA BEARER TOKEN ---------- */

    Route::middleware(TokenAuth::class)->group(function () {

        //Testing locale del Bearer
        if (app()->environment(['local', 'testing'])) {
            Route::get('/test/bearer', fn () => 'it works!');
        }

        /* -------- AUTENTICAZIONE ---------- */

        Route::post('/logout', [AuthController::class, 'logout']);

        /* ---- PROFILO E CREDITO (UTENTE CORRENTE) ---- */

        Route::prefix('/me')->group(function () {
            //READ Profilo
            Route::get('/', [UserProfileController::class, 'showCurrentUser']);
            //UPDATE Profilo
            Route::put('/', [UserProfileController::class, 'updateCurrentUser'])->middleware('throttle:10,1');
            //DELETE Utente --> ELIMINAZIONE ACCOUNT
            Route::delete('/', [UserProfileController::class, 'destroyCurrentUser']);
            //READ Credito
            Route::get('/credit', [UserProfileController::class, 'showCredit']);
            //UPDATE Credito
            Route::put('/credit/add', [UserProfileController::class, 'addCredit']);
            Route::put('/credit/remove', [UserProfileController::class, 'removeCredit']);
            //READ Indirizzo
            Route::get('/address', [UserProfileController::class, 'showAddress']);
            //UPDATE Indirizzo
            Route::put('/address', [UserProfileController::class, 'updateAddress'])->middleware('throttle:20,1');
        });

        /* ---- UTENTE (SOLO AMMINISTRATORI) ---- */

        Route::prefix('/users')->middleware('admin')->whereNumber('userId')->group(function () {
            //READ
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{userId}', [UserController::class, 'show']);
            //CREATE
            Route::post('/', [UserController::class, 'store']);
            //UPDATE
            Route::put('/{userId}', [UserController::class, 'update']);
            //UPDATE STATI 
            Route::put('/{userId}/ban', [UserController::class, 'ban']);
            Route::put('/{userId}/suspend', [UserController::class, 'suspend']);
            Route::put('/{userId}/lock', [UserController::class, 'lock']);
            Route::put('/{userId}/activate', [UserController::class, 'activate']);
            //DELETE
            Route::delete('/{userId}', [UserController::class, 'destroy']);
            Route::delete('/{userId}/force', [UserController::class, 'forceDestroy']);
            //RESTORE
            Route::put('/{userId}/restore', [UserController::class, 'restore']);
            //LOGOUT FORZATO
            Route::post('/{userId}/force-logout', [AuthController::class, 'forceLogout']);
        });

        /* ---- RICERCA ---- */

        Route::get('/search', [MediaSearchController::class, 'index'])->middleware('throttle:60,1');

        /* ---- CATEGORIE ---- */

        Route::prefix('/categories')->group(function () {
            //READ
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{category}', [CategoryController::class, 'show']);
            //SOLO ADMIN
            Route::middleware('admin')->group(function () {
                //CREATE
                Route::post('/', [CategoryController::class, 'store']);
                //UPDATE
                Route::put('/{category}', [CategoryController::class, 'update']);
                //DELETE
                Route::delete('/{category}', [CategoryController::class, 'destroy']);
            });
        });

        /* ---- FILM ---- */

        Route::prefix('/films')->whereNumber('filmId')->group(function () {
            //READ
            Route::get('/', [FilmController::class, 'index']);
            Route::get('/{filmId}', [FilmController::class, 'show']);
            //SOLO ADMIN
            Route::middleware('admin')->group(function () {
                //CREATE
                Route::post('/', [FilmController::class, 'store']);
                //UPDATE
                Route::put('/{filmId}', [FilmController::class, 'update']);
                //DELETE
                Route::delete('/{filmId}', [FilmController::class, 'destroy']);
                Route::delete('/{filmId}/force', [FilmController::class, 'forceDestroy']);
                //RESTORE
                Route::put('/{filmId}/restore', [FilmController::class, 'restore']);
            });
        });

        /* ---- SERIE TV ---- */

        Route::prefix('/tv-series')->whereNumber('tvSeriesId')->group(function () {
            //READ
            Route::get('/', [TvSeriesController::class, 'index']);
            Route::get('/{tvSeriesId}', [TvSeriesController::class, 'show']);
            //SOLO ADMIN
            Route::middleware('admin')->group(function () {
                //CREATE
                Route::post('/', [TvSeriesController::class, 'store']);
                //UPDATE
                Route::put('/{tvSeriesId}', [TvSeriesController::class, 'update']);
                //DELETE
                Route::delete('/{tvSeriesId}', [TvSeriesController::class, 'destroy']);
                Route::delete('/{tvSeriesId}/force', [TvSeriesController::class, 'forceDestroy']);
                //RESTORE
                Route::put('/{tvSeriesId}/restore', [TvSeriesController::class, 'restore']);
            });
        });

        /* ---- EPISODI ---- */

        Route::prefix('/tv-series/{tvSeriesId}/episodes')
            ->whereNumber(['tvSeriesId', 'episodeId'])
            ->group(function () {
                //READ
                Route::get('/', [EpisodeController::class, 'index']);
                Route::get('/{episodeId}', [EpisodeController::class, 'show']);
                //SOLO ADMIN
                Route::middleware('admin')->group(function () {
                    //CREATE
                    Route::post('/', [EpisodeController::class, 'store']);
                    //UPDATE
                    Route::put('/{episodeId}', [EpisodeController::class, 'update']);
                    //DELETE
                    Route::delete('/{episodeId}', [EpisodeController::class, 'destroy']);
                    Route::delete('/{episodeId}/force', [EpisodeController::class, 'forceDestroy']);
                    //RESTORE
                    Route::put('/{episodeId}/restore', [EpisodeController::class, 'restore']);
                });
            });
    });
});