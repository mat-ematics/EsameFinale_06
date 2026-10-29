import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class ProfileService extends ApiService {
  getMe() {
    return this.request<unknown>(['me'], 'GET');
  }
}