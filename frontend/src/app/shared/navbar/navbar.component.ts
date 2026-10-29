import { Component, inject, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './navbar.component.html',
})
export class NavbarComponent {
  protected auth = inject(AuthService);
  protected menuOpen = signal(false);

  toggle() { this.menuOpen.update(open => !open); }
  logout() { this.menuOpen.set(false); this.auth.logout(); }
}