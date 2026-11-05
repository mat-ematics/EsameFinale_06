import { Component, computed, inject, signal, viewChild } from '@angular/core';
import { NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Subject, catchError, distinctUntilChanged, finalize, of, switchMap } from 'rxjs';
import { AuthService } from '../../../core/auth.service';
import { apiErrorMessage } from '../../../core/api.service';
import { CountryItem, LookupItem, LookupService, MunicipalityItem, ProvinceItem, RegionItem } from '../../../core/lookup.service';
import { PasswordFieldComponent } from '../../../shared/password-field/password-field.component';
import { TypeaheadComponent } from '../../../shared/typeahead/typeahead.component';
import { FieldName, ITALY_ISO, RegistrationFormData } from './types/registration_form_data';
import { ErrorCodes } from './validation/error_codes';
import { POSTAL_CODE_FORMATS } from './validation/postal_codes';
import { validateRegistrationField, validateRegistrationForm } from './validation/form_validator';
import { FormFieldsValidationResult } from './validation/validation_types';
import { getFieldErrorMessage } from './ui/form_errors';
import { decodeTaxCode, generateTaxCode } from './validation/tax_code';

/** Fields whose blur may complete or refresh the tax code */
const TAX_CODE_SOURCES: FieldName[] = ['name', 'surname', 'birthdate', 'gender'];

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, NgClass, RouterLink, PasswordFieldComponent, TypeaheadComponent],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  private fb = inject(FormBuilder).nonNullable;
  private auth = inject(AuthService);
  private router = inject(Router);
  private lookups = inject(LookupService);

  readonly form = this.fb.group({
    name: [''], surname: [''], email: [''], username: [''], password: [''], confirm_password: [''],
    birthdate: [''], gender: [''],
    country_id: [''],
    country_iso: [''],                                   // UI only
    region_id: [{ value: '', disabled: true }],          // UI only
    province_id: [{ value: '', disabled: true }],        // UI only
    birth_municipality_id: [{ value: '', disabled: true }],   // UI only
    birth_catastal_code: [''],                                // UI only
    tax_code: [''],
    italian_municipality_id: [{ value: '', disabled: true }],
    street_address: [''], house_number: [''], cap: [''],
    locality: [''], additional_info: [''],
    credit: [''], currency: ['EUR'],
  });

  readonly results = signal<Partial<FormFieldsValidationResult>>({});
  readonly serverErrors = signal<Record<string, string>>({});
  readonly formError = signal<string | null>(null);
  readonly submitting = signal(false);

  /* --------- Italian address state --------- */
  private countryIso = toSignal(this.form.controls.country_iso.valueChanges, { initialValue: '' });
  private regionId = toSignal(this.form.controls.region_id.valueChanges, { initialValue: '' });

  protected isItaly = computed(() => this.countryIso() === ITALY_ISO);
  protected regions = signal<RegionItem[]>([]);
  private allProvinces = signal<ProvinceItem[]>([]);
  protected provinces = computed(() =>
    this.allProvinces().filter(p => String(p.region_id) === this.regionId()));
  protected capMatches = signal<MunicipalityItem[]>([]);
  protected capPlaceholder = computed(() => POSTAL_CODE_FORMATS[this.countryIso()]?.example ?? 'Postal code');

  private selectedMunicipality = signal<MunicipalityItem | null>(null);
  private municipalityField = viewChild<TypeaheadComponent>('municipalityField');
  private listsRequested = false;
  private capLookup$ = new Subject<string>();

  // Arrow functions, so `this` stays bound when the typeahead calls them
  readonly searchCountries = (term: string) => this.lookups.searchCountries(term);
  readonly searchMunicipalities = (term: string) =>
    this.lookups.searchMunicipalities(term, this.form.controls.province_id.value || undefined);

  /* --------- TAX CODE STATE --------- */
  /** False once the user types the code by hand: autofill then never overwrites it */
  private taxCodeAuto = true;
  private birthField = viewChild<TypeaheadComponent>('birthMunicipalityField');

  // Birth municipality is not narrowed by the residence province
  readonly searchBirthMunicipalities = (term: string) => this.lookups.searchMunicipalities(term);

  constructor() {
    // A complete CAP (5 digits) finds its municipality: one match fills everything
    this.capLookup$.pipe(
      distinctUntilChanged(),
      switchMap(cap => /^\d{5}$/.test(cap)
        ? this.lookups.municipalitiesByCap(cap).pipe(catchError(() => of([] as MunicipalityItem[])))
        : of(null)),
      takeUntilDestroyed(),
    ).subscribe(items => this.onCapMatches(items));
  }

  private data(): RegistrationFormData {
    return this.form.getRawValue() as RegistrationFormData;
  }

  /* --------- Template helpers --------- */

  isInvalid(field: FieldName): boolean {
    return !!this.serverErrors()[field] || this.results()[field]?.isValid === false;
  }

  isValid(field: FieldName): boolean {
    return !this.isInvalid(field) && this.results()[field]?.isValid === true;
  }

  cls(field: FieldName) {
    return { 'is-valid': this.isValid(field), 'is-invalid': this.isInvalid(field) };
  }

  error(field: FieldName): string {
    const server = this.serverErrors()[field];
    if (server) return server;

    const result = this.results()[field];
    if (!result || result.isValid) return '';

    // The postal code message includes the format of the selected country
    if (field === 'cap' && result.errorCode === ErrorCodes.INVALID_CAP) {
      const example = POSTAL_CODE_FORMATS[this.countryIso()]?.example;
      return example ? `Enter a valid postal code (e.g. ${example})` : 'Enter a valid postal code';
    }
    return getFieldErrorMessage(field, result.errorCode);
  }

  /* --------- Country --------- */

  onCountryPicked(item: LookupItem | null) {
    this.form.controls.country_iso.setValue((item as CountryItem | null)?.iso ?? '');
    this.results.update(r => ({ ...r, cap: undefined, tax_code: undefined }));   // both rules just changed
    this.syncItalyFields();
  }

  /** Enables or resets the Italy-only fields depending on the chosen country */
  private syncItalyFields() {
    const c = this.form.controls;

    if (this.isItaly()) {
      this.ensureItalyLists();
      c.region_id.enable({ emitEvent: false });
      c.italian_municipality_id.enable({ emitEvent: false });
      c.birth_municipality_id.enable({ emitEvent: false });
      c.province_id[c.region_id.value ? 'enable' : 'disable']({ emitEvent: false });
      this.refreshTaxCode();               // the data may already be there
      return;
    }

    this.form.patchValue({
      region_id: '', province_id: '', italian_municipality_id: '',
      birth_municipality_id: '', birth_catastal_code: '',
    });
    c.region_id.disable({ emitEvent: false });
    c.province_id.disable({ emitEvent: false });
    c.italian_municipality_id.disable({ emitEvent: false });
    c.birth_municipality_id.disable({ emitEvent: false });
    if (this.taxCodeAuto) c.tax_code.setValue('');   // a generated code makes no sense here
    this.selectedMunicipality.set(null);
    this.capMatches.set([]);
    this.results.update(r => ({ ...r, italian_municipality_id: undefined }));
  }

  /** Regions and provinces are small: load them once, only for Italian addresses */
  private ensureItalyLists() {
    if (this.listsRequested) return;
    this.listsRequested = true;

    this.lookups.regions().subscribe({
      next: regions => this.regions.set(regions),
      error: () => (this.listsRequested = false),
    });
    this.lookups.provinces().subscribe({
      next: provinces => this.allProvinces.set(provinces),
      error: () => (this.listsRequested = false),
    });
  }

  /* --------- Region -> Province -> Municipality --------- */

  onRegionChange(regionId: string) {
    const c = this.form.controls;

    c.province_id.setValue('');                        // the province list changed
    c.province_id[regionId ? 'enable' : 'disable']({ emitEvent: false });

    const municipality = this.selectedMunicipality();
    if (municipality && String(municipality.region_id) !== regionId) this.clearMunicipality();
  }

  onProvinceChange(provinceId: string) {
    const municipality = this.selectedMunicipality();
    if (municipality && String(municipality.province_id) !== provinceId) this.clearMunicipality();
  }

  onMunicipalityPicked(item: LookupItem | null) {
    if (item) this.applyMunicipality(item as MunicipalityItem);
    else this.selectedMunicipality.set(null);
  }

  /** Municipality -> province, region and CAP */
  private applyMunicipality(m: MunicipalityItem) {
    this.selectedMunicipality.set(m);
    this.capMatches.set([]);

    this.form.patchValue({
      region_id: m.region_id ? String(m.region_id) : '',
      province_id: m.province_id ? String(m.province_id) : '',
      cap: m.cap,
    });
    this.syncItalyFields();                            // province becomes enabled
    this.results.update(r => ({
      ...r,
      cap: validateRegistrationField('cap', this.data()),
    }));
  }

  /** Selection made from code (the CAP lookup or a chip), not typed by the user */
  protected pickMunicipality(m: MunicipalityItem) {
    this.municipalityField()?.setSelection(m);
    this.applyMunicipality(m);
  }

  private clearMunicipality() {
    this.municipalityField()?.setSelection(null);
    this.form.controls.italian_municipality_id.setValue('');
    this.selectedMunicipality.set(null);
    this.capMatches.set([]);
  }

  /* --------- CAP -> Municipality --------- */

  onCapInput(value: string) {
    this.capLookup$.next(this.isItaly() ? value.trim() : '');
  }

  private onCapMatches(items: MunicipalityItem[] | null) {
    this.capMatches.set([]);
    // Never override a municipality the user already chose
    if (!items?.length || this.selectedMunicipality()) return;

    if (items.length === 1) this.pickMunicipality(items[0]);
    else this.capMatches.set(items);                   // several: let the user choose
  }

  /* --------- Tax code --------- */

  private revalidate(...fields: FieldName[]) {
    const data = this.data();
    this.results.update(r => {
      const next = { ...r } as Partial<FormFieldsValidationResult>;
      for (const field of fields) next[field] = validateRegistrationField(field, data);
      return next;
    });
  }

  private clearResult(field: FieldName) {
    this.results.update(r => ({ ...r, [field]: undefined }));
  }

  /** Forward: personal data -> tax code (only while the user is not typing it by hand) */
  private refreshTaxCode() {
    if (this.taxCodeAuto) {
      const d = this.data();
      const code = this.isItaly()
        ? generateTaxCode({
            name: d.name, surname: d.surname, birthdate: d.birthdate,
            gender: d.gender, birthPlaceCode: d.birth_catastal_code ?? '',
          })
        : null;

      // null clears a previously generated code that is no longer computable
      this.form.controls.tax_code.setValue(code ?? '');
      if (code) this.revalidate('tax_code'); else this.clearResult('tax_code');
    } else if (this.results().tax_code) {
      this.revalidate('tax_code');         // a shown result may have gone stale
    }
  }

  onBirthMunicipalityPicked(item: LookupItem | null) {
    this.form.controls.birth_catastal_code.setValue((item as MunicipalityItem | null)?.catastal_code ?? '');
    this.refreshTaxCode();
  }

  onTaxCodeInput(value: string) {
    const code = value.trim().toUpperCase();
    this.taxCodeAuto = code === '';        // typing pauses autofill, clearing resumes it
    if (this.isItaly() && code.length === 16) this.fillFromTaxCode(code);
  }

  /** Backward: tax code -> birth date, gender and birth municipality (empty fields only) */
  private fillFromTaxCode(code: string) {
    const decoded = decodeTaxCode(code);
    if (!decoded) return;

    const c = this.form.controls;
    const filled: FieldName[] = [];

    if (!c.birthdate.value) { c.birthdate.setValue(decoded.birthdate); filled.push('birthdate'); }
    if (!c.gender.value)    { c.gender.setValue(decoded.gender); filled.push('gender'); }
    if (filled.length) this.revalidate(...filled);

    if (!c.birth_municipality_id.value && !decoded.abroad) {
      this.lookups.municipalityByCatastalCode(decoded.birthPlaceCode).subscribe({
        next: items => {
          // The user may have picked one while the request was running
          if (items.length !== 1 || this.form.controls.birth_municipality_id.value) return;
          this.birthField()?.setSelection(items[0]);
          this.form.controls.birth_catastal_code.setValue(items[0].catastal_code);
        },
        error: () => {},                   // a convenience: failing silently is fine
      });
    }
  }

  /* --------- Events --------- */

  onBlur(field: FieldName) {
    const data = this.data();
    const next = { ...this.results(), [field]: validateRegistrationField(field, data) } as Partial<FormFieldsValidationResult>;

    if (field === 'country_id') {
      next.italian_municipality_id = validateRegistrationField('italian_municipality_id', data);
      if (this.results().cap) next.cap = validateRegistrationField('cap', data);
    }

    if (field === 'password' && this.results().confirm_password) {
      next.confirm_password = validateRegistrationField('confirm_password', data);
    }

    this.results.set(next);
    if (TAX_CODE_SOURCES.includes(field)) this.refreshTaxCode();
    this.serverErrors.update(({ [field]: _removed, ...rest }) => rest);
  }

  submit() {
    const data = this.data();
    const result = validateRegistrationForm(data);

    this.results.set(result.fields);
    this.serverErrors.set({});
    this.formError.set(null);
    if (!result.isValid) return;

    this.submitting.set(true);
    this.auth.register(this.toPayload(data))
      .pipe(finalize(() => this.submitting.set(false)))
      .subscribe({
        next: () => this.router.navigate([this.auth.isLoggedIn() ? '/browse' : '/login']),
        error: err => this.handleError(err),
      });
  }

  private toPayload(d: RegistrationFormData) {
    // UI-only fields never reach the server
    const { confirm_password, country_iso, region_id, province_id,
            birth_municipality_id, birth_catastal_code, tax_code,
            italian_municipality_id, locality, additional_info, ...rest } = d;
    return {
      ...rest,
      username: d.username.trim(),
      name: d.name.trim(),
      surname: d.surname.trim(),
      email: d.email.trim(),
      cap: d.cap.trim().toUpperCase(),
      credit: Number(d.credit),
      ...(italian_municipality_id ? { italian_municipality_id } : {}),
      ...(locality?.trim() ? { locality: locality.trim() } : {}),
      ...(additional_info?.trim() ? { additional_info: additional_info.trim() } : {}),
      ...(tax_code.trim() ? { tax_code: tax_code.trim().toUpperCase() } : {}),
    };
  }

  private handleError(err: unknown) {
    if (err instanceof HttpErrorResponse && err.status === 422 && err.error?.data) {
      const flat = Object.fromEntries(
        Object.entries(err.error.data as Record<string, string[]>).map(([k, v]) => [k, v[0]])
      );
      this.serverErrors.set(flat);
    }
    this.formError.set(apiErrorMessage(err));
  }
}