import { Component, inject, signal, WritableSignal } from '@angular/core';
import { CurrencyPipe, DatePipe, NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { AuthService } from '../../core/auth.service';
import { apiErrorDetails, apiErrorMessage } from '../../core/api.service';
import { Profile, ProfileService } from '../../core/profile.service';
import { UtilitiesService } from '../../core/utilities.service';
import { validateBirthDate, validateConfirmPassword, validateCredit, validateEmail, validateName, validatePassword, validateRequired, validateSelect, validateUsername } from '../auth/register/validation/field_validators';
import { FieldValidationResult } from '../auth/register/validation/validation_types';
import { getFieldErrorMessage } from '../auth/register/ui/form_errors';
import { PasswordFieldComponent } from '../../shared/password-field/password-field.component.js';
import { FieldName } from '../auth/register/types/registration_form_data.js';

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

type SecKey = 'u_username' | 'u_current' | 'p_current' | 'p_new' | 'p_confirm';

/** Which register-form field's messages each security input reuses */
const SEC_FIELD: Partial<Record<SecKey, FieldName>> = {
  u_username: 'username',
  p_new: 'password',
  p_confirm: 'confirm_password',
};

@Component({
  selector: 'app-profile',
  imports: [ReactiveFormsModule, NgClass, DatePipe, CurrencyPipe, PasswordFieldComponent],
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

    /* --------- Security (username and password) --------- */
  protected usernameForm = this.fb.group({ username: [''], current_password: [''] });
  protected passwordForm = this.fb.group({ current_password: [''], password: [''], confirm_password: [''] });

  protected secResults = signal<Partial<Record<SecKey, FieldValidationResult>>>({});
  protected secServer = signal<Partial<Record<SecKey, string>>>({});
  protected userFormError = signal<string | null>(null);
  protected passFormError = signal<string | null>(null);
  protected secBusy = signal<'username' | 'password' | null>(null);

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

   /* --------- Security helpers --------- */

  sInvalid(key: SecKey) { return !!this.secServer()[key] || this.secResults()[key]?.isValid === false; }
  sValid(key: SecKey)   { return !this.sInvalid(key) && this.secResults()[key]?.isValid === true; }
  sCls(key: SecKey)     { return { 'is-valid': this.sValid(key), 'is-invalid': this.sInvalid(key) }; }

  sError(key: SecKey): string {
    const server = this.secServer()[key];
    if (server) return server;

    const result = this.secResults()[key];
    if (!result || result.isValid) return '';

    if (key === 'u_current' || key === 'p_current') return 'Enter your current password';
    return getFieldErrorMessage(SEC_FIELD[key]!, result.errorCode);
  }

  private checkSec(key: SecKey): FieldValidationResult {
    const u = this.usernameForm.getRawValue();
    const p = this.passwordForm.getRawValue();

    switch (key) {
      case 'u_username': return validateUsername(u.username.trim());
      case 'u_current':  return validateRequired(u.current_password);
      case 'p_current':  return validateRequired(p.current_password);
      case 'p_new':      return validatePassword(p.password);
      case 'p_confirm':  return validateConfirmPassword(p.password, p.confirm_password);
    }
  }

  sBlur(key: SecKey) {
    this.secResults.update(r => ({ ...r, [key]: this.checkSec(key) }));
    this.clearSecServer([key]);

    // The confirmation depends on the new password
    if (key === 'p_new' && this.secResults().p_confirm) {
      this.secResults.update(r => ({ ...r, p_confirm: this.checkSec('p_confirm') }));
    }
  }

  private clearSecServer(keys: SecKey[]) {
    this.secServer.update(s => {
      const next = { ...s };
      keys.forEach(k => delete next[k]);
      return next;
    });
  }

  private clearSecResults(keys: SecKey[]) {
    this.secResults.update(r => {
      const next = { ...r };
      keys.forEach(k => delete next[k]);
      return next;
    });
  }

  /** Validates the given inputs and shows their results. Returns true when all are valid */
  private validateSec(keys: SecKey[]): boolean {
    const results = Object.fromEntries(keys.map(k => [k, this.checkSec(k)])) as Record<SecKey, FieldValidationResult>;
    this.secResults.update(r => ({ ...r, ...results }));
    this.clearSecServer(keys);
    return keys.every(k => results[k].isValid);
  }

  /** Puts the server's field messages under the right inputs; falls back to a general message */
  private applySecErrors(err: unknown, map: Record<string, SecKey>, general: WritableSignal<string | null>) {
    const { message, fields } = apiErrorDetails(err);
    const mapped = Object.fromEntries(
      Object.entries(fields).filter(([name]) => name in map).map(([name, text]) => [map[name], text])
    );

    this.secServer.update(s => ({ ...s, ...mapped }));
    general.set(Object.keys(mapped).length ? null : message);
  }

  /* --------- Change username --------- */

  changeUsername() {
    this.userFormError.set(null);
    if (!this.validateSec(['u_username', 'u_current'])) return;

    const { username, current_password } = this.usernameForm.getRawValue();

    this.notice.set(null);
    this.secBusy.set('username');
    this.api.updateMe({ username: username.trim(), current_password })
      .pipe(finalize(() => this.secBusy.set(null)))
      .subscribe({
        next: () => {
          this.usernameForm.reset();
          this.clearSecResults(['u_username', 'u_current']);
          this.notice.set('Username updated. Use it the next time you sign in.');
        },
        error: err => this.applySecErrors(
          err, { username: 'u_username', current_password: 'u_current' }, this.userFormError),
      });
  }

  /* --------- Change password --------- */

  changePassword() {
    this.passFormError.set(null);
    if (!this.validateSec(['p_current', 'p_new', 'p_confirm'])) return;

    const { current_password, password } = this.passwordForm.getRawValue();

    this.notice.set(null);
    this.secBusy.set('password');
    this.api.updateMe({ password, current_password })
      .pipe(finalize(() => this.secBusy.set(null)))
      .subscribe({
        // The server ended every session: leave with an explanation
        next: () => this.auth.clear('Password changed. Please sign in again.'),
        error: err => this.applySecErrors(
          err, { current_password: 'p_current', password: 'p_new' }, this.passFormError),
      });
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