<?php

use App\Helpers\AppHelpers;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\CategoryController;
use App\Http\Controllers\api\v1\ConfigController;
use App\Http\Controllers\api\v1\ContinentController;
use App\Http\Controllers\api\v1\CountryController;
use App\Http\Controllers\api\v1\EpisodeController;
use App\Http\Controllers\api\v1\FilmController;
use App\Http\Controllers\api\v1\ItalianMunicipalityController;
use App\Http\Controllers\api\v1\ItalianProvinceController;
use App\Http\Controllers\api\v1\ItalianRegionController;
use App\Http\Controllers\api\v1\LanguageController;
use App\Http\Controllers\api\v1\PermissionController;
use App\Http\Controllers\api\v1\ProfileController;
use App\Http\Controllers\api\v1\RoleController;
use App\Http\Controllers\api\v1\StateController;
use App\Http\Controllers\api\v1\TranslationController;
use App\Http\Controllers\api\v1\TvSeriesController;
use App\Http\Controllers\api\v1\UserController;
use App\Http\Middleware\ProtectLogin;
use App\Http\Middleware\ProtectNewLogin;
use App\Http\Middleware\RequireAuth;
use App\Http\Middleware\TokenAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

define('_VERS', 'v1');

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix(_VERS)->group(function () {

    /* -------- ROUTE TEST E VARIE ---------- */

    Route::get('/test', function () {
        return 'it works!';
    });

    if (app()->environment(['local', 'testing'])) {
        Route::get('/login/test/{username}/{password}', [AuthController::class, 'testLogin']);
    }

    /* -------- ROUTE AUTENTICAZIONE ---------- */

    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login/{userHash}', [AuthController::class, 'getLoginSalt']);
    Route::get('/login/{userHash}/{passwordHash}', [AuthController::class, 'newLogin'])->middleware(ProtectNewLogin::class);


    /* -------- BEARER TOKEN OPZIONALE ---------- */

    Route::middleware([TokenAuth::class.':optional'])->group(function () {

        /* -------- ROUTE TEST E VARIE ---------- */

        Route::get('/test/optional-bearer', function() {
            return 'optional bearer works!';
        });

        /* -------- ROUTE CONFIGURAZIONE ---------- */
        //Route READ
        Route::get('/configs', [ConfigController::class, 'index']);
        Route::get('/configs/{config}', [ConfigController::class, 'show']);
        //Route UPDATE
        Route::put('/configs/{config}', [ConfigController::class, 'update'])->middleware([RequireAuth::class]);

        /* -------- ROUTE LANGUAGES ---------- */
        //Route READ
        Route::get('/langs', [LanguageController::class, 'index']);
        Route::get('/langs/{lang}', [LanguageController::class, 'show']);
        Route::middleware([RequireAuth::class])->group(function () {
            //Route CREATE
            Route::post('/langs', [LanguageController::class, 'store']);
            //Route UPDATE
            Route::put('/langs/{lang}', [LanguageController::class, 'update']);
            //Route DELETE
            Route::delete('/langs/{lang}', [LanguageController::class, 'delete']);
        });

        /* -------- ROUTE TRADUZIONE ---------- */
        Route::prefix('/translations')->group(function () {
            //Route READ Traduzioni effettive
            Route::get('/', [TranslationController::class, 'index']);
            Route::get('/effective/{lang}', [TranslationController::class, 'indexByLanguage']);
            Route::get('/effective/{lang}/{key}', [TranslationController::class, 'show']);

            //Route READ tabelle
            Route::get('/default', [TranslationController::class, 'indexDefault']);
            Route::get('/default/{lang}/{key}', [TranslationController::class, 'showDefault']);
            Route::get('/custom', [TranslationController::class, 'indexCustom']);
            Route::get('/custom/{lang}/{key}', [TranslationController::class, 'showCustom']);

            Route::middleware([RequireAuth::class])->group(function () {
                //Route CREATE
                Route::post('/default/{lang}', [TranslationController::class, 'createDefault']);
                //Route UPDATE
                Route::put('/default/{lang}/{key}', [TranslationController::class, 'updateDefault']);
                //Route CREATE e UPDATE
                Route::put('/custom/{lang}/{key}', [TranslationController::class, 'upsertCustom']);
                //Route DELETE
                Route::delete('/default/{lang}/{key}', [TranslationController::class, 'deleteDefault']);
                Route::delete('/custom/{lang}/{key}', [TranslationController::class, 'deleteCustom']);
            });
        });

        /* -------- ROUTE CONTINENTI ---------- */
        Route::prefix('/continents')->group(function () {
            //Route READ
            Route::get('/', [ContinentController::class, 'index']);
            Route::get('/{continent}', [ContinentController::class, 'show']);
        });

        /* -------- ROUTE NAZIONI ---------- */
        Route::prefix('/countries')->group(function () {
            //Route READ
            Route::get('/', [CountryController::class, 'index']);
            Route::get('/{country}', [CountryController::class, 'show']);
        });

        /* -------- ROUTE REGIONI (ITALIANE) ---------- */
        Route::prefix('/italian-regions')->group(function () {
            //Route READ
            Route::get('/', [ItalianRegionController::class, 'index']);
            Route::get('/{region}', [ItalianRegionController::class, 'show']);
        });

        /* -------- ROUTE PROVINCE (ITALIANE) ---------- */
        Route::prefix('/italian-provinces')->group(function () {
            //Route READ
            Route::get('/', [ItalianProvinceController::class, 'index']);
            Route::get('/{province}', [ItalianProvinceController::class, 'show']);
        });

        /* -------- ROUTE COMUNI (ITALIANI) ---------- */
        Route::prefix('/italian-municipalities')->group(function () {
            //Route READ
            Route::get('/', [ItalianMunicipalityController::class, 'index']);
            Route::get('/{municipality}', [ItalianMunicipalityController::class, 'show']);
        });

        /* -------- ROUTE STATI UTENTE ---------- */
        Route::prefix('/states')->group(function () {
            //Route READ 
            Route::get('/', [StateController::class, 'index']);
            Route::get('/{state}', [StateController::class, 'show']);
            Route::middleware([RequireAuth::class])->group(function () {
                //Route UPDATE
                Route::put('/{state}', [StateController::class, 'update']);
            });
        });

        /* -------- ROUTE PERMESSI UTENTE ---------- */
        Route::prefix('/permissions')->group(function () {
            //Route READ
            Route::get('/', [PermissionController::class, 'index']);
            Route::get('/{permission}', [PermissionController::class, 'show']);
            Route::middleware([RequireAuth::class])->group(function () {
                //Route UPDATE
                Route::put('/{permission}', [PermissionController::class, 'updateDefault']);
            });
        });

        /* -------- ROUTE RUOLI UTENTE ---------- */
        Route::prefix('/roles')->group(function () {
            //Route READ
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/{role}', [RoleController::class, 'show']);
            Route::middleware([RequireAuth::class])->group(function () {
                //Route UPDATE
                Route::put('/{role}', [RoleController::class, 'updateDefault']);
            });
        });
    });

    /* -------- PROTETTE DA BEARER TOKEN ---------- */

    Route::middleware([TokenAuth::class])->group(function () {

        /* -------- ROUTE TEST E VARIE ---------- */

        Route::get('/test/bearer', function() {
            return 'it works!';
        });

        /* -------- ROUTE AUTENTICAZIONE ---------- */

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/users/{user}/force-logout', [AuthController::class, 'forceLogout']);

        /* -------- ROUTE UTENTI ---------- */

        //Route READ
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{userId}', [UserController::class, 'show']);
        //Route CREATE
        Route::post('/users', [UserController::class, 'store']);
        //Route UPDATE
        Route::put('/users/{userId}', [UserController::class, 'update']);
        //Route DELETE
        Route::delete('/users/{userId}', [UserController::class, 'destroy']);
        Route::delete('/users/{userId}/force', [UserController::class, 'forceDestroy']);
        //Route Ripristino
        Route::put('/users/{userId}/restore', [UserController::class, 'restore']);
        //Route Stato
        Route::put('/users/{userId}/ban', [UserController::class, 'ban']);
        Route::put('/users/{userId}/suspend', [UserController::class, 'suspend']);
        Route::put('/users/{userId}/lock', [UserController::class, 'lock']);
        Route::put('/users/{userId}/activate', [UserController::class, 'activate']);

        /* -------- ROUTE PROFILO ---------- */
        
        Route::get('/me', [ProfileController::class, 'showCurrentUser']);
        Route::put('/me', [ProfileController::class, 'updateCurrentUser']);
        Route::delete('/me', [ProfileController::class, 'destroyCurrentUser']);
        
        /* -------- ROUTE CREDITO ---------- */

        Route::prefix('/me')->group(function () {
            Route::get('/credit', [ProfileController::class, 'showCredit']);
            Route::put('/credit/add', [ProfileController::class, 'addCredit']);
            Route::put('/credit/remove', [ProfileController::class, 'removeCredit']);
        });

        /* -------- ROUTE CATEGORIE ---------- */

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        //Route CREATE
        Route::post('/categories', [CategoryController::class, 'store']); 
        //Route UPDATE
        Route::put('/categories/{category}', [CategoryController::class, 'update']); 
        //Route DELETE
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); 

        /* -------- ROUTE FILM ---------- */

        //Route READ
        Route::get('/films', [FilmController::class, 'index']);
        Route::get('/films/{filmId}', [FilmController::class, 'show']);
        //Route CREATE
        Route::post('/films', [FilmController::class, 'store']);
        //Route UPDATE
        Route::put('/films/{filmId}', [FilmController::class, 'update']);
        //Route DELETE
        Route::delete('/films/{filmId}', [FilmController::class, 'destroy']);
        Route::delete('/films/{filmId}/force', [FilmController::class, 'forceDestroy']);
        //Altre Route
        Route::put('/films/{filmId}/restore', [FilmController::class, 'restore']);

        /* -------- ROUTE SERIE TV ---------- */

        //Route READ
        Route::get('/tv-series', [TvSeriesController::class, 'index']);
        Route::get('/tv-series/{tvSeriesId}', [TvSeriesController::class, 'show']);
        //Route CREATE
        Route::post('/tv-series', [TvSeriesController::class, 'store']);
        //Route UPDATE
        Route::put('/tv-series/{tvSeriesId}', [TvSeriesController::class, 'update']);
        //Route DELETE
        Route::delete('/tv-series/{tvSeriesId}', [TvSeriesController::class, 'destroy']);
        Route::delete('/tv-series/{tvSeriesId}/force', [TvSeriesController::class, 'forceDestroy']);
        //Altre Route
        Route::put('/tv-series/{tvSeriesId}/restore', [TvSeriesController::class, 'restore']);

        /* -------- ROUTE EPISODIO ---------- */

        Route::prefix('/tv-series/{tvSeriesId}')->group(function () {
            //Route READ
            Route::get('/episodes', [EpisodeController::class, 'index']);
            Route::get('/episodes/{episodeId}', [EpisodeController::class, 'show']);
            //Route CREATE
            Route::post('/episodes', [EpisodeController::class, 'store']);
            //Route UPDATE
            Route::put('/episodes/{episodeId}', [EpisodeController::class, 'update']);
            //Route DELETE
            Route::delete('/episodes/{episodeId}', [EpisodeController::class, 'destroy']);
            Route::delete('/episodes/{episodeId}/force', [EpisodeController::class, 'forceDestroy']);
            //Altre Route
            Route::put('/episodes/{episodeId}/restore', [EpisodeController::class, 'restore']);
        });
    });
});
