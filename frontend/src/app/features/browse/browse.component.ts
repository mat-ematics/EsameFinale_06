// features/browse/browse.component.ts
import { Component, inject, signal } from '@angular/core';
import { JsonPipe } from '@angular/common';
import { AuthService } from '../../core/auth.service';
import { ProfileService } from '../../core/profile.service';
import { UtilitiesService } from '../../core/utilities.service';
import { apiErrorMessage } from '../../core/api.service';

interface TokenData {
  sub: string;
  exp: number;
  data?: { username?: string; name?: string; primary_role?: string; roles?: string[] };
}

@Component({
  selector: 'app-browse',
  imports: [JsonPipe],
  templateUrl: './browse.component.html',
})
export class BrowseComponent {
  private auth = inject(AuthService);
  private profile = inject(ProfileService);

  /** Read from the JWT: display only, never use it for permissions */
  protected claims = UtilitiesService.readJWTToken<TokenData>(this.auth.token() ?? '');

  protected status = signal<'loading' | 'ok' | 'error'>('loading');
  protected message = signal('');
  protected result = signal<unknown>(null);

  constructor() {
    this.profile.getMe().subscribe({
      next: res => {
        this.status.set('ok');
        this.message.set(res.message ?? 'OK');
        this.result.set(res.data);
      },
      error: err => {
        this.status.set('error');
        this.message.set(apiErrorMessage(err));
      },
    });
  }

  protected expiresAt(): string {
    return this.claims?.exp ? new Date(this.claims.exp * 1000).toLocaleString() : '-';
  }
}