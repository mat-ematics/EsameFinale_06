import { Component, Injector, afterNextRender, computed, inject, signal, viewChild } from '@angular/core';
import { NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { EMPTY, Subject, catchError, distinctUntilChanged, finalize, forkJoin, of, switchMap } from 'rxjs';
import { AuthService } from '../../../core/auth.service';
import { apiErrorMessage } from '../../../core/api.service';
import { CountryItem, LookupItem, LookupService, MunicipalityItem } from '../../../core/lookup.service';
import { MunicipalityPickerComponent } from '../../../shared/municipality-picker/municipality-picker.component';
import { PasswordFieldComponent } from '../../../shared/password-field/password-field.component';
import { TypeaheadComponent } from '../../../shared/typeahead/typeahead.component';
import { FieldName, ITALY_ISO, RegistrationFormData } from './types/registration_form_data';
import { ErrorCodes } from './validation/error_codes';
import { POSTAL_CODE_FORMATS } from './validation/postal_codes';
import { validateRegistrationField, validateRegistrationForm } from './validation/form_validator';
import { decodeTaxCode, generateTaxCode } from './validation/tax_code';
import { FieldValidationResult, FormFieldsValidationResult } from './validation/validation_types';
import { getFieldErrorMessage } from './ui/form_errors';

/** Fields whose blur may complete or refresh the tax code */
const TAX_CODE_SOURCES: FieldName[] = ['name', 'surname', 'birthdate', 'gender'];

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, NgClass, RouterLink, PasswordFieldComponent, TypeaheadComponent, MunicipalityPickerComponent],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  private fb = inject(FormBuilder).nonNullable;
  private auth = inject(AuthService);
  private router = inject(Router);
  private lookups = inject(LookupService);
  private injector = inject(Injector);

  /** Values only: validation stays in your validators, not in Angular's */
  readonly form = this.fb.group({
    // Account
    username: [''], email: [''], password: [''], confirm_password: [''],                // confirm_password: UI only
    // Personal
    name: [''], surname: [''], birthdate: [''], gender: [''],
    birth_country_id: [''], birth_country_iso: [''],                                    // UI only
    birth_municipality_id: [''], birth_catastal_code: [''],                             // UI only
    tax_code: [''],
    // Residence
    country_id: [''], country_iso: [''],                                                // country_iso: UI only
    italian_municipality_id: [''],
    street_address: [''], house_number: [''], cap: [''], locality: [''], additional_info: [''],
    // Credit
    credit: [''], currency: ['EUR'],
  });

  readonly results = signal<Partial<FormFieldsValidationResult>>({});
  readonly serverErrors = signal<Record<string, string>>({});
  readonly formError = signal<string | null>(null);
  readonly submitting = signal(false);

  /* --------- Country state --------- */
  private countryIso = toSignal(this.form.controls.country_iso.valueChanges, { initialValue: '' });
  private birthCountryIso = toSignal(this.form.controls.birth_country_iso.valueChanges, { initialValue: '' });

  protected residenceIsItaly = computed(() => this.countryIso() === ITALY_ISO);
  protected birthIsItaly = computed(() => this.birthCountryIso() === ITALY_ISO);
  /** Italian rules apply to residents and to people born in Italy */
  protected italianTaxCode = computed(() => this.residenceIsItaly() || this.birthIsItaly());

  protected taxCodeHint = computed(() => {
    if (!this.italianTaxCode()) return 'Optional.';
    const bornAbroad = !!this.birthCountryIso() && !this.birthIsItaly();
    return bornAbroad
      ? 'Born abroad: enter your tax code manually.'
      : 'Filled in automatically from your name, surname, date of birth, gender and municipality of birth.';
  });

  protected capMatches = signal<MunicipalityItem[]>([]);
  protected capPlaceholder = computed(() => POSTAL_CODE_FORMATS[this.countryIso()]?.example ?? 'Postal code');

  private selectedResidence = signal<MunicipalityItem | null>(null);
  private residencePlace = viewChild<MunicipalityPickerComponent>('residencePlace');
  private birthPlace = viewChild<MunicipalityPickerComponent>('birthPlace');
  private birthCountryField = viewChild<TypeaheadComponent>('birthCountryField');
  private capLookup$ = new Subject<string>();

  /** False once the user types the tax code by hand: autofill then never overwrites it */
  private taxCodeAuto = true;

  // Arrow function, so `this` stays bound when the typeahead calls it
  readonly searchCountries = (term: string) => this.lookups.searchCountries(term);

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

  /** validateRegistrationField plus the checks that depend on UI state */
  private check(field: FieldName, data = this.data()): FieldValidationResult {
    if (field === 'birth_municipality_id' && this.birthPlace()?.hasUnselectedText()) {
      return { isValid: false, errorCode: ErrorCodes.MUNICIPALITY_NOT_SELECTED };
    }
    return validateRegistrationField(field, data);
  }

  private revalidate(...fields: FieldName[]) {
    const data = this.data();
    this.results.update(r => {
      const next = { ...r } as Partial<FormFieldsValidationResult>;
      for (const field of fields) next[field] = this.check(field, data);
      return next;
    });
  }

  private clearResult(field: FieldName) {
    this.results.update(r => ({ ...r, [field]: undefined }));
  }

  /* --------- Residence --------- */

  onCountryPicked(item: LookupItem | null) {
    this.form.controls.country_iso.setValue((item as CountryItem | null)?.iso ?? '');

    if (!this.residenceIsItaly()) {                    // the Italian municipality no longer applies
      this.form.controls.italian_municipality_id.setValue('');
      this.selectedResidence.set(null);
      this.capMatches.set([]);
      this.clearResult('italian_municipality_id');
    }

    // The postal code format and the tax code requirement depend on the country
    this.clearResult('cap');
    this.clearResult('tax_code');
    this.refreshTaxCode();
  }

  onResidenceMunicipalityPicked(item: LookupItem | null) {
    if (item) this.applyResidence(item as MunicipalityItem);
    else this.selectedResidence.set(null);
  }

  /** Municipality chosen: its CAP fills the postal code */
  private applyResidence(m: MunicipalityItem) {
    this.selectedResidence.set(m);
    this.capMatches.set([]);
    this.form.controls.cap.setValue(m.cap);
    this.revalidate('cap');
  }

  /** Choice made from code (the CAP lookup or a chip), not typed in the picker */
  protected pickFromCap(m: MunicipalityItem) {
    this.residencePlace()?.setMunicipality(m);
    this.applyResidence(m);
  }

  onCapInput(value: string) {
    this.capLookup$.next(this.residenceIsItaly() ? value.trim() : '');
  }

  private onCapMatches(items: MunicipalityItem[] | null) {
    this.capMatches.set([]);
    // Never override a municipality the user already chose
    if (!items?.length || this.selectedResidence()) return;

    if (items.length === 1) this.pickFromCap(items[0]);
    else this.capMatches.set(items);                   // several: let the user choose
  }

  /* --------- Place of birth --------- */

  onBirthCountryPicked(item: LookupItem | null) {
    this.form.controls.birth_country_iso.setValue((item as CountryItem | null)?.iso ?? '');

    if (!this.birthIsItaly()) {                        // the Italian picker disappears: forget its value
      this.form.controls.birth_municipality_id.setValue('');
      this.form.controls.birth_catastal_code.setValue('');
      this.clearResult('birth_municipality_id');
    }

    this.clearResult('tax_code');
    this.refreshTaxCode();
  }

  onBirthMunicipalityPicked(item: LookupItem | null) {
    this.form.controls.birth_catastal_code.setValue((item as MunicipalityItem | null)?.catastal_code ?? '');
    this.refreshTaxCode();
    if (item) this.revalidate('birth_municipality_id'); else this.clearResult('birth_municipality_id');
  }

  /* --------- Tax code --------- */

  /** Forward: personal data -> tax code (only while the user is not typing it by hand) */
  private refreshTaxCode() {
    if (this.taxCodeAuto) {
      const d = this.data();
      const code = this.birthIsItaly()
        ? generateTaxCode({
            name: d.name, surname: d.surname, birthdate: d.birthdate,
            gender: d.gender, birthPlaceCode: d.birth_catastal_code ?? '',
          })
        : null;

      // null clears a previously generated code that is no longer computable
      this.form.controls.tax_code.setValue(code ?? '');
      if (code) this.revalidate('tax_code'); else this.clearResult('tax_code');
    } else if (this.results().tax_code) {
      this.revalidate('tax_code');                     // a shown result may have gone stale
    }

    // The tax code and the municipality of birth must agree
    if (this.results().birth_municipality_id) this.revalidate('birth_municipality_id');
  }

  onTaxCodeInput(value: string) {
    const code = value.trim().toUpperCase();
    this.taxCodeAuto = code === '';                    // typing pauses autofill, clearing resumes it
    if (code.length === 16) this.fillFromTaxCode(code);
  }

  /** Backward: tax code -> birth date, gender, country and municipality of birth (empty fields only) */
  private fillFromTaxCode(code: string) {
    const decoded = decodeTaxCode(code);
    if (!decoded) return;

    const c = this.form.controls;
    const filled: FieldName[] = [];

    if (!c.birthdate.value) { c.birthdate.setValue(decoded.birthdate); filled.push('birthdate'); }
    if (!c.gender.value)    { c.gender.setValue(decoded.gender); filled.push('gender'); }
    if (filled.length) this.revalidate(...filled);

    // Foreign states: your database has no table to resolve those codes
    if (!decoded.abroad) this.fillBirthPlace(decoded.birthPlaceCode);
  }

  private fillBirthPlace(catastalCode: string) {
    const c = this.form.controls;
    const needCountry = !c.birth_country_id.value;
    const needMunicipality = !c.birth_municipality_id.value;

    // Nothing to complete, or the user already chose another country: never override it
    if (!needCountry && !needMunicipality) return;
    if (!needCountry && !this.birthIsItaly()) return;

    forkJoin({
      italy: needCountry ? this.lookups.countryByIso(ITALY_ISO) : of([] as CountryItem[]),
      municipality: needMunicipality ? this.lookups.municipalityByCatastalCode(catastalCode) : of([] as MunicipalityItem[]),
    })
      .pipe(catchError(() => EMPTY))                   // a convenience: failing silently is fine
      .subscribe(({ italy, municipality }) => {
        // The user may have chosen something while the requests were running
        if (needCountry && italy[0] && !c.birth_country_id.value) {
          this.birthCountryField()?.setSelection(italy[0]);
          c.birth_country_iso.setValue(italy[0].iso);
        }

        const m = municipality[0];
        if (m && this.birthIsItaly() && !c.birth_municipality_id.value) {
          c.birth_catastal_code.setValue(m.catastal_code);
          // The picker only exists once Italy is the country of birth: wait for it to render
          afterNextRender(() => this.birthPlace()?.setMunicipality(m), { injector: this.injector });
          this.clearResult('birth_municipality_id');
        }

        this.refreshTaxCode();
      });
  }

  /* --------- Events --------- */

  onBlur(field: FieldName) {
    const data = this.data();
    const next = { ...this.results(), [field]: this.check(field, data) } as Partial<FormFieldsValidationResult>;

    if (field === 'country_id' && this.results().cap) {
      next.cap = validateRegistrationField('cap', data);
    }
    if (field === 'password' && this.results().confirm_password) {
      next.confirm_password = validateRegistrationField('confirm_password', data);
    }
    // The tax code and the municipality of birth must agree: refresh the other one too
    if (field === 'tax_code' && this.birthIsItaly()) {
      next.birth_municipality_id = this.check('birth_municipality_id', data);
    }

    this.results.set(next);
    if (TAX_CODE_SOURCES.includes(field)) this.refreshTaxCode();

    this.serverErrors.update(({ [field]: _removed, ...rest }) => rest);
  }

  submit() {
    const data = this.data();
    const result = validateRegistrationForm(data);

    // The municipality of birth also depends on UI state (typed but not selected)
    const birth = this.check('birth_municipality_id', data);

    this.results.set({ ...result.fields, birth_municipality_id: birth });
    this.serverErrors.set({});
    this.formError.set(null);
    if (!result.isValid || !birth.isValid) return;

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
    const { confirm_password, country_iso, birth_country_id, birth_country_iso,
            birth_municipality_id, birth_catastal_code, tax_code,
            italian_municipality_id, locality, additional_info, ...rest } = d;

    return {
      ...rest,
      username: d.username.trim(),
      name: d.name.trim(),
      surname: d.surname.trim(),
      email: d.email.trim(),
      street_address: d.street_address.trim(),
      house_number: d.house_number.trim(),
      cap: d.cap.trim().toUpperCase(),
      credit: Number(d.credit),
      ...(italian_municipality_id ? { italian_municipality_id } : {}),
      ...(locality?.trim() ? { locality: locality.trim() } : {}),
      ...(additional_info?.trim() ? { additional_info: additional_info.trim() } : {}),
      ...(tax_code.trim() ? { tax_code: tax_code.trim().toUpperCase() } : {}),
    };
  }

  private handleError(err: unknown) {
    // Laravel validation: { error: 'Validation failed', data: { field: ['msg', ...] } }
    if (err instanceof HttpErrorResponse && err.status === 422 && err.error?.data) {
      const flat = Object.fromEntries(
        Object.entries(err.error.data as Record<string, string[]>).map(([k, v]) => [k, v[0]])
      );
      this.serverErrors.set(flat);
    }
    this.formError.set(apiErrorMessage(err));
  }
}