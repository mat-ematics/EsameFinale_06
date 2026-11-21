import { ChangeDetectionStrategy, Component, ElementRef, computed, effect, inject, input, signal, viewChild } from '@angular/core';
import { MediaCardComponent } from '../media-card/media-card.component';
import { AddTarget, Crumb, MediaSummary, mediaTrackKey } from '../../models/media';
import { SessionService } from '../../core/session.service.js';
import { MediaCrumbsComponent } from '../media-crumbs/media-crumbs.component';

@Component({
  selector: 'app-media-row',
  imports: [ MediaCardComponent, MediaCrumbsComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './media-row.component.html',
  styleUrl: './media-row.component.scss',
})
export class MediaRowComponent {
  readonly items = input.required<MediaSummary[]>();
  readonly crumbs = input.required<Crumb[]>();
  readonly add = input<AddTarget | null>(null);
  readonly headingLink = input<string | any[]>();
  /** Fraction of the visible width scrolled by each arrow click */
  readonly step = input(0.8);

  /** Number of cards at the start that load with priority (only for a row near the top) */
    readonly priorityCount = input(0);

  private readonly session = inject(SessionService);
  private readonly viewport = viewChild<ElementRef<HTMLElement>>('viewport');
  private readonly track = viewChild<ElementRef<HTMLElement>>('track');

  protected readonly atStart = signal(true);
  protected readonly atEnd = signal(true);
  /** Arrows appear only when there is something to scroll */
  protected readonly scrollable = computed(() => !(this.atStart() && this.atEnd()));
  protected readonly key = mediaTrackKey;

  protected readonly label = computed(() => this.crumbs().map(c => c.label).join(' - '));
  /** Empty rows stay visible for admins: they are the place to add the first title */
  protected readonly visible = computed(() => 
    this.items().length > 0 || (!!this.add() && this.session.isAdmin()));

  constructor() {
    // Re-measure when the row or its content changes size (resize, items loaded)
    effect(onCleanup => {
      const viewport = this.viewport()?.nativeElement;
      const track = this.track()?.nativeElement;
      if (!viewport || !track) return;

      const observer = new ResizeObserver(() => this.update());
      observer.observe(viewport);
      observer.observe(track);
      onCleanup(() => observer.disconnect());
    });
  }

  protected update() {
    const el = this.viewport()?.nativeElement;
    if (!el) return;
    this.atStart.set(el.scrollLeft <= 1);
    this.atEnd.set(el.scrollLeft + el.clientWidth >= el.scrollWidth - 1);
  }

  protected scrollBy(direction: 1 | -1) {
    const el = this.viewport()?.nativeElement;
    if (!el) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.scrollBy({ left: direction * el.clientWidth * this.step(), behavior: reduced ? 'auto' : 'smooth' });
  }
}