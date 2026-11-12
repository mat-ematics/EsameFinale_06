import { ChangeDetectionStrategy, Component, ElementRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { NavigationEnd, Params, Router, RouterLink, RouterLinkActive } from '@angular/router';
import { filter } from 'rxjs';
import { AuthService } from '../../core/auth.service';
import { SessionService } from '../../core/session.service';
import { MediaCatalogService } from '../../core/media-catalog.service.js';

interface NavItem {
  label: string;
  link?: string | any[];
  queryParams?: Params;
  children?: NavItem[];
  /** Who sees it: 'member' = anyone signed in. Undefined = everybody */
  access?: 'member' | 'admin';
  /** Placeholder for a page that does not exist yet */
  disabled?: boolean;
}

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive, ReactiveFormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './navbar.component.html',
  host: {
    '(document:click)': 'onDocumentClick($event)',
    '(document:keydown.escape)': 'closeAll()',
  },
})
export class NavbarComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);
  private readonly catalog = inject(MediaCatalogService);
  protected readonly session = inject(SessionService);

  protected readonly menuOpen = signal(false);
  protected readonly openDropdown = signal<string | null>(null);
  protected readonly searchForm = inject(FormBuilder).nonNullable.group({ q: [''] });

  private readonly categories = toSignal(this.catalog.categories(), { initialValue: [] as string[] });

  /** The menu for the current user */
  protected readonly menu = computed<NavItem[]>(() => {
    const all: NavItem[] = [
      { label: 'Home', link: '/browse', access: 'member' },
      {
        label: 'Browse', access: 'member',
        children: [
          { label: 'Films', link: '/films' },
          { label: 'TV Series', link: '/tv-series' },
        ],
      },
      {
        label: 'Genre', access: 'member',
        children: this.categories().map(name => ({ label: name, link: ['/genre', name.toLowerCase()] })),
      },
      {
        label: 'Manage', access: 'admin',
        children: [
          { label: 'Add film', disabled: true },
          { label: 'Add TV series', disabled: true },
          { label: 'Categories', disabled: true },
          { label: 'Users', disabled: true },
        ],
      },
    ];

    const signedIn = this.session.signedIn();
    const admin = this.session.isAdmin();
    return all.filter(item => !item.access || (item.access === 'admin' ? admin : signedIn));
  });

  constructor() {
    // Close menus after every navigation
    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd), takeUntilDestroyed())
      .subscribe(() => this.closeAll());
  }

  protected toggleDropdown(label: string) {
    this.openDropdown.update(current => (current === label ? null : label));
  }

  protected closeAll() {
    this.openDropdown.set(null);
    this.menuOpen.set(false);
  }

  protected onDocumentClick(event: MouseEvent) {
    if (!this.host.nativeElement.contains(event.target as Node)) this.openDropdown.set(null);
  }

  protected submitSearch() {
    const q = this.searchForm.controls.q.value.trim();
    if (!q) return;
    this.router.navigate(['/search'], { queryParams: { q } });   // TODO: the results page will call the API
  }

  protected logout() {
    this.closeAll();
    this.auth.logout();
  }
}