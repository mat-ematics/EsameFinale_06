import { ChangeDetectionStrategy, Component, ElementRef, computed, effect, input, signal, viewChild } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MediaCardComponent } from '../media-card/media-card.component';
import { MediaSummary, mediaTrackKey } from '../../models/media';

@Component({
  selector: 'app-media-row',
  imports: [RouterLink, MediaCardComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './media-row.component.html',
  styleUrl: './media-row.component.scss',
})
export class MediaRowComponent {
  readonly items = input.required<MediaSummary[]>();
  readonly heading = input.required<string>();
  readonly subheading = input<string>();
  readonly headingLink = input<string | any[]>();
  /** Fraction of the visible width scrolled by each arrow click */
  readonly step = input(0.8);

  private readonly viewport = viewChild<ElementRef<HTMLElement>>('viewport');
  private readonly track = viewChild<ElementRef<HTMLElement>>('track');

  protected readonly atStart = signal(true);
  protected readonly atEnd = signal(true);
  /** Arrows appear only when there is something to scroll */
  protected readonly scrollable = computed(() => !(this.atStart() && this.atEnd()));
  protected readonly key = mediaTrackKey;

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