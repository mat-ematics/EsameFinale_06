import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { of, switchMap } from 'rxjs';
import { MediaRowComponent } from '../../shared/media-row/media-row.component';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { SessionService } from '../../core/session.service';
import { Episode, MEDIA_LABEL, MediaSummary, mediaSectionLink } from '../../models/media';
import { MediaType } from '../../models/media';

@Component({
  selector: 'app-media-detail',
  imports: [MediaRowComponent],
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

  /** undefined = loading, null = not found */
  protected readonly media = toSignal(
    toObservable(computed(() => ({ type: this.type(), id: Number(this.id()) })))
      .pipe(switchMap(({ type, id }) => this.catalog.detail(type, id))),
  );

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
      { label: 'Genre', value: m.categories.join(', ') },
      { label: 'Language', value: m.languages?.join(', ') },
      { label: 'Subtitles', value: m.subtitles?.join(', ') },
    ].filter(row => !!row.value);
  });

  protected readonly relatedHeading = computed(() => MEDIA_LABEL[this.type()]);
  protected readonly relatedLink = computed(() => mediaSectionLink(this.type()));

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

  protected play() { /* TODO: open the player */ }
  protected edit() { /* TODO: open the edit form */ }
  protected remove() {
    // TODO: call the delete endpoint, then go back to the list
    this.confirmingDelete.set(false);
  }

  protected playEpisode(episode: Episode) { /* TODO */ }
  protected editEpisode(episode: Episode) { /* TODO */ }
  protected removeEpisode(episode: Episode) { /* TODO */ }
}