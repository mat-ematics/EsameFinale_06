<?php

use App\Helpers\AppHelpers;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\CategoryController;
use App\Http\Controllers\api\v1\EpisodeController;
use App\Http\Controllers\api\v1\FilmController;
use App\Http\Controllers\api\v1\ProfileController;
use App\Http\Controllers\api\v1\TvSeriesController;
use App\Http\Controllers\api\v1\UserController;
use App\Http\Middleware\ProtectLogin;
use App\Http\Middleware\ProtectNewLogin;
use App\Http\Middleware\TokenAuth;
use App\Models\Global\Config;
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

    /* -------- ROUTE CONFIGURAZIONE ---------- */
    //Route READ
    // Route::get('/config');

    /* -------- ROUTE AUTENTICAZIONE ---------- */

    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login/{userHash}', [AuthController::class, 'getLoginSalt']);
    Route::get('/login/{userHash}/{passwordHash}', [AuthController::class, 'newLogin'])->middleware(ProtectNewLogin::class);


    /* -------- PROTETTE DA BEARER TOKEN ---------- */

    Route::middleware([TokenAuth::class])->group(function () {

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
