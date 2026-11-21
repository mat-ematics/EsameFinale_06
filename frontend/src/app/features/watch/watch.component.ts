import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { switchMap } from 'rxjs';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { MediaType, mediaLink } from '../../models/media';

@Component({
  selector: 'app-watch',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: "",
})
export class WatchComponent {
  // Bound by the router: route data and path params
  readonly type = input.required<MediaType>();
  readonly id = input.required<string>();
  readonly episodeId = input<string>();

  private readonly catalog = inject(MediaCatalogService);

  protected readonly media = toSignal(
    toObservable(computed(() => ({ type: this.type(), id: Number(this.id()) })))
      .pipe(switchMap(({ type, id }) => this.catalog.detail(type, id))),
  );

  private readonly episodes = computed(() => (this.media()?.episodes ?? []).filter(e => !e.deleted && e.videoUrl));
  protected readonly episode = computed(() => this.episodes().find(e => e.id === Number(this.episodeId())) ?? null);
  protected readonly next = computed(() => {
    const list = this.episodes();
    const current = list.findIndex(e => e.id === this.episode()?.id);
    return current >= 0 ? list[current + 1] ?? null : null;
  });

  protected readonly source = computed(() => (this.type() === 'film' ? this.media()?.videoUrl : this.episode()?.videoUrl));
  protected readonly poster = computed(() => this.media()?.bannerUrl ?? this.media()?.image ?? null);
  protected readonly title = computed(() => {
    const m = this.media();
    const e = this.episode();
    return e ? `${m?.title}: S${e.season} E${e.number} · ${e.title}` : (m?.title ?? '');
  });
  protected readonly back = computed(() => (this.media() ? mediaLink(this.media()!) : ['/browse']));
}