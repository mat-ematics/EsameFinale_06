import {
  ChangeDetectionStrategy, Component, DestroyRef, ElementRef, computed, effect, inject, input, signal, untracked, viewChild,
} from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { Title } from '@angular/platform-browser';
import { Router, RouterLink } from '@angular/router';
import { startWith, switchMap } from 'rxjs';
import { loaded } from '../../core/load-state';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { WatchProgressService } from '../../core/watch-progress.service';
import { Episode, MediaType, mediaLink } from '../../models/media';

const NEXT_DELAY = 8;          // seconds before the next episode starts by itself
const RESUME_MIN = 5;          // before this, starting from the beginning is simpler
const SAVE_EVERY_MS = 5000;

const ERROR_TEXT: Record<number, string> = {
  1: 'The video was stopped.',
  2: 'The connection was lost while loading the video.',
  3: 'The video file is damaged or uses an unsupported format.',
  4: 'The video cannot be played: it is missing or its format is not supported.',
};

/** What is happening with the current source. It resets by itself when the source changes (see `ui`) */
interface Ui {
  key: string;
  ended: boolean;
  error: string | null;
  resumedAt: number | null;
  countdown: number | null;
}
const blank = (key: string): Ui => ({ key, ended: false, error: null, resumedAt: null, countdown: null });

const pad = (n: number) => String(n).padStart(2, '0');

@Component({
  selector: 'app-watch',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './watch.component.html',
  styleUrl: './watch.component.scss',
  host: {
    '(document:keydown)': 'onKey($event)',
    '(document:fullscreenchange)': 'onFullscreenChange()',
  },
})
export class WatchComponent {
  // Bound by the router: route data, the :id path param and the ?episode= query param
  readonly type = input.required<MediaType>();
  readonly id = input.required<string>();
  readonly episodeId = input<string | undefined>(undefined, { alias: 'episode' });

  private readonly catalog = inject(MediaCatalogService);
  private readonly progress = inject(WatchProgressService);
  private readonly router = inject(Router);
  private readonly pageTitle = inject(Title);
  private readonly video = viewChild<ElementRef<HTMLVideoElement>>('video');
  private readonly stage = viewChild<ElementRef<HTMLElement>>('stage');

  /* --------- Loading the title (an error is a value, never an exception) --------- */

  protected readonly reload = signal(0);

  /** undefined = loading */
  private readonly state = toSignal(
    toObservable(computed(() => ({ type: this.type(), id: Number(this.id()), reload: this.reload() }))).pipe(
      switchMap(({ type, id }) => loaded(this.catalog.detail(type, id)).pipe(startWith(undefined))),
    ),
  );

  protected readonly loading = computed(() => this.state() === undefined);
  protected readonly failure = computed(() => this.state()?.error ?? null);
  protected readonly media = computed(() => this.state()?.data ?? null);

  /* --------- What is playing --------- */

  private readonly playable = computed<Episode[]>(() =>
    (this.media()?.episodes ?? []).filter(e => !e.deleted && !!e.videoUrl));

  protected readonly episode = computed(() => this.playable().find(e => e.id === Number(this.episodeId())) ?? null);

  protected readonly next = computed(() => {
    const list = this.playable();
    const at = list.findIndex(e => e.id === this.episode()?.id);
    return at >= 0 ? list[at + 1] ?? null : null;
  });

  protected readonly source = computed(() =>
    this.type() === 'film' ? this.media()?.videoUrl ?? null : this.episode()?.videoUrl ?? null);

  protected readonly poster = computed(() => this.media()?.bannerUrl ?? this.media()?.image ?? null);
  protected readonly back = computed(() => (this.media() ? mediaLink(this.media()!) : ['/browse']));

  protected readonly heading = computed(() => {
    const m = this.media();
    const e = this.episode();
    if (!m) return '';
    return e ? `${m.title} · S${e.season} E${e.number} · ${e.title}` : m.title;
  });

