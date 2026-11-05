import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from './api.service';

export interface LookupItem { id: number | string; name: string; label?: string }
export interface CountryItem extends LookupItem { iso: string }
export type RegionItem = LookupItem;
export interface ProvinceItem extends LookupItem { region_id: number; code: string }
export interface MunicipalityItem extends LookupItem {
  cap: string;
  province_id: number | null;
  region_id: number | null;
}

@Injectable({ providedIn: 'root' })
export class LookupService extends ApiService {

  searchCountries(term: string): Observable<CountryItem[]> {
    return this.list<CountryItem>('countries', { search: term, limit: 10 });
  }

  searchMunicipalities(term: string, provinceId?: string): Observable<MunicipalityItem[]> {
    return this.list<MunicipalityItem>('italian-municipalities', {
      search: term,
      limit: 10,
      ...(provinceId ? { province_id: provinceId } : {}),
    });
  }

  municipalitiesByCap(cap: string): Observable<MunicipalityItem[]> {
    return this.list<MunicipalityItem>('italian-municipalities', { cap });
  }

  regions(): Observable<RegionItem[]> {
    return this.list<RegionItem>('italian-regions', { compact: 1 });
  }

  provinces(): Observable<ProvinceItem[]> {
    return this.list<ProvinceItem>('italian-provinces', { compact: 1 });
  }

  private list<T>(resource: string, params: Record<string, string | number>): Observable<T[]> {
    return this.request<T[]>([resource], 'GET', null, params).pipe(
      map((res): T[] => res.data ?? [])
    );
  }
}