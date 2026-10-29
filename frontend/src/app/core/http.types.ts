import { Observable } from "rxjs";

export type HttpRequestType = "GET"|"POST"|"PUT"|"DELETE";

export interface HttpResponseInterface<T = unknown> {
    data: T | null;
    message: string | null;
    error: string | null;
}

export type HttpResponseObservable<T = unknown> = Observable<HttpResponseInterface<T>>;