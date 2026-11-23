import { Injectable, inject } from '@angular/core';
import { SessionService } from './session.service';

interface Entry {
  position: number;       // seconds
  duration: number;
  watched?: boolean;
  at: number;
}

/**
 * Where each user stopped, kept in this browser. Keys: film:12, episode:3:40, last:3 (last episode of a series).
 * TODO: sync with the server when there is an endpoint, so it follows the user across devices.
 */
@Injectable({ providedIn: 'root' })
export class WatchProgressService {
  private readonly session = inject(SessionService);

  private storageKey(key: string) { return `watch:${this.session.userId()}:${key}`; }

  private read<T>(key: string): T | null {
    try {
      const raw = localStorage.getItem(this.storageKey(key));
      return raw ? (JSON.parse(raw) as T) : null;
    } catch {
      return null;
    }
  }

  private write(key: string, value: unknown) {
    try { localStorage.setItem(this.storageKey(key), JSON.stringify(value)); } catch { /* storage full or blocked */ }
  }

  get(key: string): Entry | null { return this.read<Entry>(key); }

  save(key: string, position: number, duration: number) {
    this.write(key, { position: Math.floor(position), duration: Math.floor(duration), at: Date.now() } satisfies Entry);
  }

  markWatched(key: string, duration: number) {
    this.write(key, { position: 0, duration: Math.floor(duration), watched: true, at: Date.now() } satisfies Entry);
  }

  /** 0-100 */
  percent(key: string): number {
    const entry = this.get(key);
    if (!entry) return 0;
    return entry.watched ? 100 : Math.min(100, Math.round((100 * entry.position) / (entry.duration || 1)));
  }

  lastEpisode(seriesId: number): number | null { return this.read<number>(`last:${seriesId}`); }
  setLastEpisode(seriesId: number, episodeId: number) { this.write(`last:${seriesId}`, episodeId); }

  autoNext(): boolean { return this.read<boolean>('autonext') ?? true; }
  setAutoNext(value: boolean) { this.write('autonext', value); }
}