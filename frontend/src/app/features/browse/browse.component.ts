import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { HeroCarouselComponent } from '../../shared/hero-carousel/hero-carousel.component';
import { MediaRowComponent } from '../../shared/media-row/media-row.component';
import { MediaCatalogService } from '../../core/media-catalog.service';
import { AddTarget, Crumb, MediaSummary, typeCrumb } from '../../models/media';

@Component({
  selector: 'app-browse',
  imports: [HeroCarouselComponent, MediaRowComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './browse.component.html',
})
export class BrowseComponent {
  private readonly catalog = inject(MediaCatalogService);

  protected readonly featured = toSignal(this.catalog.featured(), { initialValue: [] as MediaSummary[] });
  protected readonly films = toSignal(this.catalog.latest('film'), { initialValue: [] as MediaSummary[] });
  protected readonly series = toSignal(this.catalog.latest('tv-series'), { initialValue: [] as MediaSummary[] });

  protected readonly filmsCrumbs: Crumb[] = [typeCrumb('film'), { label: 'New releases' }];
  protected readonly seriesCrumbs: Crumb[] = [typeCrumb('tv-series'), { label: 'New releases' }];
  protected readonly filmsAdd: AddTarget = { type: 'film' };
  protected readonly seriesAdd: AddTarget = { type: 'tv-series' };
}