  /** Identifies the current source for the saved progress */
  private readonly key = computed(() => {
    const m = this.media();
    if (!m) return '';
    if (this.type() === 'film') return `film:${m.id}`;
    return this.episode() ? `episode:${m.id}:${this.episode()!.id}` : '';
  });

  /** Episodes grouped by season, with how much of each was watched */
  private readonly tick = signal(0);
  protected readonly seasons = computed(() => {
    this.tick();                                         // recomputed when progress is saved
    const seriesId = this.media()?.id;
    const groups = new Map<number, { episode: Episode; percent: number }[]>();

    for (const episode of this.playable()) {
      const rows = groups.get(episode.season) ?? [];
      rows.push({ episode, percent: this.progress.percent(`episode:${seriesId}:${episode.id}`) });
      groups.set(episode.season, rows);
    }
    return [...groups].map(([season, rows]) => ({ season, rows }));
  });

  /* --------- Player state --------- */

  private readonly uiState = signal<Ui>(blank(''));
  protected readonly ui = computed(() => {
    const state = this.uiState();
    return state.key === this.key() ? state : blank(this.key());       // a new source starts clean
  });

  protected readonly buffering = signal(false);
  protected readonly fullscreen = signal(false);
  protected readonly autoNext = signal(this.progress.autoNext());

  private timer?: ReturnType<typeof setInterval>;
  private lastSave = 0;

  constructor() {
    // A series without a valid ?episode=: continue the last one watched, or start the first
    effect(() => {
      const m = this.media();
      if (!m || this.type() !== 'tv-series' || this.episode()) return;

      const last = this.progress.lastEpisode(m.id);
      const target = this.playable().find(e => e.id === last) ?? this.playable()[0];
      if (target) {
        untracked(() => this.router.navigate(['/watch', 'tv-series', m.id], {
          queryParams: { episode: target.id }, replaceUrl: true,
        }));
      }
    });

    // Leaving a source (another episode, or the page): save where it stopped, stop the countdown
    effect(onCleanup => {
      const key = this.key();
      onCleanup(() => { this.clearTimer(); this.saveFor(key); });
    });

    // The last episode watched of a series
    effect(() => {
      const m = this.media();
      const e = this.episode();
      if (m && e) this.progress.setLastEpisode(m.id, e.id);
    });

    // The browser tab shows the title
    const previous = this.pageTitle.getTitle();
    effect(() => this.pageTitle.setTitle(this.heading() ? `${this.heading()} · CODE` : previous));
    inject(DestroyRef).onDestroy(() => this.pageTitle.setTitle(previous));
  }

  /* --------- Helpers --------- */

  private patch(change: Partial<Ui>) {
    const key = this.key();
    this.uiState.update(s => ({ ...(s.key === key ? s : blank(key)), ...change, key }));
  }

