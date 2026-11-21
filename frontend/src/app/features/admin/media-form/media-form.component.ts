import { Component, DestroyRef, computed, inject, input, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { takeUntilDestroyed, toObservable, toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { combineLatest, finalize, map, of, switchMap } from 'rxjs';
import { apiErrorDetails } from '../../../core/api.service';
import { MediaAdminService } from '../../../core/media-admin.service';
import { MediaCatalogService } from '../../../core/media-catalog.service';
import { Category, MEDIA_LABEL, MediaDetail, MediaType, mediaLink, mediaSectionLink } from '../../../models/media';

type Kind = 'cover' | 'banner' | 'video';

const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_IMAGE = 5 * 1024 * 1024;
const MAX_VIDEO = 50 * 1024 * 1024;
const THIS_YEAR = new Date().getFullYear();

const pad = (n: number) => String(n).padStart(2, '0');
/** 67 -> "01:07:00" */
const toLength = (minutes: number) => `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}:00`;

/** API field names that the form shows under a different name */
const FIELD_MAP: Record<string, string> = { start_year: 'year', end_year: 'endYear', length: 'minutes' };

@Component({
  selector: 'app-media-form',
  imports: [ReactiveFormsModule, NgClass, RouterLink],
  templateUrl: './media-form.component.html',
})
export class MediaFormComponent {
  // Bound by the router: route data, the :id path param and the ?genre= query param
  readonly type = input.required<MediaType>();
  readonly id = input<string>();
  readonly genre = input<string>();

  private readonly fb = inject(FormBuilder).nonNullable;
  private readonly admin = inject(MediaAdminService);
  private readonly catalog = inject(MediaCatalogService);
  private readonly router = inject(Router);

  protected readonly categories = toSignal(this.catalog.categories(), { initialValue: [] as Category[] });
  protected readonly isFilm = computed(() => this.type() === 'film');
  protected readonly editing = computed(() => !!this.id());
  protected readonly label = computed(() => (this.isFilm() ? 'film' : 'TV series'));
  protected readonly maxYear = THIS_YEAR + 1;

  protected readonly form = this.fb.group({
    name: [''], description: [''],
    year: [''], endYear: [''], minutes: [''],          // year = start year for a series
    directors: [''], actors: [''],
    categories: [[] as string[]],
  });

  protected readonly cover = signal<File | null>(null);
  protected readonly banner = signal<File | null>(null);
  protected readonly video = signal<File | null>(null);
  protected readonly coverPreview = signal<string | null>(null);
  protected readonly bannerPreview = signal<string | null>(null);
  protected readonly removeBanner = signal(false);

  protected readonly existing = signal<MediaDetail | null>(null);
  protected readonly notFound = signal(false);
  protected readonly clientErrors = signal<Record<string, string>>({});
  protected readonly serverErrors = signal<Record<string, string>>({});
  protected readonly formError = signal<string | null>(null);
  protected readonly saving = signal(false);
  protected readonly progress = signal(0);

  /** Where "Cancel" goes */
  protected readonly backLink = computed(() => {
    const m = this.existing();
    return m ? mediaLink(m) : mediaSectionLink(this.type());
  });

  constructor() {
    // Edit: load the title and fill the form. New: preselect the genre from the URL
    combineLatest([toObservable(this.type), toObservable(this.id), toObservable(this.genre)]).pipe(
      switchMap(([type, id, slug]) => id
        ? this.catalog.detail(type, Number(id)).pipe(map(media => ({ media, preset: null as Category | null })))
        : (slug ? this.catalog.genre(slug) : of(null)).pipe(map(preset => ({ media: undefined, preset })))),
      takeUntilDestroyed(),
    ).subscribe(({ media, preset }) => {
      if (media === null) { this.notFound.set(true); return; }

      if (media) {
        this.existing.set(media);
        this.form.patchValue({
          name: media.title,
          description: media.description ?? '',
          year: String(media.year),
          endYear: media.endYear ? String(media.endYear) : '',
          minutes: media.durationMinutes ? String(media.durationMinutes) : '',
          directors: media.directors,
          actors: media.actors,
          categories: media.categories,
        });
      } else if (preset) {
        this.form.patchValue({ categories: [preset.name] });
      }
    });

    inject(DestroyRef).onDestroy(() => {
      this.revoke(this.coverPreview());
      this.revoke(this.bannerPreview());
    });
  }

  private revoke(url: string | null) {
    if (url) URL.revokeObjectURL(url);
  }

  /* --------- Genres --------- */

  protected toggleCategory(name: string) {
    const current = this.form.controls.categories.value;
    this.form.controls.categories.setValue(current.includes(name) ? current.filter(c => c !== name) : [...current, name]);
  }

  /* --------- Files --------- */

  protected pick(kind: Kind, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    const errors = { ...this.clientErrors() };
    delete errors[kind];

    if (file) {
      if (kind === 'video') {
        if (file.type !== 'video/mp4') errors[kind] = 'The video must be an MP4 file';
        else if (file.size > MAX_VIDEO) errors[kind] = 'The video cannot exceed 50 MB';
      } else if (!IMAGE_TYPES.includes(file.type)) errors[kind] = 'Use a JPG, PNG or WebP image';
      else if (file.size > MAX_IMAGE) errors[kind] = 'The image cannot exceed 5 MB';
    }
    this.clientErrors.set(errors);

    if (errors[kind]) {                       // refuse it: the input and the state stay empty
      input.value = '';
      this.setFile(kind, null);
      return;
    }
    this.setFile(kind, file);
    if (kind === 'banner') this.removeBanner.set(false);
  }

  private setFile(kind: Kind, file: File | null) {
    this[kind].set(file);
    if (kind === 'video') return;

    const preview = kind === 'cover' ? this.coverPreview : this.bannerPreview;
    this.revoke(preview());
    preview.set(file ? URL.createObjectURL(file) : null);
  }

  /* --------- Errors --------- */

  protected error(field: string): string {
    return this.clientErrors()[field] ?? this.serverErrors()[field] ?? '';
  }

  protected bad(field: string): boolean {
    return !!this.error(field);
  }

  /* --------- Save --------- */

  private validate(): Record<string, string> {
    const v = this.form.getRawValue();
    const errors: Record<string, string> = {};
    const year = Number(v.year);
    const minutes = Number(v.minutes);

    if (!v.name.trim()) errors['name'] = 'Name is required';
    if (!v.directors.trim()) errors['directors'] = 'Directors are required';
    if (!v.actors.trim()) errors['actors'] = 'Cast is required';
    if (!Number.isInteger(year) || year < 1888 || year > this.maxYear) errors['year'] = `Enter a year between 1888 and ${this.maxYear}`;
    if (!v.categories.length) errors['categories'] = 'Pick at least one genre';

    if (this.isFilm()) {
      if (!Number.isInteger(minutes) || minutes < 1 || minutes > 240) errors['minutes'] = 'Enter the duration in minutes (1-240)';
    } else if (v.endYear && Number(v.endYear) < year) {
      errors['endYear'] = 'The end year cannot be before the start year';
    }

    if (!this.editing() && !this.cover()) errors['cover'] = 'A cover image is required';
    if (this.isFilm() && !this.editing() && !this.video()) errors['video'] = 'A video file is required';

    return { ...errors, ...Object.fromEntries(Object.entries(this.clientErrors()).filter(([k]) => ['cover', 'banner', 'video'].includes(k))) };
  }

  private formData(): FormData {
    const v = this.form.getRawValue();
    const data = new FormData();

    data.append('name', v.name.trim());
    data.append('description', v.description.trim());          // empty clears it: the server turns '' into null
    data.append(this.isFilm() ? 'year' : 'start_year', v.year);
    if (this.isFilm()) data.append('length', toLength(Number(v.minutes)));
    else data.append('end_year', v.endYear);                    // empty = still running
    data.append('directors', v.directors.trim());
    data.append('actors', v.actors.trim());
    v.categories.forEach(name => data.append('categories[]', name));

    const [cover, banner, video] = [this.cover(), this.banner(), this.video()];
    if (cover) data.append('cover', cover);
    if (banner) data.append('banner', banner);
    if (video && this.isFilm()) data.append('video', video);
    if (this.removeBanner()) data.append('remove_banner', '1');

    return data;
  }

  protected save() {
    const errors = this.validate();
    this.clientErrors.set(errors);
    this.serverErrors.set({});
    this.formError.set(null);
    if (Object.keys(errors).length) return;

    this.saving.set(true);
    this.progress.set(0);

    this.admin.saveTitle(this.type(), this.editing() ? Number(this.id()) : null, this.formData())
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: event => {
          if (event.kind === 'progress') this.progress.set(event.percent);
          else this.router.navigate(mediaLink({ type: this.type(), id: event.data.id }));
        },
        error: err => {
          const { message, fields } = apiErrorDetails(err);
          const mapped: Record<string, string> = {};
          for (const [key, text] of Object.entries(fields)) {
            const base = key.split('.')[0];                     // categories.0 -> categories
            mapped[FIELD_MAP[base] ?? base] = text;
          }
          this.serverErrors.set(mapped);
          this.formError.set(message);
        },
      });
  }
}