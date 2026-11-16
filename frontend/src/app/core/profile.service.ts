import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

export interface Profile {
  name?: string;
  surname?: string;
  email?: string;
  birthdate?: string;
  gender?: string;
  tax_code?: string | null;
  residence_iso?: string | null;      // decides whether the tax code is required
  [key: string]: unknown;
}

import { MunicipalityItem } from './lookup.service';

export interface AddressData {
  country: { id: number; name: string; iso: string } | null;
  italian_municipality: MunicipalityItem | null;
  cap: string | null;
  street_address: string | null;
  house_number: string | null;
  locality: string | null;
  additional_info: string | null;
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

  getAddress()                    { return this.request<AddressData>(['me', 'address'], 'GET'); }
  updateAddress(body: object)     { return this.request<AddressData>(['me', 'address'], 'PUT', body); }
}