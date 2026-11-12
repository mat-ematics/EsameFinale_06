import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { switchMap } from 'rxjs';
import { MediaCardComponent } from '../../shared/media-card/media-card.component';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { SessionService } from '../../core/session.service';
import { MEDIA_LABEL, MediaType, mediaTrackKey } from '../../models/media';
import { toTitleCase } from '../../helpers/helpers.js';

@Component({
  selector: 'app-media-list',
  imports: [MediaCardComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './media-list.component.html',
  styleUrl: './media-list.component.scss',
})
export class MediaListComponent {
  // Bound by the router: route data, path params and query params
  readonly type = input<MediaType>();
  readonly category = input<string>();
  readonly q = input<string>();

  private readonly catalog = inject(MediaCatalogService);
  protected readonly session = inject(SessionService);
  protected readonly key = mediaTrackKey;

  protected readonly title = computed(() => {
    if (this.q()) return `Results for "${this.q()}"`;
    if (this.category()) return `Genre: ${toTitleCase(this.category())}`;
    const type = this.type();
    return type ? MEDIA_LABEL[type] : 'Catalog';
  });

  /** undefined while loading */
  protected readonly items = toSignal(
    toObservable(computed(() => ({ type: this.type(), category: this.category(), q: this.q() })))
      .pipe(switchMap(filter => this.catalog.list(filter))),
  );
}