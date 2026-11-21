import { RouterLinkWithHref } from "@angular/router";

export type MediaType = 'film' | 'tv-series';

/** Enough to draw a card */
export interface MediaSummary {
  id: number;
  type: MediaType;
  title: string;
  image: string;
  year: number;
  /** Admins also see soft-deleted titles */
  deleted?: boolean;
}

export interface Episode {
  id: number;
  season: number;
  number: number;
  title: string;
  durationMinutes: number;
  description?: string | null;
  length?: string;
  year?: number;
  videoUrl?: string | null;
  image?: string | null;
  deleted?: boolean;
}

export interface MediaDetail extends MediaSummary {
  description: string | null;
  durationMinutes?: number;
  seasonCount?: number;
  endYear?: number | null;
  directors: string;
  actors: string;
  categories: string[];
  categoryLabels?: string[];
  bannerUrl?: string | null;
  videoUrl?: string | null;
  languages?: string[];
  subtitles?: string[];
  episodes?: Episode[];
}

export const MEDIA_LABEL: Record<MediaType, string> = { film: 'Films', 'tv-series': 'TV Series' };
const ROUTE_SEGMENT: Record<MediaType, string> = { film: 'films', 'tv-series': 'tv-series' };
/** 'films' or 'tv-series': the API and the routes use the same word */
export const mediaSegment = (type: MediaType): string => ROUTE_SEGMENT[type];

export const mediaSectionLink = (type: MediaType) => ['/', ROUTE_SEGMENT[type]];
export const mediaLink = (item: Pick<MediaSummary, 'type' | 'id'>) => ['/', ROUTE_SEGMENT[item.type], item.id];
/** Film 1 and series 1 share an id: lists need a key that includes the type */
export const mediaTrackKey = (item: Pick<MediaSummary, 'type' | 'id'>) => `${item.type}-${item.id}`;

export type MatchField = 'title' | 'description' | 'genre' | 'director' | 'cast' | 'year' | 'duration' | 'episodes';

export interface SearchResult extends MediaSummary {
  score: number;
  /** Where the text was found, most important first */
  matched: MatchField[];
  /** The part of the description around the match */
  snippet: string | null;
}

export const MATCH_LABEL: Record<MatchField, string> = {
  title: 'Title', description: 'Description', genre: 'Genre', director: 'Director',
  cast: 'Cast', year: 'Year', duration: 'Duration', episodes: 'Episodes',
};

/** One step of a breadcrumb. Without a link it is plain text (the current place) */
export interface Crumb {
  label: string;
  link?: string | any[];
}

/** Where an "Add" button leads and what it prefills */
export interface AddTarget {
  type: MediaType;
  genre?: string;
}

export const typeCrumb = (type: MediaType): Crumb =>
  ({ label: MEDIA_LABEL[type], link: mediaSectionLink(type) });

export interface Category {
  name: string;          // technical key: API filters, form values, URLs
  label: string;         // what people read
}

/** A URL slug matches the machine name or the label: /genre/scifi and /genre/sci-fi are the same genre */
export const matchesGenre = (category: Category, slug: string): boolean => {
  const wanted = genreSlug(slug);
  return genreSlug(category.name) === wanted || genreSlug(category.label) === wanted;
};

/** URL form of a genre: "Sci-Fi" -> "sci-fi", "Science Fiction" -> "science-fiction" */
export const genreSlug = (name: string): string =>
  name.normalize('NFD').replace(/[\u0300-\u036f]/g, '')      // accents: "Fantascienza", "Azione"…
      .toLowerCase()
      .replace(/[^\p{L}\p{N}]+/gu, '-')
      .replace(/^-+|-+$/g, '');

/** Case- and accent-insensitive: "Animation", "animation" and "ANIMATION" are the same genre */
export const sameGenre = (a: string, b: string): boolean => genreSlug(a) === genreSlug(b);

/** A genre across both types: /genre/sci-fi */
export const genreLink = (category: Category) => ['/genre', genreSlug(category.name)];

/** The label keeps the real name, the link uses the slug */
export const genreCrumb = (type: MediaType, category: Category): Crumb =>
  ({ label: category.label, link: ['/', ROUTE_SEGMENT[type], 'genre', genreSlug(category.name)] });

/** The future "Insert film" page: /admin/films/new?genre=horror */
export const mediaAddLink = (target: AddTarget) => ({
  commands: ['/admin', ROUTE_SEGMENT[target.type], 'new'],
  queryParams: target.genre ? { genre: genreSlug(target.genre) } : {},
});