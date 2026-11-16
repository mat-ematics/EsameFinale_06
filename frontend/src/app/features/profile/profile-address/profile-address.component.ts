import { Component, Injector, afterNextRender, computed, inject, input, signal, viewChild, output } from '@angular/core';
import { NgClass } from '@angular/common';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { apiErrorDetails } from '../../../core/api.service';
import { CountryItem, LookupItem, LookupService, MunicipalityItem } from '../../../core/lookup.service';
import { AddressData, ProfileService } from '../../../core/profile.service';
import { MunicipalityPickerComponent } from '../../../shared/municipality-picker/municipality-picker.component';
import { TypeaheadComponent } from '../../../shared/typeahead/typeahead.component';
import { ITALY_ISO, RegistrationFormData } from '../../auth/register/types/registration_form_data';
import { ErrorCodes } from '../../auth/register/validation/error_codes';
import { validateRegistrationField } from '../../auth/register/validation/form_validator';
import { POSTAL_CODE_FORMATS } from '../../auth/register/validation/postal_codes';
import { FieldValidationResult } from '../../auth/register/validation/validation_types';
import { getFieldErrorMessage } from '../../auth/register/ui/form_errors';
import { ModalComponent } from '../../../shared/modal/modal.component';

type AddressField = 'country_id' | 'italian_municipality_id' | 'street_address' | 'house_number'
                  | 'cap' | 'locality' | 'additional_info';

const FIELDS: AddressField[] = ['country_id', 'italian_municipality_id', 'street_address', 'house_number',
                                'cap', 'locality', 'additional_info'];

@Component({
  selector: 'app-profile-address',
  imports: [ReactiveFormsModule, NgClass, TypeaheadComponent, MunicipalityPickerComponent, ModalComponent],
  templateUrl: './profile-address.component.html',
})
export class ProfileAddressComponent {
  
  private fb = inject(FormBuilder).nonNullable;
  private api = inject(ProfileService);
  private lookups = inject(LookupService);
  private injector = inject(Injector);

  protected status = signal<'loading' | 'ok' | 'error'>('loading');
  protected loadError = signal('');
  protected data = signal<AddressData | null>(null);
  protected notice = signal<string | null>(null);

  protected editing = signal(false);
  protected saving = signal(false);
  protected results = signal<Partial<Record<AddressField, FieldValidationResult>>>({});
  protected serverErrors = signal<Record<string, string>>({});
  protected formError = signal<string | null>(null);

  readonly saved = output<void>();

  protected form = this.fb.group({
    country_id: [''], country_iso: [''],               // country_iso: UI only
    italian_municipality_id: [''],
    street_address: [''], house_number: [''], cap: [''], locality: [''], additional_info: [''],
    tax_code: [''],
  });

  private countryIso = toSignal(this.form.controls.country_iso.valueChanges, { initialValue: '' });
  protected isItaly = computed(() => this.countryIso() === ITALY_ISO);
  protected capPlaceholder = computed(() => POSTAL_CODE_FORMATS[this.countryIso()]?.example ?? 'Postal code');

  private countryField = viewChild<TypeaheadComponent>('countryField');
  private place = viewChild<MunicipalityPickerComponent>('place');

  // Arrow function, so `this` stays bound when the typeahead calls it
  readonly searchCountries = (term: string) => this.lookups.searchCountries(term);

  constructor() {
    this.load();
  }

  protected load() {
    this.status.set('loading');
    this.api.getAddress().subscribe({
      next: res => { this.data.set(res.data); this.status.set('ok'); },
      error: err => { this.loadError.set(apiErrorDetails(err).message); this.status.set('error'); },
    });
  }

  /* --------- Editing --------- */

