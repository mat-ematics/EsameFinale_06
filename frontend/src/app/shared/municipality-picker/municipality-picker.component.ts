import {
  ChangeDetectionStrategy, Component, computed, forwardRef, inject, input, output, signal, viewChild,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { ItalyGeoService } from '../../core/italy-geo.service';
import { LookupItem, LookupService, MunicipalityItem } from '../../core/lookup.service';
import { TypeaheadComponent } from '../typeahead/typeahead.component';

/**
 * Region -> Province -> Municipality, as one form control.
 * Its value is the municipality id; region and province only narrow the search.
 */
@Component({
  selector: 'app-municipality-picker',
  imports: [TypeaheadComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './municipality-picker.component.html',
  providers: [{
    provide: NG_VALUE_ACCESSOR,
    useExisting: forwardRef(() => MunicipalityPickerComponent),
    multi: true,
  }],
})
export class MunicipalityPickerComponent implements ControlValueAccessor {
  /** Prefix for the element ids: two pickers on one page must not share ids */
  readonly idPrefix = input.required<string>();
  readonly label = input('Municipality');
  readonly required = input(false);
  readonly valid = input(false);
  readonly invalid = input(false);

  /** A municipality was chosen (or the choice was cancelled). Not emitted by setMunicipality() */
  readonly picked = output<MunicipalityItem | null>();
  readonly blurred = output<void>();

  private readonly lookups = inject(LookupService);
  protected readonly geo = inject(ItalyGeoService);
  private readonly typeahead = viewChild(TypeaheadComponent);

  protected readonly regionId = signal('');
  protected readonly provinceId = signal('');
  protected readonly disabled = signal(false);
  private readonly selected = signal<MunicipalityItem | null>(null);

  protected readonly provinces = computed(() =>
    this.geo.provinces().filter(p => String(p.region_id) === this.regionId()));

  /** True while the user typed something that was not chosen from the suggestions */
  readonly hasUnselectedText = computed(() => this.typeahead()?.hasUnselectedText() ?? false);

  // Arrow function, so `this` stays bound when the typeahead calls it
  protected readonly search = (term: string) => this.lookups.searchMunicipalities(term, {
    provinceId: this.provinceId() || undefined,
    regionId: this.regionId() || undefined,
  });

  private onChange: (value: string) => void = () => {};
  private onTouched: () => void = () => {};

  constructor() {
    this.geo.ensureLoaded();
  }

  /* --------- ControlValueAccessor --------- */
  writeValue(value: string | null) { if (!value) this.clear(); }
  registerOnChange(fn: (value: string) => void) { this.onChange = fn; }
  registerOnTouched(fn: () => void) { this.onTouched = fn; }
  setDisabledState(isDisabled: boolean) { this.disabled.set(isDisabled); }

  /* --------- Used by the parent (autofill) --------- */

  /** Selects a municipality from code: fills region, province and the text */
  setMunicipality(item: MunicipalityItem) {
    this.selected.set(item);
    this.regionId.set(item.region_id ? String(item.region_id) : '');
    this.provinceId.set(item.province_id ? String(item.province_id) : '');
    this.typeahead()?.setSelection(item);
    this.onChange(String(item.id));
  }

  /** Empties everything, region and province included */
  clear() {
    this.selected.set(null);
    this.regionId.set('');
    this.provinceId.set('');
    this.typeahead()?.setSelection(null);
    this.onChange('');
  }

  /* --------- Events --------- */

  protected same(id: number | string, current: string) { return String(id) === current; }

  protected onRegionChange(value: string) {
    this.regionId.set(value);
    this.provinceId.set('');                       // the province list changed
    const m = this.selected();
    if (m && value && String(m.region_id ?? '') !== value) this.dropSelection();
  }

  protected onProvinceChange(value: string) {
    this.provinceId.set(value);
    const m = this.selected();
    if (m && value && String(m.province_id ?? '') !== value) this.dropSelection();
  }

  protected onPicked(item: LookupItem | null) {
    const m = item as MunicipalityItem | null;
    this.selected.set(m);
    if (m) {                                       // municipality -> province and region
      this.regionId.set(m.region_id ? String(m.region_id) : '');
      this.provinceId.set(m.province_id ? String(m.province_id) : '');
    }
    this.onChange(m ? String(m.id) : '');
    this.picked.emit(m);
  }

  protected onBlur() {
    this.onTouched();
    this.blurred.emit();
  }

  /** The region or province no longer matches the chosen municipality: cancel the choice only */
  private dropSelection() {
    this.selected.set(null);
    this.typeahead()?.setSelection(null);
    this.onChange('');
    this.picked.emit(null);
  }
}