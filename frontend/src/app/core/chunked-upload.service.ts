import { HttpClient, HttpErrorResponse, HttpEventType, HttpHeaders, HttpParams, HttpRequest, HttpResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { EMPTY, Observable, catchError, concat, expand, filter, map, of, retry, switchMap, tap, throwError, timer } from 'rxjs';
import { ApiService } from './api.service';
import { HttpResponseInterface } from './http.types';

interface Session {
  id: string;
  chunk_size: number;
  received: number;
  complete: boolean;
}

type Step = { kind: 'bytes'; offset: number; loaded: number } | { kind: 'sent'; received: number };

export type ChunkedUploadEvent =
  | { kind: 'started'; id: string; resumed: boolean }
  | { kind: 'progress'; percent: number }
  | { kind: 'done'; id: string };

const PREFIX = 'upload:';

/** Server errors and dropped connections are worth another try; a 4xx answer is not */
const retryable = (error: unknown) => error instanceof HttpErrorResponse && (error.status === 0 || error.status >= 500);

@Injectable({ providedIn: 'root' })
export class ChunkedUploadService extends ApiService {
  private readonly client = inject(HttpClient);

  /**
   * Uploads a file in chunks, one after the other. A file that was partly uploaded before
   * (same name, size and date) resumes from where it stopped.
   */
  upload(file: File): Observable<ChunkedUploadEvent> {
    return this.open(file).pipe(
      switchMap(({ session, resumed }) => {
        const started: ChunkedUploadEvent = { kind: 'started', id: session.id, resumed };
        if (session.complete) return of<ChunkedUploadEvent[]>(started, { kind: 'done', id: session.id });

        const send = (offset: number) => this.sendChunk(session.id, file.slice(offset, offset + session.chunk_size), offset);

        const chunks$ = send(session.received).pipe(
          // Each confirmed chunk triggers the next one; the stream ends after the last
          expand(step => (step.kind === 'sent' && step.received < file.size ? send(step.received) : EMPTY)),
          map((step): ChunkedUploadEvent => {
            if (step.kind === 'bytes') {
              return { kind: 'progress', percent: Math.min(99, Math.floor((100 * (step.offset + step.loaded)) / file.size)) };
            }
            return step.received >= file.size
              ? { kind: 'done', id: session.id }
              : { kind: 'progress', percent: Math.floor((100 * step.received) / file.size) };
          }),
          tap(event => { if (event.kind === 'done') this.forget(session.id); }),
        );

        return concat(of(started), chunks$);
      }),
    );
  }

  /** Cancels an upload on the server and forgets it */
  discard(id: string): Observable<unknown> {
    this.forget(id);
    return this.request(['uploads', id], 'DELETE');
  }

  /* --------- Starting or resuming --------- */

  private open(file: File): Observable<{ session: Session; resumed: boolean }> {
    const key = this.key(file);
    const saved = this.recall(key);

    const fresh = () =>
      this.request<Session>(['uploads'], 'POST', { filename: file.name, size: file.size }).pipe(
        map(res => {
          this.remember(key, res.data!.id);
          return { session: res.data!, resumed: false };
        }),
      );

    if (!saved) return fresh();

    return this.request<Session>(['uploads', saved], 'GET').pipe(
      map(res => ({ session: res.data!, resumed: res.data!.received > 0 })),
      catchError(() => fresh()),                    // it expired or was deleted: start again
    );
  }

  /* --------- One chunk --------- */

  private sendChunk(id: string, body: Blob, offset: number): Observable<Step> {
    const request = new HttpRequest('PUT', this.getURL(['uploads', id]), body, {
      reportProgress: true,
      params: new HttpParams().set('offset', offset),
      headers: new HttpHeaders({ 'Content-Type': 'application/octet-stream' }),
    });

    return this.client.request<HttpResponseInterface<{ received: number }>>(request).pipe(
      filter(event => event.type === HttpEventType.UploadProgress || event.type === HttpEventType.Response),
      map((event): Step => event.type === HttpEventType.UploadProgress
        ? { kind: 'bytes', offset, loaded: event.loaded }
        : { kind: 'sent', received: (event as HttpResponse<HttpResponseInterface<{ received: number }>>).body!.data!.received }),
      retry({ count: 3, delay: (error, attempt) => (retryable(error) ? timer(1000 * attempt) : throwError(() => error)) }),
      // The answer to a chunk was lost, but the server did store it: carry on from the server's position
      catchError(error => {
        const received = error instanceof HttpErrorResponse && error.status === 409 ? error.error?.data?.received : undefined;
        return typeof received === 'number' ? of<Step>({ kind: 'sent', received }) : throwError(() => error);
      }),
    );
  }

  /* --------- Remembering an unfinished upload, so a reload can resume it --------- */

  private key(file: File): string {
    return `${PREFIX}${file.name}:${file.size}:${file.lastModified}`;
  }

  private remember(key: string, id: string) {
    try { localStorage.setItem(key, id); } catch { /* storage unavailable: no resume, still works */ }
  }

  private recall(key: string): string | null {
    try { return localStorage.getItem(key); } catch { return null; }
  }

  private forget(id: string) {
    try {
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key?.startsWith(PREFIX) && localStorage.getItem(key) === id) localStorage.removeItem(key);
      }
    } catch { /* ignore */ }
  }
}