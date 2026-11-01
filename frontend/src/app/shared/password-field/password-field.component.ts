import { Component, forwardRef, input, output, signal } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

@Component({
  selector: 'app-password-field',
  templateUrl: './password-field.component.html',
  styleUrl: './password-field.component.scss',
  providers: [{
    provide: NG_VALUE_ACCESSOR,
    useExisting: forwardRef(() => PasswordFieldComponent),
    multi: true,
  }],
})
export class PasswordFieldComponent implements ControlValueAccessor {
  readonly inputId = input.required<string>();
  readonly placeholder = input('');
  readonly autocomplete = input('current-password');
  /** Validation state, driven by the parent (same as the other fields) */
  readonly valid = input(false);
  readonly invalid = input(false);
  readonly blurred = output<void>();

  protected visible = signal(false);
  protected value = signal('');
  protected disabled = signal(false);

  private onChange: (value: string) => void = () => {};
  private onTouched: () => void = () => {};

  /* --------- ControlValueAccessor --------- */
  writeValue(value: string | null) { this.value.set(value ?? ''); }
  registerOnChange(fn: (value: string) => void) { this.onChange = fn; }
  registerOnTouched(fn: () => void) { this.onTouched = fn; }
  setDisabledState(isDisabled: boolean) { this.disabled.set(isDisabled); }

  /* --------- Events --------- */
  protected onInput(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    this.value.set(value);
    this.onChange(value);
  }

  protected onBlur() {
    this.onTouched();
    this.blurred.emit();
  }

  protected toggle() { this.visible.update(v => !v); }
}