  protected startEdit() {
    const d = this.data();
    this.notice.set(null);
    this.results.set({});
    this.serverErrors.set({});
    this.formError.set(null);

    this.form.reset({
      country_id: d?.country ? String(d.country.id) : '',
      country_iso: (d?.country?.iso ?? '').toUpperCase(),
      italian_municipality_id: d?.italian_municipality ? String(d.italian_municipality.id) : '',
      street_address: d?.street_address ?? '',
      house_number: d?.house_number ?? '',
      cap: d?.cap ?? '',
      locality: d?.locality ?? '',
      additional_info: d?.additional_info ?? '',
    });
    this.editing.set(true);

    // The typeaheads only exist while editing: show the current values once they render
    afterNextRender(() => {
      if (d?.country) this.countryField()?.setSelection(d.country);
      if (d?.italian_municipality) this.place()?.setMunicipality(d.italian_municipality);
    }, { injector: this.injector });
  }

  protected cancelEdit() {
    this.editing.set(false);
  }

  protected onCountryPicked(item: LookupItem | null) {
    this.form.controls.country_iso.setValue(((item as CountryItem | null)?.iso ?? '').toUpperCase());

    if (!this.isItaly()) {
      this.form.controls.italian_municipality_id.setValue('');
      this.clear('italian_municipality_id');
    }
    this.clear('cap');                                  // the postal code and tax code rules depend on the country
  }

  /** A municipality fills the postal code (editable afterwards) */
  protected onMunicipalityPicked(item: LookupItem | null) {
    if (!item) return;
    this.form.controls.cap.setValue((item as MunicipalityItem).cap);
    this.results.update(r => ({ ...r, cap: this.validate('cap') }));
  }

  /* --------- Validation (same validators as the registration form) --------- */

  /** The registration validators read a RegistrationFormData: fill in what they need */
    private registrationData(): RegistrationFormData {
        return { ...this.form.getRawValue(), birth_country_iso: '', birth_catastal_code: '' } as unknown as RegistrationFormData;
    }

  private validate(field: AddressField): FieldValidationResult {
    return validateRegistrationField(field, this.registrationData());
  }

  private clear(field: AddressField) {
    this.results.update(r => ({ ...r, [field]: undefined }));
  }

  protected isInvalid(field: AddressField) { return !!this.serverErrors()[field] || this.results()[field]?.isValid === false; }
  protected isValid(field: AddressField)   { return !this.isInvalid(field) && this.results()[field]?.isValid === true; }
  protected cls(field: AddressField)       { return { 'is-valid': this.isValid(field), 'is-invalid': this.isInvalid(field) }; }

  protected error(field: AddressField): string {
    const server = this.serverErrors()[field];
    if (server) return server;

    const result = this.results()[field];
    if (!result || result.isValid) return '';

    if (field === 'cap' && result.errorCode === ErrorCodes.INVALID_CAP) {
      const example = POSTAL_CODE_FORMATS[this.countryIso()]?.example;
      return example ? `Enter a valid postal code (e.g. ${example})` : 'Enter a valid postal code';
    }
    return getFieldErrorMessage(field, result.errorCode);
  }

  protected onBlur(field: AddressField) {
    this.results.update(r => ({ ...r, [field]: this.validate(field) }));
    this.serverErrors.update(({ [field]: _removed, ...rest }) => rest);
  }

  /* --------- Save --------- */

  protected save() {
    const results = Object.fromEntries(FIELDS.map(f => [f, this.validate(f)])) as Record<AddressField, FieldValidationResult>;
    this.results.set(results);
    this.serverErrors.set({});
    this.formError.set(null);
    if (FIELDS.some(f => !results[f].isValid)) return;

    const v = this.form.getRawValue();
    const body = {
      country_id: Number(v.country_id),
      ...(this.isItaly() && v.italian_municipality_id ? { italian_municipality_id: Number(v.italian_municipality_id) } : {}),
      cap: v.cap.trim().toUpperCase(),
      street_address: v.street_address.trim(),
      house_number: v.house_number.trim(),
      locality: v.locality.trim() || null,              // null clears the field on the server
      additional_info: v.additional_info.trim() || null,
    };

    this.saving.set(true);
    this.api.updateAddress(body)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: res => {
          this.data.set(res.data);
          this.editing.set(false);
          this.notice.set('Address updated');
          this.saved.emit();
        },
        error: err => {
          const { message, fields } = apiErrorDetails(err);
          this.serverErrors.set(fields);
          this.formError.set(message);
        },
      });
  }
}