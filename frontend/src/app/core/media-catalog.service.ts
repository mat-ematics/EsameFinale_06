import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
import { MediaDetail, MediaSummary, MediaType } from '../models/media';

/**
 * MOCK DATA. Replace the bodies with ApiService calls (/films, /tv-series, /categories)
 * and map the API resources to these models. The signatures can stay as they are.
 */

const LOCAL_IMAGE = 'media/images/film_example.jpg';
const CATEGORIES = ['Animation', 'Sci-Fi', 'Horror'];

const placeholder = (width: number, height: number, text: string) =>
  `https://placehold.co/${width}x${height}?text=${encodeURIComponent(text)}`;

const FILMS: MediaDetail[] = Array.from({ length: 12 }, (_, i) => {
  const id = i + 1;
  return {
    id,
    type: 'film',
    title: id === 1 ? 'Nathan' : `Film ${id}`,
    year: 2001 + i,
    image: id === 1 ? LOCAL_IMAGE : placeholder(640, 360, `Film ${id}`),
    description: id === 1
      ? 'Nathan discovers the world together with the audience he takes along on a dazzling adventure. Life.'
      : 'Placeholder description.',
    durationMinutes: 67 + i * 5,
    directors: 'Sean Decolavic',
    actors: 'Mike Dafoe',
    categories: [CATEGORIES[i % CATEGORIES.length]],
    languages: ['Italian'],
    subtitles: ['Italian', 'English', 'French'],
  };
});

const SERIES: MediaDetail[] = Array.from({ length: 12 }, (_, i) => {
  const id = i + 1;
  return {
    id,
    type: 'tv-series',
    title: `Series ${id}`,
    year: 2010 + i,
    endYear: 2015 + i,
    seasonCount: 2,
    image: placeholder(640, 360, `Series ${id}`),
    description: 'Placeholder description.',
    directors: 'Sean Decolavic',
    actors: 'Mike Dafoe',
    categories: [CATEGORIES[i % CATEGORIES.length]],
    episodes: [1, 2].flatMap(season =>
      [1, 2, 3, 4].map(number => ({
        id: season * 100 + number, season, number, title: `Episode ${number}`, durationMinutes: 42,
      }))),
  };
});

export interface MediaFilter {
  type?: MediaType;
  category?: string;
  q?: string;
}

const toSummary = ({ id, type, title, image, year }: MediaDetail): MediaSummary =>
  ({ id, type, title, image, year });
const source = (type: MediaType) => (type === 'film' ? FILMS : SERIES);

@Injectable({ providedIn: 'root' })
export class MediaCatalogService {

  featured(): Observable<MediaSummary[]> {
    return of([FILMS[0], SERIES[0], FILMS[1], SERIES[1]].map(m => ({
      ...toSummary(m),
      image: m === FILMS[0] ? LOCAL_IMAGE : placeholder(1280, 400, m.title),
    })));
  }

  latest(type: MediaType): Observable<MediaSummary[]> {
    return of(source(type).slice(0, 10).map(toSummary));
  }

  list(filter: MediaFilter): Observable<MediaSummary[]> {
    const q = filter.q?.trim().toLowerCase();
    const category = filter.category?.trim().toLowerCase();
    const pool = filter.type ? source(filter.type) : [...FILMS, ...SERIES];

    return of(pool
      .filter(m => (!category || m.categories.map(c => c.toLowerCase()).includes(category))
                && (!q || m.title.toLowerCase().includes(q)))
      .map(toSummary));
  }

  /** null = not found */
  detail(type: MediaType, id: number): Observable<MediaDetail | null> {
    return of(source(type).find(m => m.id === id) ?? null);
  }

  related(media: MediaDetail): Observable<MediaSummary[]> {
    return of(source(media.type)
      .filter(m => m.id !== media.id && m.categories.some(c => media.categories.includes(c)))
      .slice(0, 10)
      .map(toSummary));
  }

  categories(): Observable<string[]> {
    return of(CATEGORIES);
  }
}