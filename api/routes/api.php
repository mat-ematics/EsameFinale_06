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

    if (app()->environment(['local', 'testing'])) {
        Route::get('/login/test/{username}/{password}', [AuthController::class, 'testLogin']);
    }

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/login/{userHash}', [AuthController::class, 'getLoginSalt'])->middleware('throttle:30,1');
    Route::get('/login/{userHash}/{passwordHash}', [AuthController::class, 'newLogin'])
        ->middleware(['throttle:10,1', ProtectNewLogin::class]);

    /* -------- BEARER TOKEN OPZIONALE ---------- */

    Route::middleware(TokenAuth::class . ':optional')->group(function () {

        Route::get('/test/optional-bearer', fn () => 'optional bearer works!');

        Route::prefix('/configs')->group(function () {
            Route::get('/', [ConfigController::class, 'index']);
            Route::get('/{config}', [ConfigController::class, 'show']);
            Route::put('/{config}', [ConfigController::class, 'update'])->middleware('admin');
        });

        Route::prefix('/langs')->group(function () {
            Route::get('/', [LanguageController::class, 'index']);
            Route::get('/{lang}', [LanguageController::class, 'show']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [LanguageController::class, 'store']);
                Route::put('/{lang}', [LanguageController::class, 'update']);
                Route::delete('/{lang}', [LanguageController::class, 'destroy']);
            });
        });

        Route::prefix('/translations')->group(function () {
            Route::get('/', [TranslationController::class, 'index']);
            Route::get('/effective/{lang}', [TranslationController::class, 'indexByLanguage']);
            Route::get('/effective/{lang}/{key}', [TranslationController::class, 'show']);
            Route::get('/default', [TranslationController::class, 'indexDefault']);
            Route::get('/default/{lang}/{key}', [TranslationController::class, 'showDefault']);
            Route::get('/custom', [TranslationController::class, 'indexCustom']);
            Route::get('/custom/{lang}/{key}', [TranslationController::class, 'showCustom']);

            Route::middleware('admin')->group(function () {
                Route::post('/default/{lang}', [TranslationController::class, 'createDefault']);
                Route::put('/default/{lang}/{key}', [TranslationController::class, 'updateDefault']);
                Route::put('/custom/{lang}/{key}', [TranslationController::class, 'upsertCustom']);
                Route::delete('/default/{lang}/{key}', [TranslationController::class, 'deleteDefault']);
                Route::delete('/custom/{lang}/{key}', [TranslationController::class, 'deleteCustom']);
            });
        });

        // Read-only lookups
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

        Route::prefix('/states')->group(function () {
            Route::get('/', [StateController::class, 'index']);
            Route::get('/{state}', [StateController::class, 'show']);
            Route::put('/{state}', [StateController::class, 'update'])->middleware('admin');
        });

        Route::prefix('/permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index']);
            Route::get('/{permission}', [PermissionController::class, 'show']);
            Route::put('/{permission}', [PermissionController::class, 'update'])->middleware('admin');
        });

        Route::prefix('/roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/{role}', [RoleController::class, 'show']);
            Route::put('/{role}', [RoleController::class, 'update'])->middleware('admin');
        });
    });

    /* -------- PROTETTE DA BEARER TOKEN ---------- */

    Route::middleware(TokenAuth::class)->group(function () {

        Route::get('/test/bearer', fn () => 'it works!');

        Route::post('/logout', [AuthController::class, 'logout']);

        /* ---- Profilo e credito (utente corrente) ---- */
        Route::prefix('/me')->group(function () {
            Route::get('/', [UserProfileController::class, 'showCurrentUser']);
            Route::put('/', [UserProfileController::class, 'updateCurrentUser']);
            Route::delete('/', [UserProfileController::class, 'destroyCurrentUser']);
            Route::get('/credit', [UserProfileController::class, 'showCredit']);
            Route::put('/credit/add', [UserProfileController::class, 'addCredit']);
            Route::put('/credit/remove', [UserProfileController::class, 'removeCredit']);
        });

        /* ---- Utenti: solo admin ---- */
        Route::prefix('/users')->middleware('admin')->whereNumber('userId')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{userId}', [UserController::class, 'show']);
            Route::put('/{userId}', [UserController::class, 'update']);
            Route::delete('/{userId}', [UserController::class, 'destroy']);
            Route::delete('/{userId}/force', [UserController::class, 'forceDestroy']);
            Route::put('/{userId}/restore', [UserController::class, 'restore']);
            Route::put('/{userId}/ban', [UserController::class, 'ban']);
            Route::put('/{userId}/suspend', [UserController::class, 'suspend']);
            Route::put('/{userId}/lock', [UserController::class, 'lock']);
            Route::put('/{userId}/activate', [UserController::class, 'activate']);
            Route::post('/{userId}/force-logout', [AuthController::class, 'forceLogout']);
        });

        /* ---- Categorie ---- */
        Route::prefix('/categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{category}', [CategoryController::class, 'show']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [CategoryController::class, 'store']);
                Route::put('/{category}', [CategoryController::class, 'update']);
                Route::delete('/{category}', [CategoryController::class, 'destroy']);
            });
        });

        /* ---- Film ---- */
        Route::prefix('/films')->whereNumber('filmId')->group(function () {
            Route::get('/', [FilmController::class, 'index']);
            Route::get('/{filmId}', [FilmController::class, 'show']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [FilmController::class, 'store']);
                Route::put('/{filmId}', [FilmController::class, 'update']);
                Route::delete('/{filmId}', [FilmController::class, 'destroy']);
                Route::delete('/{filmId}/force', [FilmController::class, 'forceDestroy']);
                Route::put('/{filmId}/restore', [FilmController::class, 'restore']);
            });
        });

        /* ---- Serie TV ---- */
        Route::prefix('/tv-series')->whereNumber('tvSeriesId')->group(function () {
            Route::get('/', [TvSeriesController::class, 'index']);
            Route::get('/{tvSeriesId}', [TvSeriesController::class, 'show']);
            Route::middleware('admin')->group(function () {
                Route::post('/', [TvSeriesController::class, 'store']);
                Route::put('/{tvSeriesId}', [TvSeriesController::class, 'update']);
                Route::delete('/{tvSeriesId}', [TvSeriesController::class, 'destroy']);
                Route::delete('/{tvSeriesId}/force', [TvSeriesController::class, 'forceDestroy']);
                Route::put('/{tvSeriesId}/restore', [TvSeriesController::class, 'restore']);
            });
        });

        /* ---- Episodi ---- */
        Route::prefix('/tv-series/{tvSeriesId}/episodes')
            ->whereNumber(['tvSeriesId', 'episodeId'])
            ->group(function () {
                Route::get('/', [EpisodeController::class, 'index']);
                Route::get('/{episodeId}', [EpisodeController::class, 'show']);
                Route::middleware('admin')->group(function () {
                    Route::post('/', [EpisodeController::class, 'store']);
                    Route::put('/{episodeId}', [EpisodeController::class, 'update']);
                    Route::delete('/{episodeId}', [EpisodeController::class, 'destroy']);
                    Route::delete('/{episodeId}/force', [EpisodeController::class, 'forceDestroy']);
                    Route::put('/{episodeId}/restore', [EpisodeController::class, 'restore']);
                });
            });
    });
});