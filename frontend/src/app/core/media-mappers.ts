import { ApiEpisode, ApiMedia } from '../models/media-api';
import { Episode, MediaDetail, MediaSummary } from '../models/media';

const placeholder = (text: string, width = 640, height = 360) =>
  `https://placehold.co/${width}x${height}?text=${encodeURIComponent(text)}`;


export const toSummary = (row: ApiMedia): MediaSummary => ({
  id: row.id,
  type: row.type,
  title: row.name,
  image: row.cover_url ?? row.banner_url ?? placeholder(row.name),
  year: row.year,
  deleted: !!row.deleted_at,
});

/** The hero shows the banner, and falls back to the cover */
export const toSlide = (row: ApiMedia): MediaSummary => ({
  ...toSummary(row),
  image: row.banner_url ?? row.cover_url ?? placeholder(row.name, 1280, 400),
});

export const toEpisode = (row: ApiEpisode): Episode => ({
  id: row.id,
  season: row.season_number,
  number: row.episode_number,
  title: row.name,
  durationMinutes: row.duration_minutes ?? 0,
  description: row.description,
  length: row.length,
  year: row.year,
  videoUrl: row.video_url,
  image: row.cover_url,
  deleted: !!row.deleted_at,
});

export const toDetail = (row: ApiMedia, episodes: ApiEpisode[] = []): MediaDetail => ({
  ...toSummary(row),
  description: row.description,
  durationMinutes: row.duration_minutes ?? undefined,
  seasonCount: row.season_count,
  endYear: row.end_year,
  directors: row.directors,
  actors: row.actors,
  categories: row.categories,
  categoryLabels: row.category_labels ?? row.categories,
  bannerUrl: row.banner_url,
  videoUrl: row.video_url,
  episodes: episodes.map(toEpisode),
});