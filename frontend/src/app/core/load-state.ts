import { Observable, catchError, map, of } from 'rxjs';
import { apiErrorMessage } from './api.service';

/** A request result as a value: an error then shows a message instead of blanking the page */
export type Loaded<T> = { data: T; error: null } | { data: null; error: string };

export const loaded = <T>(source$: Observable<T>): Observable<Loaded<T>> =>
  source$.pipe(
    map((data): Loaded<T> => ({ data, error: null })),
    catchError(err => of<Loaded<T>>({ data: null, error: apiErrorMessage(err) })),
  );