  protected formatTime(seconds: number): string {
    const s = Math.max(0, Math.floor(seconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h ? `${h}:${pad(m)}:${pad(s % 60)}` : `${m}:${pad(s % 60)}`;
  }

  private saveFor(key: string) {
    const el = this.video()?.nativeElement;
    if (!key || !el || el.readyState < 1 || !Number.isFinite(el.duration) || el.duration <= 0) return;

    if (el.ended || el.currentTime >= el.duration * 0.95) this.progress.markWatched(key, el.duration);
    else if (el.currentTime > RESUME_MIN) this.progress.save(key, el.currentTime, el.duration);
  }

  /* --------- Video events --------- */

  protected onLoadedMetadata() {
    const el = this.video()?.nativeElement;
    if (!el) return;

    const key = this.key();
    const saved = this.progress.get(key);
    const resume = !!saved && !saved.watched && saved.position > RESUME_MIN && saved.position < el.duration - 15;

    if (resume) el.currentTime = saved.position;
    this.patch({ error: null, resumedAt: resume ? saved.position : null });

    if (resume) setTimeout(() => { if (this.key() === key) this.patch({ resumedAt: null }); }, 7000);
  }

  protected onTimeUpdate() {
    if (Date.now() - this.lastSave < SAVE_EVERY_MS) return;
    this.lastSave = Date.now();
    this.saveFor(this.key());
    this.tick.update(n => n + 1);
  }

  protected onPause() {
    this.saveFor(this.key());
    this.tick.update(n => n + 1);
  }

  protected onEnded() {
    this.saveFor(this.key());
    this.tick.update(n => n + 1);

    if (this.next() && this.autoNext()) this.startCountdown();
    else this.patch({ ended: true });
  }

  protected onError() {
    const code = this.video()?.nativeElement.error?.code ?? 4;
    this.buffering.set(false);
    this.patch({ error: ERROR_TEXT[code] ?? ERROR_TEXT[4] });
  }

  protected retryPlayback() {
    const el = this.video()?.nativeElement;
    this.patch({ error: null });
    el?.load();
    void el?.play().catch(() => {});
  }

  /* --------- Actions --------- */

  protected startOver() {
    const el = this.video()?.nativeElement;
    if (el) el.currentTime = 0;
    this.patch({ resumedAt: null });
  }

  protected watchAgain() {
    const el = this.video()?.nativeElement;
    if (!el) return;
    this.patch({ ended: false });
    el.currentTime = 0;
    void el.play().catch(() => {});
  }

  protected goNext() {
    const m = this.media();
    const n = this.next();
    if (m && n) this.router.navigate(['/watch', 'tv-series', m.id], { queryParams: { episode: n.id } });
  }

  private startCountdown() {
    this.clearTimer();
    this.patch({ countdown: NEXT_DELAY, ended: false });

    this.timer = setInterval(() => {
      const left = (this.ui().countdown ?? 0) - 1;
      if (left <= 0) { this.clearTimer(); this.goNext(); }
      else this.patch({ countdown: left });
    }, 1000);
  }

  private clearTimer() {
    clearInterval(this.timer);
    this.timer = undefined;
  }

  protected cancelCountdown() {
    this.clearTimer();
    this.patch({ countdown: null, ended: true });
  }

  protected toggleAutoNext() {
    this.autoNext.update(v => !v);
    this.progress.setAutoNext(this.autoNext());
    if (!this.autoNext() && this.ui().countdown !== null) this.cancelCountdown();
  }

  /* --------- Full screen and keyboard --------- */

  protected toggleFullscreen() {
    if (document.fullscreenElement) { void document.exitFullscreen(); return; }
    void this.stage()?.nativeElement.requestFullscreen().catch(() => {});
  }

  protected onFullscreenChange() {
    this.fullscreen.set(!!document.fullscreenElement);
  }

  private seek(seconds: number) {
    const el = this.video()?.nativeElement;
    if (!el || !Number.isFinite(el.duration)) return;
    el.currentTime = Math.min(Math.max(0, el.currentTime + seconds), el.duration);
  }

  protected onKey(event: KeyboardEvent) {
    const el = this.video()?.nativeElement;
    if (!el || event.ctrlKey || event.metaKey || event.altKey) return;

    const target = event.target as HTMLElement | null;
    if (target?.closest('input, textarea, select, [contenteditable="true"]')) return;

    const onVideo = target === el;                 // the browser's own controls already handle most keys there
    const key = event.key.toLowerCase();

    switch (key) {
      case 'f': this.toggleFullscreen(); break;
      case 'j': this.seek(-10); break;
      case 'l': this.seek(10); break;
      case 'n': if (!this.next()) return; this.goNext(); break;
      default:
        if (onVideo || target?.closest('button, a')) return;       // do not fight native behavior or a focused button
        if (key === ' ' || key === 'k') { el.paused ? void el.play().catch(() => {}) : el.pause(); }
        else if (key === 'arrowleft') this.seek(-5);
        else if (key === 'arrowright') this.seek(5);
        else if (key === 'm') el.muted = !el.muted;
        else return;
    }
    event.preventDefault();
  }
}