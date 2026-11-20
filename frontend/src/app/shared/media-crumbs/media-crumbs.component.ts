import { Component, computed, inject, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AddTarget, Crumb, mediaAddLink } from '../../models/media.js';
import { SessionService } from '../../core/session.service.js';

@Component({
  imports: [RouterLink],
  selector: 'app-media-crumbs',
  styleUrl: './media-crumbs.component.scss',
  templateUrl: './media-crumbs.component.html',
})
export class MediaCrumbsComponent {
  readonly crumbs = input.required<Crumb[]>();
  /** Where "Add" leads. Admins only (UI: the server still enforces it). Empty = no button */
  readonly add = input<AddTarget | null>(null);

  protected readonly session = inject(SessionService);

  protected readonly addLink = computed(() => {
    const target = this.add();
    return target ? mediaAddLink(target) : null;
  });

  protected readonly addLabel = computed(() => {
    const target = this.add();
    if (!target) return '';
    const what = target.type === 'film' ? 'film' : "TV series";
    return target.genre ? `Add ${what} in ${target.genre}` : `Add ${what}`;
  })
}
