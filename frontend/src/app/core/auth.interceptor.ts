// core/auth.interceptor.ts
import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';
import { AuthService } from './auth.service.js';
import { environment } from '../../environments/environment.js';

/** Funzione intercettatrice di richieste di autenticazione */
export const authInterceptor: HttpInterceptorFn = (req, next) => {

    /* ------- SERVIZI ------- */

    const auth = inject(AuthService);

    /* ------- PROPRIETA ------- */

    /** Il token inviato */
    const token = auth.token();
    /** Controlla se la richiesta è di tipo API */
    const isApi = req.url.startsWith(environment.apiUrl);

    //Clona la richiesta corrente impostando il bearer se il token esiste e la richiesta è API
    if (token && isApi) {
        req = req.clone({ setHeaders: { Authorization: `Bearer ${token}` } });
    }

    //Inoltra la richiesta e controlla errori
    return next(req).pipe(
        //se c'è un errore
        catchError((err: HttpErrorResponse) => {
            //se l'errore è 401 e la richiesta non è al login (per evitare loop)
            if (err.status === 401 && !req.url.endsWith('/login')) {
                //cancella autenticazione --> logout
                auth.clear();
            }
            //lancia errore
            return throwError(() => err);
        })
    );
};