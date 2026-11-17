import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { catchError, map, of, switchMap } from 'rxjs';
import { apiErrorMessage } from '../../core/api.service';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { MediaSearchService } from '../../core/media-search.service';
import { SessionService } from '../../core/session.service';
import { MediaCardComponent } from '../../shared/media-card/media-card.component';
import { MATCH_LABEL, MEDIA_LABEL, MediaType, SearchResult, mediaLink, mediaTrackKey } from '../../models/media';

@Component({
  selector: 'app-media-list',
  imports: [MediaCardComponent, RouterLink],
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
  private readonly searchApi = inject(MediaSearchService);
  protected readonly session = inject(SessionService);

  protected readonly key = mediaTrackKey;
  protected readonly link = mediaLink;
  protected readonly matchLabel = MATCH_LABEL;

  private readonly filter = computed(() => ({
    type: this.type(), category: this.category(), q: this.q()?.trim() || undefined,
  }));
  protected readonly isSearch = computed(() => !!this.filter().q);

  protected readonly title = computed(() => {
    const q = this.filter().q;
    if (q) return `Results for "${q}"`;
    if (this.category()) return `Genre: ${this.category()}`;
    const type = this.type();
    return type ? MEDIA_LABEL[type] : 'Catalog';
  });

  /** Browsing. undefined = loading */
  protected readonly items = toSignal(
    toObservable(this.filter).pipe(switchMap(f => (f.q ? of(undefined) : this.catalog.list(f)))),
  );

  /** Searching. undefined = loading. Errors travel as a value: no signal writes inside the pipe */
  protected readonly searchState = toSignal(
    toObservable(this.filter).pipe(
      switchMap(f => !f.q ? of(undefined) : this.searchApi.search(f.q, f.type).pipe(
        map(results => ({ results, error: null as string | null })),
        catchError(err => of({ results: [] as SearchResult[], error: apiErrorMessage(err) })),
      )),
    ),
  );

  /** Splits a text into parts, marking the ones that contain a searched word (no innerHTML needed) */
  protected highlight(text: string): { text: string; hit: boolean }[] {
    const words = (this.filter().q ?? '').split(/\s+/)
      .filter(word => word.length >= 2)
      .map(word => word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    if (!words.length) return [{ text, hit: false }];

    // With one capturing group, the odd positions of split() are the matches
    return text.split(new RegExp(`(${words.join('|')})`, 'gi'))
      .map((part, i) => ({ text: part, hit: i % 2 === 1 }))
      .filter(part => part.text);
  }
}