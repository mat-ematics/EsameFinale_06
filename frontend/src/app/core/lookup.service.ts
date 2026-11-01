import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from './api.service';

export interface LookupItem {
  id: number | string;
  name: string;
}

@Injectable({ providedIn: 'root' })
export class LookupService extends ApiService {
  searchCountries(term: string): Observable<LookupItem[]> {
    return this.search('countries', term);
  }

  searchMunicipalities(term: string): Observable<LookupItem[]> {
    return this.search('italian-municipalities', term);
  }

  private search(resource: string, term: string): Observable<LookupItem[]> {
    return this.request<LookupItem[]>([resource], 'GET', null, { search: term, limit: 10 })
      .pipe(map(res => res.data ?? []));
  }
}