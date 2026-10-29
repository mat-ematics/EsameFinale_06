import { sha256 } from 'js-sha256';
import { jwtDecode, JwtPayload } from 'jwt-decode';

export class UtilitiesService {

    /** Numero intero randomico tra un minimo e un massimo compresi */
    static randomNumber(min: number, max: number): number {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    /**
     * Decodifica il payload di un Token JWT
     * @param token Il token da decodificare
     * @returns Il payload oppure null se fallisce
     */
    static readJWTToken<T = JwtPayload>(token: string): T | null {
        try {
            return jwtDecode<T>(token);
        } catch {
            return null;
        }
    }

    /**
     * Controlla se il token è scaduto
     * @param token Token JWT
     * @param skewSeconds Margine di sicurezza in secondi entro cui
     *                    il token è considerato scaduto questi secondi prima di exp (default 10)
     * @returns Vero se il token è illeggibile, non ha scadenza (exp) oppure scade entro skewSeconds
     */
    static isJWTExpired(token: string, skewSeconds = 10): boolean {
        const payload = UtilitiesService.readJWTToken(token);
        if (!payload?.exp) return true;
        return Date.now() >= (payload.exp - skewSeconds) * 1000;
    }

    /**
     * Esegue Hash256 di sale + stringa
     * @param str La stringa di cui eseguire l'hash
     * @param salt Il sale dell'hash
     * @returns Hash256 della concatenazione tra sale e stringa
     */
    static hash(str: string, salt: string = ''): string {
        return sha256(salt + str);
    }
}