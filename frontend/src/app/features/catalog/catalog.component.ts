import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { Observable, filter, forkJoin, map, of, startWith, switchMap } from 'rxjs';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { SessionService } from '../../core/session.service';
import { HeroCarouselComponent } from '../../shared/hero-carousel/hero-carousel.component';
import { MediaRowComponent } from '../../shared/media-row/media-row.component';
import { AddTarget, Category, Crumb, MEDIA_LABEL, MediaSummary, MediaType, genreCrumb, typeCrumb } from '../../models/media';

interface Section {
  crumbs: Crumb[];
  add: AddTarget;
  items: MediaSummary[];
}

/** genre: undefined = this page has no genre, null = the genre in the URL does not exist */
interface Scope {
  type?: MediaType;
  genre?: Category | null;         // undefined = no genre on this page, null = the genre in the URL does not exist
}


@Component({
  selector: 'app-catalog',
  imports: [HeroCarouselComponent, MediaRowComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './catalog.component.html',
})
export class CatalogComponent {
  // Bound by the router: route data (type) and the :category path param (the slug, e.g. "sci-fi")
  readonly type = input<MediaType>();
  readonly category = input<string>();

  private readonly catalog = inject(MediaCatalogService);
  protected readonly session = inject(SessionService);

  /** undefined while the genre in the URL is being resolved to its real name */
    protected readonly scope = toSignal(
    toObservable(computed(() => ({ type: this.type(), slug: this.category() }))).pipe(
        switchMap(({ type, slug }): Observable<Scope | undefined> => slug
        ? this.catalog.genre(slug).pipe(map((genre): Scope => ({ type, genre })), startWith(undefined))
        : of({ type })),
    ),
    );

  protected readonly unknownGenre = computed(() => this.scope()?.genre === null);

  protected readonly title = computed(() => {
    const scope = this.scope();
    if (scope?.type) return MEDIA_LABEL[scope.type];
    return scope?.genre ? `Genre: ${scope.genre.label}` : 'Genre';
  });

  /** Only real scopes load anything: still resolving or unknown genre do not */
  private readonly ready$ = toObservable(this.scope).pipe(
    filter((scope): scope is Scope => !!scope && scope.genre !== null),
  );

  protected readonly slides = toSignal(
    this.ready$.pipe(switchMap(s => this.catalog.featured({ type: s.type, category: s.genre?.name ?? undefined }))),
    { initialValue: [] as MediaSummary[] },
  );

  /** undefined while loading */
  protected readonly sections = toSignal(this.ready$.pipe(switchMap(s => this.sectionsFor(s))));

  protected readonly isEmpty = computed(() => this.sections()?.every(s => !s.items.length) ?? false);

  private sectionsFor({ type, genre }: Scope): Observable<Section[]> {
    if (type) {
        // Type page: "New releases", then one carousel per genre
        return this.catalog.categories().pipe(
        switchMap(genres => forkJoin([
            this.catalog.latest(type),
            ...genres.map(c => this.catalog.list({ type, category: c.name })),
        ]).pipe(
            map(([latest, ...perGenre]): Section[] => [
            { crumbs: [typeCrumb(type), { label: 'New releases' }], add: { type }, items: latest },
            ...genres.map((c, i): Section => ({
                crumbs: [typeCrumb(type), genreCrumb(type, c)],
                add: { type, genre: c.name },
                items: perGenre[i],
            })),
            ]),
        )),
        );
    }

        // Genre page: the same genre in films and in TV series
        if (!genre) return of([]);
        const types: MediaType[] = ['film', 'tv-series'];

        return forkJoin(types.map(t => this.catalog.list({ type: t, category: genre.name }))).pipe(
            map(lists => types.map((t, i): Section => ({
            crumbs: [typeCrumb(t), genreCrumb(t, genre)],
            add: { type: t, genre: genre.name },
            items: lists[i],
            }))),
        );
    }
}