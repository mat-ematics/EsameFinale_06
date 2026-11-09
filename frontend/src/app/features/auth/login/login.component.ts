import { Component, inject, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { AuthService } from '../../../core/auth.service';
import { apiErrorMessage } from '../../../core/api.service';
import { validateRequired } from '../register/validation/field_validators';
import { FieldValidationResult } from '../register/validation/validation_types';
import { getFieldErrorMessage } from '../register/ui/form_errors';
import { PasswordFieldComponent } from '../../../shared/password-field/password-field.component.js';

type LoginField = 'username' | 'password';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, NgClass, RouterLink, PasswordFieldComponent],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private fb = inject(FormBuilder).nonNullable;
  private auth = inject(AuthService);
  private router = inject(Router);

  readonly form = this.fb.group({ username: [''], password: [''] });

  readonly results = signal<Partial<Record<LoginField, FieldValidationResult>>>({});
  readonly formError = signal<string | null>(null);
  readonly submitting = signal(false);

  protected notice: string | null = history.state?.notice ?? null;

  /* --------- Template helpers (same shape as RegisterComponent) --------- */

  isInvalid(field: LoginField) { return this.results()[field]?.isValid === false; }
  isValid(field: LoginField)   { return this.results()[field]?.isValid === true; }
  cls(field: LoginField)       { return { 'is-valid': this.isValid(field), 'is-invalid': this.isInvalid(field) }; }

  error(field: LoginField): string {
    const result = this.results()[field];
    return result && !result.isValid ? getFieldErrorMessage(field, result.errorCode) : '';
  }

  /* --------- Events --------- */

  /** Login only checks that something was typed: complexity rules belong to registration */
  private validate(field: LoginField): FieldValidationResult {
    return validateRequired(this.form.controls[field].value.trim());
  }

  onBlur(field: LoginField) {
    this.results.update(r => ({ ...r, [field]: this.validate(field) }));
  }

  submit() {
    const results = {
      username: this.validate('username'),
      password: this.validate('password'),
    };
    this.results.set(results);
    this.formError.set(null);
    if (!results.username.isValid || !results.password.isValid) return;

    const { username, password } = this.form.getRawValue();

    this.submitting.set(true);
    // The username is trimmed (spaces are not allowed in it); the password is sent exactly as typed
    this.auth.login(username.trim(), password)
      .pipe(finalize(() => this.submitting.set(false)))
      .subscribe({
        next: () => this.router.navigate(['/browse']),
        error: err => this.formError.set(apiErrorMessage(err)),
      });
  }
}