import { Component, inject, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { AuthService } from '../../../core/auth.service';
import { apiErrorMessage } from '../../../core/api.service';
import { COUNTRY_ITALY_ID, FieldName, RegistrationFormData } from './types/registration_form_data';
import { validateRegistrationField, validateRegistrationForm } from './validation/form_validator';
import { FormFieldsValidationResult } from './validation/validation_types';
import { getFieldErrorMessage } from './ui/form_errors';
import { PasswordFieldComponent } from '../../../shared/password-field/password-field.component.js';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, NgClass, RouterLink, PasswordFieldComponent],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  private fb = inject(FormBuilder).nonNullable;
  private auth = inject(AuthService);
  private router = inject(Router);

  /** Values only: validation stays in your validators, not in Angular's */
  readonly form = this.fb.group({
    name: [''], surname: [''], email: [''], username: [''], password: [''], confirm_password: [''],
    birthdate: [''], gender: [''], country_id: [''],
    italian_municipality_id: [{ value: '', disabled: true }],
    street_address: [''], house_number: [''], cap: [''],
    locality: [''], additional_info: [''],
    credit: [''], currency: ['EUR'],
  });

  /** Latest validation result per field (replaces the DOM is-valid/is-invalid toggling) */
  readonly results = signal<Partial<FormFieldsValidationResult>>({});
  /** Errors returned by Laravel (422), keyed by field */
  readonly serverErrors = signal<Record<string, string>>({});
  readonly formError = signal<string | null>(null);
  readonly submitting = signal(false);

  constructor() {
    // Municipality is only enabled for Italy (was the 'input' listener in register.ts)
    this.form.controls.country_id.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe(id => {
        const municipality = this.form.controls.italian_municipality_id;
        if (id === COUNTRY_ITALY_ID) {
          municipality.enable();
        } else {
          municipality.reset('');
          municipality.disable();
        }
        this.results.update(r => ({ ...r, italian_municipality_id: undefined }));
      });
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
    return result && !result.isValid ? getFieldErrorMessage(field, result.errorCode) : '';
  }

  /* --------- Events --------- */

  onBlur(field: FieldName) {
    const data = this.data();
    const next = { ...this.results(), [field]: validateRegistrationField(field, data) } as Partial<FormFieldsValidationResult>;

    // The country affects whether the municipality is required
    if (field === 'country_id') {
      next.italian_municipality_id = validateRegistrationField('italian_municipality_id', data);
    }

    // The confirmation depends on the password: re-check it if it was already validated
    if (field === 'password' && this.results().confirm_password) {
        next.confirm_password = validateRegistrationField('confirm_password', data);
    }

    this.results.set(next);

    // The user is editing the field: drop the old server error
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
        // The backend logs the user in on register, so a token means we can go straight in
        next: () => this.router.navigate([this.auth.isLoggedIn() ? '/browse' : '/login']),
        error: err => this.handleError(err),
      });
  }

  private toPayload(d: RegistrationFormData) {
    const { italian_municipality_id, locality, additional_info, confirm_password, ...rest } = d;
    return {
        ...rest,
        username: d.username.trim(),
        name: d.name.trim(),
        surname: d.surname.trim(),
        email: d.email.trim(),
        credit: Number(d.credit),
        italian_municipality_id: italian_municipality_id || null,
        locality: locality?.trim() || null,
        additional_info: additional_info?.trim() || null,
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