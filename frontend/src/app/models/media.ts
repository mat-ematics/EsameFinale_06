export type MediaType = 'film' | 'tv-series';

/** Enough to draw a card */
export interface MediaSummary {
  id: number;
  type: MediaType;
  title: string;
  image: string;
  year: number;
}

export interface Episode {
  id: number;
  season: number;
  number: number;
  title: string;
  durationMinutes: number;
}

export interface MediaDetail extends MediaSummary {
  description: string | null;
  durationMinutes?: number;          // films
  seasonCount?: number;              // series
  endYear?: number | null;           // series
  directors: string;
  actors: string;
  categories: string[];
  /** Not stored by the backend yet: the page hides these rows when missing */
  languages?: string[];
  subtitles?: string[];
  episodes?: Episode[];
}

export const MEDIA_LABEL: Record<MediaType, string> = { film: 'Films', 'tv-series': 'TV Series' };
const ROUTE_SEGMENT: Record<MediaType, string> = { film: 'films', 'tv-series': 'tv-series' };

export const mediaSectionLink = (type: MediaType) => ['/', ROUTE_SEGMENT[type]];
export const mediaLink = (item: Pick<MediaSummary, 'type' | 'id'>) => ['/', ROUTE_SEGMENT[item.type], item.id];
/** Film 1 and series 1 share an id: lists need a key that includes the type */
export const mediaTrackKey = (item: Pick<MediaSummary, 'type' | 'id'>) => `${item.type}-${item.id}`;