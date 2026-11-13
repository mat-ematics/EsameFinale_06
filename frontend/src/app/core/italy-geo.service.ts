import { Injectable, inject, signal } from '@angular/core';
import { LookupService, ProvinceItem, RegionItem } from './lookup.service';

@Injectable({ providedIn: 'root' })
export class ItalyGeoService {
  private readonly lookups = inject(LookupService);

  readonly regions = signal<RegionItem[]>([]);
  readonly provinces = signal<ProvinceItem[]>([]);
  private requested = false;

  /** Regions (20) and provinces (107) are small: loaded once for the whole app */
  ensureLoaded() {
    if (this.requested) return;
    this.requested = true;

    this.lookups.regions().subscribe({
      next: regions => this.regions.set(regions),
      error: () => (this.requested = false),
    });
    this.lookups.provinces().subscribe({
      next: provinces => this.provinces.set(provinces),
      error: () => (this.requested = false),
    });
  }
}