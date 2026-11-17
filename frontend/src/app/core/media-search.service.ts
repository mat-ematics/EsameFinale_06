import { Injectable } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiService } from './api.service';
import { MatchField, MediaType, SearchResult } from '../models/media';

interface SearchRow {
  type: MediaType;
  id: number;
  title: string;
  year: number;
  score: number;
  matched: MatchField[];
  snippet: string | null;
  /** The standard film / series resource, as the rest of the API returns it */
  media: Record<string, any>;
}

@Injectable({ providedIn: 'root' })
export class MediaSearchService extends ApiService {

  search(q: string, type?: MediaType): Observable<SearchResult[]> {
    return this.request<SearchRow[]>(['search'], 'GET', null, { q, limit: 24, ...(type ? { type } : {}) }).pipe(
      map(res => (res.data ?? []).map(row => ({
        id: row.id,
        type: row.type,
        title: row.title,
        year: row.year,
        image: coverUrl(row.media, row.title),
        score: row.score,
        matched: row.matched,
        snippet: row.snippet,
      }))),
    );
  }
}

/**
 * TODO: point this at the real cover field once the resource shape is known.
 * Paste the JSON of GET /api/v1/films/1 and it can be finished in one line.
 */
function coverUrl(media: Record<string, any>, title: string): string {
  const cover = media?.['files']?.find?.((file: any) => file.role === 'cover');
  return cover?.url ?? media?.['cover_url'] ?? `https://placehold.co/640x360?text=${encodeURIComponent(title)}`;
}