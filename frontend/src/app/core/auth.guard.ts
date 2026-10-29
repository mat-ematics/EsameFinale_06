import { inject } from "@angular/core";
import { CanActivateFn, Router } from "@angular/router";
import { AuthService } from "./auth.service.js";

/**
 * Controlla se l'utente è loggato prima di procedere con la richiesta, e riporta alla pagina di login se non lo è
 * @returns Vero oppure una nuova richiesta alla pagina di login
 */
export const authGuard: CanActivateFn = () => {
    const auth = inject(AuthService);
    //se è loggato --> true, altrimenti vai alla pagina di login
    return auth.isLoggedIn() ? true : inject(Router).createUrlTree(['/login']);
};

export const guestGuard: CanActivateFn = () =>
    inject(AuthService).isLoggedIn() ? inject(Router).createUrlTree(['/browse']) : true;