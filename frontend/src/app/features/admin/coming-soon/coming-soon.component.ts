// features/admin/coming-soon/coming-soon.component.ts
import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MediaType } from '../../../models/media';

/** Placeholder for the admin pages that are not built yet. Delete it when the real ones exist */
@Component({
  selector: 'app-coming-soon',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="container py-5 min-vh-100">
      <h1 class="h3">{{ title() }}</h1>
      <p class="text-muted">This page is not built yet. It will receive:</p>
      <ul>
        @if (type()) { <li>Type: <strong>{{ type() }}</strong></li> }
        @if (genre()) { <li>Genre: <strong>{{ genre() }}</strong></li> }
      </ul>
      <a class="btn btn-outline-light btn-sm" routerLink="/browse">Back to browse</a>
    </main>
  `,
})
export class ComingSoonComponent {
  // Bound by the router: route data (title, type) and the ?genre= query param
  readonly title = input('Coming soon');
  readonly type = input<MediaType>();
  readonly genre = input<string>();
}