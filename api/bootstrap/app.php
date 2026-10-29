<?php

use App\Exceptions\InvalidCredentialsException;
use App\Helpers\AppHelpers;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('admin', [\App\Http\Middleware\RequireAuth::class, 'can:isAdmin']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //stampa errore se le chiamate API sono non JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $r, \Throwable $e) => $r->is('api/*') || $r->expectsJson()
        );

        //non loggare l'errore
        $exceptions->dontReport([InvalidCredentialsException::class]);

        // Validazione (FormRequests): gli errori vanno dentro `data` con campi come chiavi
        $exceptions->render(function (ValidationException $e, Request $r) {
            if ($r->is('api/*')) {
                Log::alert($e->errors());
                return AppHelpers::jsonResponse('Validation failed', 422, $e->errors());
            }
        });

        //Errore di Autenticazione
        $exceptions->render(function (AuthenticationException $e, Request $r) {
            if ($r->is('api/*')) {
                return AppHelpers::jsonResponse('Unauthenticated', 401);
            }
        });

        // errori findOrFail() arrivano qui come NotFoundHttpException con l'originale
        // ModelNotFoundException attaccato: costruisci il messaggio di errore dal nome del modello.
        // Tutto il resto (abort(404, 'msg'), route sconosciute) ritornano null e decadono.
        $exceptions->render(function (NotFoundHttpException $e, Request $r) {
            $previous = $e->getPrevious();
            if ($r->is('api/*') && $previous instanceof ModelNotFoundException) {
                return AppHelpers::jsonResponse(
                    Str::headline(class_basename($previous->getModel())) . ' Not Found', 404
                );
            }
        });

        // Tutte le eccezioni lanciate di proposito con uno status: abort(), HttpException,
        // AccessDeniedHttpException, throttling (429), errori di autorizzazione (403)...
        $exceptions->render(function (HttpExceptionInterface $e, Request $r) {
            if ($r->is('api/*')) {
                return AppHelpers::jsonResponse($e->getMessage(), $e->getStatusCode());
            }
        });

        // Tutto il resto e' considerato bug, loggato automaticamente e nascosto al client
        $exceptions->render(function (\Throwable $e, Request $r) {
            if ($r->is('api/*')) {
                return AppHelpers::jsonResponse(config('app.debug') ? $e->getMessage() : 'Server error', 500);
            }
        });
    })->create();
