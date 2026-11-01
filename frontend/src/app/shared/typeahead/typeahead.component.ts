import { Component, forwardRef, input, output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { Observable, Subject, catchError, debounceTime, of, switchMap } from 'rxjs';
import { LookupItem } from '../../core/lookup.service';

@Component({
  selector: 'app-typeahead',
  templateUrl: './typeahead.component.html',
  styleUrl: './typeahead.component.scss',
  providers: [{
    provide: NG_VALUE_ACCESSOR,
    useExisting: forwardRef(() => TypeaheadComponent),
    multi: true,
  }],
})
export class TypeaheadComponent implements ControlValueAccessor {
  readonly inputId = input.required<string>();
  readonly placeholder = input('Start typing…');
  readonly minChars = input(3);
  /** Search function provided by the parent (usually a LookupService method) */
  readonly search = input.required<(term: string) => Observable<LookupItem[]>>();
  readonly valid = input(false);
  readonly invalid = input(false);
  readonly blurred = output<void>();

  protected text = signal('');
  protected items = signal<LookupItem[]>([]);
  protected open = signal(false);
  protected loading = signal(false);
  protected failed = signal(false);
  protected active = signal(-1);
  protected disabled = signal(false);
  private selected = signal<LookupItem | null>(null);

  private term$ = new Subject<string>();
  private onChange: (value: string) => void = () => {};
  private onTouched: () => void = () => {};

  constructor() {
    this.term$.pipe(
      debounceTime(300),
      // switchMap cancels the previous request when a newer term arrives
      switchMap(term => {
        if (term.length < this.minChars()) return of([] as LookupItem[]);
        this.failed.set(false);
        return this.search()(term).pipe(
          catchError(() => { this.failed.set(true); return of([] as LookupItem[]); })
        );
      }),
      takeUntilDestroyed(),
    ).subscribe(items => {
      this.items.set(items);
      this.loading.set(false);
      this.active.set(items.length ? 0 : -1);
    });
  }

  protected optionId(index: number) { return `${this.inputId()}-opt-${index}`; }
  protected listId() { return `${this.inputId()}-list`; }
  protected shortText() { return this.text().trim().length < this.minChars(); }

  /* --------- ControlValueAccessor --------- */
  writeValue(value: string | null) {
    // Only clears are handled: the id alone doesn't tell us the display name
    if (!value) {
      this.selected.set(null);
      this.text.set('');
      this.items.set([]);
    }
  }
  registerOnChange(fn: (value: string) => void) { this.onChange = fn; }
  registerOnTouched(fn: () => void) { this.onTouched = fn; }
  setDisabledState(isDisabled: boolean) { this.disabled.set(isDisabled); }

  /* --------- Events --------- */
  protected onInput(event: Event) {
    const text = (event.target as HTMLInputElement).value;
    this.text.set(text);

    // Editing after a choice cancels it: the form must not keep a stale id
    if (this.selected()) {
      this.selected.set(null);
      this.onChange('');
    }

    const term = text.trim();
    this.open.set(term.length > 0);
    this.loading.set(term.length >= this.minChars());
    this.term$.next(term);
  }

  protected onFocus() {
    if (!this.selected() && this.text().trim().length > 0) this.open.set(true);
  }

  protected onBlur() {
    this.open.set(false);

    // Typed the exact name but didn't click it: accept it
    if (!this.selected()) {
      const typed = this.text().trim().toLowerCase();
      const exact = this.items().find(item => item.name.toLowerCase() === typed);
      if (exact) this.select(exact);
    }

    this.onTouched();
    this.blurred.emit();
  }

  protected select(item: LookupItem) {
    this.selected.set(item);
    this.text.set(item.name);
    this.open.set(false);
    this.onChange(String(item.id));
  }

  protected onKeydown(event: KeyboardEvent) {
    const count = this.items().length;
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        this.open.set(true);
        if (count) this.active.update(i => (i + 1) % count);
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (count) this.active.update(i => (i - 1 + count) % count);
        break;
      case 'Enter':
        if (this.open() && !this.loading() && count && this.active() >= 0) {
          event.preventDefault();               // don't submit the form
          this.select(this.items()[this.active()]);
        }
        break;
      case 'Escape':
        this.open.set(false);
        break;
    }
  }
}