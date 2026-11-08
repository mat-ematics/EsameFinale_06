import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe, DatePipe, NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { AuthService } from '../../core/auth.service';
import { apiErrorDetails, apiErrorMessage } from '../../core/api.service';
import { Profile, ProfileService } from '../../core/profile.service';
import { UtilitiesService } from '../../core/utilities.service';
import { validateBirthDate, validateCredit, validateEmail, validateName, validateSelect } from '../auth/register/validation/field_validators';
import { FieldValidationResult } from '../auth/register/validation/validation_types';
import { getFieldErrorMessage } from '../auth/register/ui/form_errors';

type EditField = 'name' | 'surname' | 'email' | 'birthdate' | 'gender';
const FIELDS: EditField[] = ['name', 'surname', 'email', 'birthdate', 'gender'];

const VALIDATORS: Record<EditField, (value: string) => FieldValidationResult> = {
  name: validateName,
  surname: validateName,
  email: validateEmail,
  birthdate: validateBirthDate,
  gender: validateSelect,
};

const GENDER_LABELS: Record<string, string> = { male: 'Male', female: 'Female', other: 'Other / prefer not to say' };

@Component({
  selector: 'app-profile',
  imports: [ReactiveFormsModule, NgClass, DatePipe, CurrencyPipe],
  templateUrl: './profile.component.html',
})
export class ProfileComponent {
  private fb = inject(FormBuilder).nonNullable;
  private api = inject(ProfileService);
  private auth = inject(AuthService);

  /** Role shown from the JWT: display only, never use it for permissions */
  protected role = UtilitiesService
    .readJWTToken<{ data?: { primary_role?: string } }>(this.auth.token() ?? '')?.data?.primary_role;

  /* --------- Loading --------- */
  protected status = signal<'loading' | 'ok' | 'error'>('loading');
  protected loadError = signal('');
  protected profile = signal<Profile>({});
  protected credit = signal<number | null>(null);
  protected notice = signal<string | null>(null);

  /* --------- Editing --------- */
  protected editing = signal(false);
  protected saving = signal(false);
  protected results = signal<Partial<Record<EditField, FieldValidationResult>>>({});
  protected serverErrors = signal<Record<string, string>>({});
  protected formError = signal<string | null>(null);

  protected form = this.fb.group({ name: [''], surname: [''], email: [''], birthdate: [''], gender: [''] });

  /* --------- Credit --------- */
  protected amount = this.fb.control('');
  protected amountError = signal('');
  protected creditBusy = signal(false);

  /* --------- Delete account --------- */
  protected confirmingDelete = signal(false);
  protected deleting = signal(false);

  constructor() {
    this.load();
  }

  load(initial = true) {
    if (initial) this.status.set('loading');

    this.api.getMe().subscribe({
      next: res => {
        this.profile.set(res.data ?? {});
        this.resetForm();
        this.status.set('ok');
      },
      error: err => {
        this.loadError.set(apiErrorMessage(err));
        this.status.set('error');
      },
    });

    // Independent from the profile: a failure here only hides the balance
    this.api.getCredit().subscribe({
      next: res => this.credit.set(res.data?.credit ?? null),
      error: () => this.credit.set(null),
    });
  }

  protected genderLabel(value?: string) {
    return value ? GENDER_LABELS[value] ?? value : '-';
  }

  /* --------- Personal data --------- */

  private resetForm() {
    const p = this.profile();
    this.form.reset({
      name: p.name ?? '',
      surname: p.surname ?? '',
      email: p.email ?? '',
      birthdate: (p.birthdate ?? '').slice(0, 10),    // the API may send a full timestamp
      gender: p.gender ?? '',
    });
    this.results.set({});
    this.serverErrors.set({});
    this.formError.set(null);
  }

  startEdit() {
    this.notice.set(null);
    this.editing.set(true);
  }

  cancelEdit() {
    this.resetForm();
    this.editing.set(false);
  }

  isInvalid(field: EditField) { return !!this.serverErrors()[field] || this.results()[field]?.isValid === false; }
  isValid(field: EditField)   { return !this.isInvalid(field) && this.results()[field]?.isValid === true; }
  cls(field: EditField)       { return { 'is-valid': this.isValid(field), 'is-invalid': this.isInvalid(field) }; }

  error(field: EditField): string {
    const server = this.serverErrors()[field];
    if (server) return server;
    const result = this.results()[field];
    return result && !result.isValid ? getFieldErrorMessage(field, result.errorCode) : '';
  }

  onBlur(field: EditField) {
    const result = VALIDATORS[field](this.form.controls[field].value.trim());
    this.results.update(r => ({ ...r, [field]: result }));
    this.serverErrors.update(({ [field]: _removed, ...rest }) => rest);
  }

  save() {
    const values = this.form.getRawValue();
    const results = Object.fromEntries(
      FIELDS.map(f => [f, VALIDATORS[f](values[f].trim())])
    ) as Record<EditField, FieldValidationResult>;

    this.results.set(results);
    this.serverErrors.set({});
    this.formError.set(null);
    if (FIELDS.some(f => !results[f].isValid)) return;

    // Send only what changed: an untouched e-mail can't trip the "unique" rule
    const original = this.profile();
    const changes: Record<string, string> = {};
    for (const f of FIELDS) {
      const now = values[f].trim();
      const before = f === 'birthdate' ? (original.birthdate ?? '').slice(0, 10) : String(original[f] ?? '');
      if (now !== before) changes[f] = now;
    }

    if (!Object.keys(changes).length) {
      this.editing.set(false);
      return;
    }

    this.saving.set(true);
    this.api.updateMe(changes)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: () => {
          this.editing.set(false);
          this.notice.set('Profile updated');
          this.load(false);                       // refresh without the loading screen
        },
        error: err => this.handleError(err),
      });
  }

  private handleError(err: unknown) {
    const { message, fields } = apiErrorDetails(err);
    this.serverErrors.set(fields);                 // shown under each field
    this.formError.set(message);
  }

  /* --------- Credit --------- */

  changeCredit(kind: 'add' | 'remove') {
    const raw = this.amount.value.trim().replace(',', '.');     // "12,50" is accepted
    const result = validateCredit(raw);

    if (!result.isValid) {
      this.amountError.set(getFieldErrorMessage('credit', result.errorCode));
      return;
    }

    this.amountError.set('');
    this.notice.set(null);
    this.creditBusy.set(true);

    const amount = Number(raw);
    const call = kind === 'add' ? this.api.addCredit(amount) : this.api.removeCredit(amount);

    call.pipe(finalize(() => this.creditBusy.set(false))).subscribe({
      next: res => {
        this.credit.set(res.data?.credit ?? this.credit());
        this.amount.reset('');
        this.notice.set(kind === 'add' ? 'Credit added' : 'Credit removed');
      },
      error: err => {
        const { message, fields } = apiErrorDetails(err);
        // A server message about the field is more useful than "Validation failed"
        this.amountError.set(fields['credit'] ?? message);
        },
    });
  }

  /* --------- Delete account --------- */

  deleteAccount() {
    this.deleting.set(true);
    this.api.deleteMe()
      .pipe(finalize(() => this.deleting.set(false)))
      .subscribe({
        next: () => this.auth.clear(),            // clears the token and goes to /login
        error: err => {
          this.formError.set(apiErrorMessage(err));
          this.confirmingDelete.set(false);
        },
      });
  }
}