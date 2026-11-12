import { ChangeDetectionStrategy, Component, computed, effect, input, signal } from '@angular/core';
import { NgOptimizedImage } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MediaSummary, mediaLink, mediaTrackKey } from '../../models/media';

@Component({
  selector: 'app-hero-carousel',
  imports: [NgOptimizedImage, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './hero-carousel.component.html',
  styleUrl: './hero-carousel.component.scss',
})
export class HeroCarouselComponent {
  readonly slides = input.required<MediaSummary[]>();
  readonly intervalMs = input(6000);

  protected readonly link = mediaLink;
  protected readonly key = mediaTrackKey;
  protected readonly paused = signal(false);

  private readonly requested = signal(0);
  private readonly reducedMotion =
    typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /** The slide actually shown (stays valid if the list shrinks) */
  protected readonly index = computed(() =>
    Math.min(this.requested(), Math.max(this.slides().length - 1, 0)));

  private readonly autoplay = computed(() =>
    this.slides().length > 1 && !this.paused() && !this.reducedMotion);

  constructor() {
    effect(onCleanup => {
      this.index();                                   // a manual change restarts the countdown
      if (!this.autoplay()) return;

      const id = setTimeout(() => this.next(), this.intervalMs());
      onCleanup(() => clearTimeout(id));
    });
  }

  protected go(i: number) { this.requested.set(i); }

  protected next() {
    const count = this.slides().length;
    if (count) this.requested.set((this.index() + 1) % count);
  }

  protected prev() {
    const count = this.slides().length;
    if (count) this.requested.set((this.index() - 1 + count) % count);
  }
}