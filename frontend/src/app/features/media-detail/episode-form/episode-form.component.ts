import { Component, inject, input, output, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { filter, finalize } from 'rxjs';
import { apiErrorDetails } from '../../../core/api.service';
import { MediaAdminService } from '../../../core/media-admin.service';
import { ModalComponent } from '../../../shared/modal/modal.component';
import { Episode } from '../../../models/media';
import { VideoUploadComponent } from '../../../shared/video-upload/video-upload.component';

const pad = (n: number) => String(n).padStart(2, '0');
const toLength = (minutes: number) => `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}:00`;
const FIELD_MAP: Record<string, string> = {
    season_number: 'season',
    episode_number: 'number',
    length: 'minutes',
    video_upload_id: 'video',
};

@Component({
  selector: 'app-episode-form',
  imports: [ModalComponent, ReactiveFormsModule, NgClass, VideoUploadComponent],
  templateUrl: './episode-form.component.html',
})
export class EpisodeFormComponent {
  readonly open = input(false);
  readonly seriesId = input.required<number>();
  /** The episode being edited. null = a new one */
  readonly episode = input<Episode | null>(null);
  /** Suggestions for a new episode */
  readonly suggestion = input<{ season: number; number: number; year: number }>({ season: 1, number: 1, year: new Date().getFullYear() });

  readonly closed = output<void>();
  readonly saved = output<void>();

  private readonly fb = inject(FormBuilder).nonNullable;
  private readonly admin = inject(MediaAdminService);

  protected readonly form = this.fb.group({
    name: [''], description: [''], season: [''], number: [''], minutes: [''], year: [''],
  });

  protected readonly videoUploadId = signal<string | null>(null);
  protected readonly videoBusy = signal(false);

  protected readonly cover = signal<File | null>(null);
  protected readonly errors = signal<Record<string, string>>({});
  protected readonly formError = signal<string | null>(null);
  protected readonly saving = signal(false);
  protected readonly progress = signal(0);

  constructor() {
    // Every time the modal opens: start from the episode, or from the suggestion
    toObservable(this.open).pipe(filter(Boolean), takeUntilDestroyed()).subscribe(() => this.reset());
  }

  private reset() {
    const e = this.episode();
    const s = this.suggestion();

    this.form.reset({
      name: e?.title ?? '',
      description: e?.description ?? '',
      season: String(e?.season ?? s.season),
      number: String(e?.number ?? s.number),
      minutes: e?.durationMinutes ? String(e.durationMinutes) : '',
      year: String(e?.year ?? s.year),
    });
    this.cover.set(null);
    this.errors.set({});
    this.formError.set(null);
    this.progress.set(0);

    this.videoUploadId.set(null);
    this.videoBusy.set(false);
  }

  protected pick(kind: 'cover', event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    this.cover.set(file);
  }

  protected bad(field: string) { return !!this.errors()[field]; }

  private validate(): Record<string, string> {
    const v = this.form.getRawValue();
    const errors: Record<string, string> = {};
    const int = (value: string, min: number, max: number) => Number.isInteger(Number(value)) && Number(value) >= min && Number(value) <= max;

    if (!v.name.trim()) errors['name'] = 'Name is required';
    if (!int(v.season, 1, 100)) errors['season'] = '1-100';
    if (!int(v.number, 1, 9999)) errors['number'] = 'Enter a number';
    if (!int(v.minutes, 1, 240)) errors['minutes'] = 'Enter the duration in minutes (1-240)';
    if (!int(v.year, 1888, new Date().getFullYear() + 1)) errors['year'] = 'Enter a valid year';

    if (this.videoBusy()) errors['video'] = 'Wait for the video upload to finish';
    else if (!this.episode() && !this.videoUploadId()) errors['video'] = 'A video file is required';

    const cover = this.cover();
    if (cover && (!['image/jpeg', 'image/png', 'image/webp'].includes(cover.type) || cover.size > 5 * 1024 * 1024)) {
      errors['cover'] = 'A JPG, PNG or WebP image up to 5 MB';
    }
    return errors;
  }

  protected save() {
    const errors = this.validate();
    this.errors.set(errors);
    this.formError.set(null);
    if (Object.keys(errors).length) return;

    const v = this.form.getRawValue();
    const data = new FormData();
    data.append('name', v.name.trim());
    data.append('description', v.description.trim());
    data.append('season_number', v.season);
    data.append('episode_number', v.number);
    data.append('length', toLength(Number(v.minutes)));
    data.append('year', v.year);
    if (this.videoUploadId()) data.append('video_upload_id', this.videoUploadId()!);
    if (this.cover()) data.append('cover', this.cover()!);

    this.saving.set(true);
    this.admin.saveEpisode(this.seriesId(), this.episode()?.id ?? null, data)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: event => {
          if (event.kind === 'progress') this.progress.set(event.percent);
          else this.saved.emit();
        },
        error: err => {
          const { message, fields } = apiErrorDetails(err);
          const mapped: Record<string, string> = {};
          for (const [key, text] of Object.entries(fields)) mapped[FIELD_MAP[key] ?? key] = text;
          this.errors.set(mapped);
          this.formError.set(message);
        },
      });
  }
}