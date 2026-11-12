import { Injectable, computed, inject } from '@angular/core';
import { AuthService } from './auth.service';
import { UtilitiesService } from './utilities.service';

interface TokenClaims {
  sub: string;
  exp: number;
  data?: { name?: string; roles?: string[] };
}

/** What the token says about the user. For showing/hiding UI only: the server enforces permissions */
@Injectable({ providedIn: 'root' })
export class SessionService {
  private readonly auth = inject(AuthService);

  private readonly claims = computed(() => {
    const token = this.auth.token();
    return token ? UtilitiesService.readJWTToken<TokenClaims>(token) : null;
  });

  readonly signedIn = computed(() => !!this.auth.token());
  readonly roles = computed(() => this.claims()?.data?.roles ?? []);
  readonly isAdmin = computed(() => this.roles().includes('admin'));
  readonly displayName = computed(() => this.claims()?.data?.name ?? 'Account');
}