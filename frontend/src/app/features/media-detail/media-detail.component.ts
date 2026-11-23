import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { finalize, Observable, of, startWith, switchMap } from 'rxjs';
import { MediaRowComponent } from '../../shared/media-row/media-row.component';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { SessionService } from '../../core/session.service';
import { Crumb, Episode, MediaSummary, genreCrumb, mediaSectionLink, mediaSegment, typeCrumb } from '../../models/media';
import { MediaType } from '../../models/media';
import { Router } from '@angular/router';
import { MediaAdminService } from '../../core/media-admin.service.js';
import { apiErrorMessage } from '../../core/api.service.js';
import { EpisodeFormComponent } from './episode-form/episode-form.component';
import { ModalComponent } from '../../shared/modal/modal.component';
import { loaded } from '../../core/load-state.js';

@Component({
  selector: 'app-media-detail',
  imports: [MediaRowComponent, EpisodeFormComponent, ModalComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './media-detail.component.html',
  styleUrl: './media-detail.component.scss',
})
export class MediaDetailComponent {
  // Bound by the router: route data and the :id path param
  readonly type = input.required<MediaType>();
  readonly id = input.required<string>();

  private readonly catalog = inject(MediaCatalogService);
  protected readonly session = inject(SessionService);

  private readonly router = inject(Router);
    private readonly admin = inject(MediaAdminService);

    /** Bumped after every change, so the page loads again */
    private readonly reload = signal(0);

    private readonly state = toSignal(
    toObservable(computed(() => ({ type: this.type(), id: Number(this.id()), reload: this.reload() })))
        .pipe(switchMap(({ type, id }) => loaded(this.catalog.detail(type, id)).pipe(startWith(undefined)))),
    );

    /** undefined = loading, null = not found or failed */
    protected readonly media = computed(() => (this.state() === undefined ? undefined : this.state()!.data));
    protected readonly loadError = computed(() => this.state()?.error ?? null);

  protected readonly actionError = signal<string | null>(null);
  protected readonly busy = signal(false);
  protected readonly confirmingPermanent = signal(false);

    protected readonly canPlay = computed(() => {
        const m = this.media();
        if (!m) return false;
        return m.type === 'film' ? !!m.videoUrl : !!m.episodes?.some(e => e.videoUrl && !e.deleted);
    });

    /** Run an admin action: block the buttons meanwhile and show the server's message on failure */
    private run(action: () => Observable<unknown>, done: () => void) {
        this.actionError.set(null);
        this.busy.set(true);
        action().pipe(finalize(() => this.busy.set(false))).subscribe({
            next: done,
            error: err => this.actionError.set(apiErrorMessage(err)),
        });
    }


  protected readonly related = toSignal(
    toObservable(this.media).pipe(switchMap(m => (m ? this.catalog.related(m) : of([] as MediaSummary[])))),
    { initialValue: [] as MediaSummary[] },
  );

  /** Small facts shown under the title */
  protected readonly facts = computed(() => {
    const m = this.media();
    if (!m) return [];
    if (m.type === 'film') {
      return [m.durationMinutes ? `${m.durationMinutes} min` : '', String(m.year)].filter(Boolean);
    }
    return [
      m.seasonCount ? `${m.seasonCount} season${m.seasonCount > 1 ? 's' : ''}` : '',
      m.endYear && m.endYear !== m.year ? `${m.year}–${m.endYear}` : String(m.year),
    ].filter(Boolean);
  });

  /** Label/value rows: missing values are not shown */
  protected readonly info = computed(() => {
    const m = this.media();
    if (!m) return [];
    return [
      { label: 'Director', value: m.directors },
      { label: 'Cast', value: m.actors },
      { label: 'Genre', value: (m.categoryLabels ?? m.categories).join(', ') },
      { label: 'Language', value: m.languages?.join(', ') },
      { label: 'Subtitles', value: m.subtitles?.join(', ') },
    ].filter(row => !!row.value);
  });

  protected readonly relatedCrumbs = computed<Crumb[]>(() => {
    const m = this.media();
    const name = m?.categories[0];
    if (!m || !name) return m ? [typeCrumb(m.type)] : [];
    return [typeCrumb(m.type), genreCrumb(m.type, { name, label: m.categoryLabels?.[0] ?? name })];
  });

  /* --------- Seasons (the choice resets when another title opens) --------- */
  private readonly seasonChoice = signal({ id: '', season: 1 });
  protected readonly season = computed(() =>
    this.seasonChoice().id === this.id() ? this.seasonChoice().season : 1);
  protected readonly seasons = computed(() =>
    Array.from({ length: this.media()?.seasonCount ?? 0 }, (_, i) => i + 1));
  protected readonly episodes = computed(() =>
    this.media()?.episodes?.filter(e => e.season === this.season()) ?? []);

  protected chooseSeason(season: number) {
    this.seasonChoice.set({ id: this.id(), season });
  }

  /* --------- Actions (interfaces ready: connect them to the API later) --------- */
  protected readonly confirmingDelete = signal(false);

    protected play(episode?: Episode) {
        const m = this.media();
        if (!m) return;

        if (m.type === 'film') {
            this.router.navigate(['/watch', 'film', m.id]);
            return;
        }
        // Without an episode, the player continues the last one watched (or starts the first)
        this.router.navigate(['/watch', 'tv-series', m.id], episode ? { queryParams: { episode: episode.id } } : {});
    }

    protected edit() {
        const m = this.media();
        if (m) this.router.navigate(['/admin', mediaSegment(m.type), m.id, 'edit']);
    }

    protected remove() {
        const m = this.media();
        if (!m) return;
        this.run(() => this.admin.softDelete(m.type, m.id), () => { this.confirmingDelete.set(false); this.reload.update(n => n + 1); });
    }

    protected restore() {
        const m = this.media();
        if (m) this.run(() => this.admin.restore(m.type, m.id), () => this.reload.update(n => n + 1));
    }

    protected removePermanently() {
        const m = this.media();
        if (!m) return;
        this.run(() => this.admin.forceDelete(m.type, m.id), () => {
            this.confirmingPermanent.set(false);
            this.router.navigate(mediaSectionLink(m.type));
        });
    }

    /* --------- Episodes --------- */
    protected readonly episodeFormOpen = signal(false);
    protected readonly episodeBeingEdited = signal<Episode | null>(null);

    /** Next free number of the selected season, and the series' start year */
    protected readonly suggestion = computed(() => {
    const season = this.season();
    const numbers = (this.media()?.episodes ?? []).filter(e => e.season === season).map(e => e.number);
    return { season, number: (numbers.length ? Math.max(...numbers) : 0) + 1, year: (this.media()?.year ?? new Date().getFullYear()) + season - 1 };
    });

    protected openEpisodeForm(episode: Episode | null) {
    this.episodeBeingEdited.set(episode);
    this.episodeFormOpen.set(true);
    }

    protected onEpisodeSaved() {
    this.episodeFormOpen.set(false);
    this.reload.update(n => n + 1);
    }

    protected removeEpisode(episode: Episode) {
    const m = this.media();
    if (m) this.run(() => this.admin.deleteEpisode(m.id, episode.id), () => this.reload.update(n => n + 1));
    }

    protected restoreEpisode(episode: Episode) {
        const m = this.media();
        if (m) this.run(() => this.admin.restoreEpisode(m.id, episode.id), () => this.reload.update(n => n + 1));
    }
}