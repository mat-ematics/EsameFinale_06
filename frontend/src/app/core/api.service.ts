import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { API_V1 } from './api.config.js';
import { HttpRequestType, HttpResponseInterface, HttpResponseObservable } from './http.types.js';

/**
 * Estrae un messaggio leggibile da qualsiasi errore lanciato da una richiesta 
 * @param err L'errore lanciato
 * @returns Il messaggio di errore
 */
export function apiErrorMessage(err: unknown): string {
    //se err è un errore HTTP
    if (err instanceof HttpErrorResponse) {
        //estrai il corpo, che puo' essere nullo
        const body = err.error as Partial<HttpResponseInterface> | null;
        //ritorna l'errore nel corpo, altrimenti il messaggio nel corpo, altrimenti il messaggio nell'errore
        return body?.error ?? body?.message ?? err.message;
    }

    //se non è errore HTTP ritorna il messaggio passato oppure errore generico
    return err instanceof Error ? err.message : 'UNKNOWN_ERROR';
}

@Injectable({
    providedIn: 'root',
})
export class ApiService {

    /* ------- SERVIZI ------- */
    
    private http = inject(HttpClient);
    
    /* ------- METODI ------- */

    /**
     * Calcola l'esatto endpoint della risorsa
     * @param resource La risorsa da cui costruire l'URL
     * @returns L'URL endpoint del server
     */
    protected getURL(resource: (string | number)[]): string {
        //taglia i / finali dalla base API
        const base = API_V1.replace(/\/+$/, '');
        //unisci ogni parte della risorsa in un percorso
        const path = resource.map(part => encodeURIComponent(String(part))).join('/');
        //unisci base e percorso per ottenere l'URL
        return `${base}/${path}`;
    }

    /**
     * Genera un oggetto di risposta errore
     * @param error L'errore da ritornare
     * @returns L'oggetto errore
     */
    protected generateErrorResponse(error: string): HttpResponseInterface {
        return {data: null, message: null, error: error};
    }

    /**
     * Genera un Observable di Errore
     * @param error L'errore da ritornare
     * @returns L'observable di errore
     */
    protected generateErrorResponseObs(error: string): Observable<HttpResponseInterface> {
        //genera un nuovo oggetto errore
        const errObj: HttpResponseInterface = this.generateErrorResponse(error);
        //ritorna l'errore in un Observable wrapper
        return of(errObj);
    }

    /**
     * Effettua una richiesta HTTP API.
     * Il tipo passato in TS indica il tipo di risposta dentro il campo 'data' di risposta
     * @param resource I segmenti URL della risorsa, ad esempio ['movies', 5]
     * @param method Il metodo della richiesta (GET | POST | PUT | DELETE)
     * @param body Corpo di dati aggiuntivi inviato solo con POST/PUT (oggetto vuoto di default)
     * @returns Un Observable della risposta di tipo specificato
     */
    protected request<T = unknown>(
        resource: (string | number)[],
        method: HttpRequestType,
        body: object | null = null,
        params?: Record<string, string | number>,
    ): HttpResponseObservable<T> {
        //controlla se la richiesta necessita un corpo
        const hasBody = method === 'POST' || method === 'PUT';
        //effettua la richiesta
        return this.http.request<HttpResponseInterface<T>>(
            method,
            this.getURL(resource), 
            //se necessita body e ne ha uno passato lo invia, altrimenti spedisce un corpo vuoto
            {
                ...(hasBody ? { body: body ?? {} } : {}),
                params,
            }
        );
    }
}
