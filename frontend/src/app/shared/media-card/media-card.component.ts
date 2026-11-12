import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';
import { NgOptimizedImage } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MediaSummary, mediaLink } from '../../models/media';

@Component({
  selector: 'app-media-card',
  imports: [RouterLink, NgOptimizedImage],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './media-card.component.html',
  styleUrl: './media-card.component.scss',
})
export class MediaCardComponent {
  readonly item = input.required<MediaSummary>();
  /** Only for the images that are visible on first load */
  readonly priority = input(false);
  protected readonly link = computed(() => mediaLink(this.item()));
}