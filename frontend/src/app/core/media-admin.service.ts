import { HttpClient, HttpEventType, HttpRequest, HttpResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, filter, map } from 'rxjs';
import { ApiService } from './api.service';
import { HttpResponseInterface } from './http.types';
import { ApiMedia, ApiEpisode } from '../models/media-api';
import { MediaType, mediaSegment } from '../models/media';

export type UploadEvent<T> = { kind: 'progress'; percent: number } | { kind: 'done'; data: T };

@Injectable({ providedIn: 'root' })
export class MediaAdminService extends ApiService {
  private readonly client = inject(HttpClient);

  /* --------- Films and TV series (multipart: details + files) --------- */

  saveTitle(type: MediaType, id: number | null, form: FormData): Observable<UploadEvent<ApiMedia>> {
    return this.upload<ApiMedia>(id ? [mediaSegment(type), id] : [mediaSegment(type)], form, !!id);
  }

  saveEpisode(seriesId: number, id: number | null, form: FormData): Observable<UploadEvent<ApiEpisode>> {
    const base = ['tv-series', seriesId, 'episodes'];
    return this.upload<ApiEpisode>(id ? [...base, id] : base, form, !!id);
  }

  /** Multipart bodies need POST: PHP does not parse them on PUT, so an update is a POST with _method=PUT */
  private upload<T>(resource: (string | number)[], form: FormData, update: boolean): Observable<UploadEvent<T>> {
    if (update) form.append('_method', 'PUT');

    const request = new HttpRequest('POST', this.getURL(resource), form, { reportProgress: true });

    return this.client.request<HttpResponseInterface<T>>(request).pipe(
      filter(event => event.type === HttpEventType.UploadProgress || event.type === HttpEventType.Response),
      map((event): UploadEvent<T> => {
        if (event.type === HttpEventType.UploadProgress) {
          return { kind: 'progress', percent: event.total ? Math.round((100 * event.loaded) / event.total) : 0 };
        }
        return { kind: 'done', data: (event as HttpResponse<HttpResponseInterface<T>>).body!.data as T };
      }),
    );
  }

  /* --------- Delete, restore, delete permanently --------- */

  softDelete(type: MediaType, id: number)  { return this.request([mediaSegment(type), id], 'DELETE'); }
  restore(type: MediaType, id: number)     { return this.request([mediaSegment(type), id, 'restore'], 'PUT', {}); }
  forceDelete(type: MediaType, id: number) { return this.request([mediaSegment(type), id, 'force'], 'DELETE'); }

  deleteEpisode(seriesId: number, id: number)  { return this.request(['tv-series', seriesId, 'episodes', id], 'DELETE'); }
  restoreEpisode(seriesId: number, id: number) { return this.request(['tv-series', seriesId, 'episodes', id, 'restore'], 'PUT', {}); }
}