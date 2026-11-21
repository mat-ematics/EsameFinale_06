import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { catchError, map, of, startWith, switchMap } from 'rxjs';
import { apiErrorMessage } from '../../core/api.service';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { MediaSearchService } from '../../core/media-search.service';
import { MediaCardComponent } from '../../shared/media-card/media-card.component';
import { MediaCrumbsComponent } from '../../shared/media-crumbs/media-crumbs.component';
import {
  AddTarget, Crumb, MATCH_LABEL, MEDIA_LABEL, MediaType, SearchResult, mediaLink, mediaTrackKey, typeCrumb,
} from '../../models/media';

@Component({
  selector: 'app-media-list',
  imports: [MediaCardComponent, MediaCrumbsComponent, RouterLink],
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

  protected readonly key = mediaTrackKey;
  protected readonly link = mediaLink;
  protected readonly matchLabel = MATCH_LABEL;

  
    // 1. The genre signal now holds the Category
    protected readonly genre = toSignal(
    toObservable(this.category).pipe(
        switchMap(slug => (slug ? this.catalog.genre(slug).pipe(startWith(undefined)) : of(undefined))),
    ),
    );

  protected readonly unknown = computed(() => this.genre() === null);

  private readonly filter = computed(() => ({
  type: this.type(),
    // 2. In filter(): the API filters by the machine name
    category: this.genre()?.name,

    q: this.q()?.trim() || undefined,
    // The genre name is still being resolved: do not list everything meanwhile
    waiting: !!this.category() && this.genre() === undefined,
  }));

  protected readonly isSearch = computed(() => !!this.filter().q);

    /** Browsing. undefined = loading */
  protected readonly items = toSignal(
    toObservable(this.filter).pipe(
        switchMap(f => (f.q || f.waiting || this.unknown() ? of(undefined) : this.catalog.list(f))),
    ),
  );

  protected readonly title = computed(() => {
    const { q, type } = this.filter();
    const label = this.genre()?.label;
    if (q) return `Results for "${q}"`;
    if (type && label) return `${MEDIA_LABEL[type]}: ${label}`;
    if (type) return MEDIA_LABEL[type];
    return label ? `Genre: ${label}` : 'Catalog';
  });

  /** Breadcrumb of the grid: "Films › Animation" (the last step is where we are) */
  protected readonly crumbs = computed<Crumb[]>(() => {
    const { type } = this.filter();
    const label = this.genre()?.label;
    if (!type) return label ? [{ label }] : [];
    return label ? [typeCrumb(type), { label }] : [typeCrumb(type)];
  });

  protected readonly addTarget = computed<AddTarget | null>(() => {
    const { type, category } = this.filter();
    return type ? { type, genre: category } : null;
  });

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

    return text.split(new RegExp(`(${words.join('|')})`, 'gi'))
      .map((part, i) => ({ text: part, hit: i % 2 === 1 }))
      .filter(part => part.text);
  }
}