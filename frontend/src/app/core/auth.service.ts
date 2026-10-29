// auth.service.ts
import { Injectable, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { concatMap, map, Observable, switchMap, tap } from 'rxjs';
import { ApiService } from './api.service.js';
import { UtilitiesService } from './utilities.service.js';

const KEY = 'access_token';

@Injectable({ providedIn: 'root' })
export class AuthService extends ApiService {

    /* ------- SERVIZI ------- */

    private router = inject(Router);

    /* ------- PROPRIETA ------- */

    /** Token riscrivibile ottenuto dallo storage */
    private _token = signal<string | null>(localStorage.getItem(KEY));
    /** Variabile token pubblica disponibile in sola lettura */
    readonly token = this._token.asReadonly();

    register(payload: object): Observable<void> {
        return this.request<{ token: string }>(['register'], 'POST', payload).pipe(
            map(res => { if (res.data?.token) this.setToken(res.data.token); })
        );
    }

    /** Two steps: get the user's salt, then send the salted password hash */
    /**
     * Effettua un login basato su challenge (a 2 step):
     * - prima invia l'hash utente e ottiene un sale
     * - poi invia l'hash utente e l'hash password con il sale ricevuto
     * - se le informazioni sono giuste l'utente è autenticato, altrimenti ritorna errore
     * @param user L'identificatore utente
     * @param password La password
     * @returns 
     */
    login(user: string, password: string): Observable<void> {
        //hash username e password
        const userHash = UtilitiesService.hash(user);
        const passwordHash = UtilitiesService.hash(password);

        //prima richiesta login
        return this.request<{ salt: string }>(['login', userHash], 'GET', { user: userHash })
        .pipe(
            //prima risposta
            map(res => {
                //se non c'è sale ritorna errore
                if (!res.data?.salt) throw new Error('NO_SALT');
                //altrimenti esegui nuovamente hash password con il sale ricevuto
                return UtilitiesService.hash(passwordHash, res.data.salt);
            }),
            //seconda richiesta login
            switchMap(salted => this.request<{ token: string }>(['login', userHash, salted], 'GET')),
            //seconda risposta
            map(res => {
                //se non c'è sale ritorna errore
                if (!res.data?.token) throw new Error('NO_TOKEN');
                //altirmenti setta il token
                this.setToken(res.data.token);
            })
        );
    }

    /** Esegue il logout */
    logout() {
        //invia una richiesta di logout al server
        this.request(['logout'], 'GET').subscribe({ error: () => {} });
        //pulisce l'autenticazione
        this.clear();
    }

    /** Pulisce l'autenticazione eliminando token e riportando alla pagina di login */
    clear() {
        //rimuove il token
        localStorage.removeItem(KEY);
        this._token.set(null);
        //riporta alla pagina di login
        this.router.navigate(['/login']);
    }

    /**
     * Controlla a ogni richiesta se il 
     * @returns Vero se autenticato altrimenti falso
     */
    isLoggedIn(): boolean {
        const t = this._token();
        return !!t && !UtilitiesService.isJWTExpired(t);
    }

    /**
     * Imposta in memoria e nella variabile locali il token ricevuto
     * @param token Il token da salvare
     */
    private setToken(token: string) {
        //imposta in memoria locale il token
        localStorage.setItem(KEY, token);
        //imposta la proprieta locale
        this._token.set(token);
    }
}