import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

export interface Profile {
  name?: string;
  surname?: string;
  email?: string;
  birthdate?: string;
  gender?: string;
  [key: string]: unknown;
}

interface CreditData { credit: number }

@Injectable({ providedIn: 'root' })
export class ProfileService extends ApiService {
  getMe()                       { return this.request<Profile>(['me'], 'GET'); }
  updateMe(changes: object)     { return this.request(['me'], 'PUT', changes); }
  deleteMe()                    { return this.request(['me'], 'DELETE'); }

  getCredit()                   { return this.request<CreditData>(['me', 'credit'], 'GET'); }
  addCredit(credit: number)     { return this.request<CreditData>(['me', 'credit', 'add'], 'PUT', { credit }); }
  removeCredit(credit: number)  { return this.request<CreditData>(['me', 'credit', 'remove'], 'PUT', { credit }); }
}