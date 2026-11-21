import { Injectable } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, forkJoin, map, of, shareReplay, throwError } from 'rxjs';
import { ApiService } from './api.service';
import { toDetail, toSlide, toSummary } from './media-mappers';
import { ApiEpisode, ApiMedia } from '../models/media-api';
import { Category, MediaDetail, MediaSummary, MediaType, matchesGenre, mediaSegment, sameGenre } from '../models/media';

export interface MediaFilter {
  type?: MediaType;
  category?: string;
  q?: string;
}

@Injectable({ providedIn: 'root' })
export class MediaCatalogService extends ApiService {

  /** The genres rarely change: fetched once and shared */
  private readonly all$ = this.request<Category[]>(['categories'], 'GET', null, { compact: 1 }).pipe(
    map(res => (res.data ?? []).map(({ name, label }) => ({ name, label: label || name }))),
    shareReplay({ bufferSize: 1, refCount: false }),
    );

    categories(): Observable<Category[]> {
        return this.all$;
    }

    /** The genre behind a URL slug, ignoring case. null = no such genre */
    genre(slug: string): Observable<Category | null> {
        return this.all$.pipe(map(list => list.find(category => matchesGenre(category, slug)) ?? null));
    }


  featured(filter: MediaFilter = {}): Observable<MediaSummary[]> {
    const params: Record<string, string | number> = { limit: 5 };
    if (filter.type) params['type'] = filter.type;
    if (filter.category) params['category'] = filter.category;

    return this.request<ApiMedia[]>(['media', 'featured'], 'GET', null, params).pipe(
      map(res => (res.data ?? []).map(toSlide)),
    );
  }

  latest(type: MediaType): Observable<MediaSummary[]> {
    return this.fetch(type, { limit: 10 });
  }

  list(filter: MediaFilter): Observable<MediaSummary[]> {
    const params: Record<string, string | number> = {};
    if (filter.category) params['category'] = filter.category;
    if (filter.q) params['name'] = filter.q;

    return filter.type
      ? this.fetch(filter.type, params)
      : forkJoin([this.fetch('film', params), this.fetch('tv-series', params)]).pipe(map(([a, b]) => [...a, ...b]));
  }

  /** null = not found */
  detail(type: MediaType, id: number): Observable<MediaDetail | null> {
    if (!Number.isInteger(id)) return of(null);

    const media$ = this.request<ApiMedia>([mediaSegment(type), id], 'GET');
    const episodes$ = type === 'tv-series'
      ? this.request<ApiEpisode[]>([mediaSegment(type), id, 'episodes'], 'GET').pipe(map(res => res.data ?? []))
      : of([] as ApiEpisode[]);

    return forkJoin([media$, episodes$]).pipe(
      map(([res, episodes]) => (res.data ? toDetail(res.data, episodes) : null)),
      catchError(err => err instanceof HttpErrorResponse && err.status === 404 ? of(null) : throwError(() => err)),
    );
  }

  related(media: MediaDetail): Observable<MediaSummary[]> {
    const genre = media.categories[0];
    if (!genre) return of([]);

    return this.list({ type: media.type, category: genre }).pipe(
      map(items => items.filter(item => item.id !== media.id).slice(0, 10)),
    );
  }

  private fetch(type: MediaType, params: Record<string, string | number>): Observable<MediaSummary[]> {
    return this.request<ApiMedia[]>([mediaSegment(type)], 'GET', null, params).pipe(
      map(res => (res.data ?? []).map(toSummary)),
    );
  }
}