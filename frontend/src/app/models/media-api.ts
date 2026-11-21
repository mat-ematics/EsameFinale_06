import { MediaType } from './media';

export interface ApiMedia {
  id: number;
  type: MediaType;
  name: string;
  description: string | null;
  year: number;
  end_year?: number | null;
  season_count?: number;
  episode_count?: number;
  length?: string;
  duration_minutes?: number | null;
  directors: string;
  actors: string;
  categories: string[];
  category_labels?: string[];
  cover_url: string | null;
  banner_url: string | null;
  video_url?: string | null;
  deleted_at: string | null;
}

export interface ApiEpisode {
  id: number;
  tv_series_id: number;
  season_number: number;
  episode_number: number;
  name: string;
  description: string | null;
  length: string;
  duration_minutes: number | null;
  year: number;
  cover_url: string | null;
  video_url: string | null;
  deleted_at: string | null;
}