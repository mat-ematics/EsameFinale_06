import { ChangeDetectionStrategy, Component, DestroyRef, ElementRef, inject, input, model, signal, viewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { apiErrorMessage } from '../../core/api.service';
import { ChunkedUploadService } from '../../core/chunked-upload.service';

const MAX_BYTES = 2 * 1024 * 1024 * 1024;       // keep in sync with UPLOAD_MAX_BYTES on the server

@Component({
  selector: 'app-video-upload',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './video-upload.component.html',
})
export class VideoUploadComponent {
  readonly inputId = input.required<string>();
  readonly label = input('Video (MP4)');
  readonly required = input(false);
  /** The video already stored, when editing */
  readonly existing = input<string | null>(null);
  readonly invalid = input(false);
  readonly errorText = input('');

  /** The id of the finished upload: null while there is none, or while a new one is running */
  readonly uploadId = model<string | null>(null);
  /** True while the upload is running: the parent keeps its Save button disabled */
  readonly uploading = model(false);

  private readonly uploads = inject(ChunkedUploadService);
  private readonly fileInput = viewChild<ElementRef<HTMLInputElement>>('fileInput');

  protected readonly state = signal<'idle' | 'uploading' | 'ready' | 'error'>('idle');
  protected readonly percent = signal(0);
  protected readonly fileName = signal('');
  protected readonly message = signal('');

  private subscription?: Subscription;
  private sessionId: string | null = null;
  private file: File | null = null;

  constructor() {
    // Leaving the page stops sending, but keeps the partial upload: it can resume later
    inject(DestroyRef).onDestroy(() => this.subscription?.unsubscribe());
  }

  protected pick(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.cancel();                                    // forget a previous choice, running or finished
    if (!file) return;

    if (file.type !== 'video/mp4' && !/\.mp4$/i.test(file.name)) return this.reject(input, 'The video must be an MP4 file');
    if (file.size > MAX_BYTES) return this.reject(input, 'The video cannot exceed 2 GB');

    this.start(file);
  }

  private reject(input: HTMLInputElement, message: string) {
    input.value = '';
    this.message.set(message);
    this.state.set('error');
  }

  protected start(file: File) {
    this.file = file;
    this.fileName.set(file.name);
    this.message.set('');
    this.percent.set(0);
    this.state.set('uploading');
    this.uploading.set(true);
    this.uploadId.set(null);

    this.subscription = this.uploads.upload(file).subscribe({
      next: event => {
        if (event.kind === 'started') {
          this.sessionId = event.id;
          if (event.resumed) this.message.set('Resuming a previous upload…');
        } else if (event.kind === 'progress') {
          this.percent.set(event.percent);
        } else {
          this.percent.set(100);
          this.message.set('');
          this.uploadId.set(event.id);
          this.uploading.set(false);
          this.state.set('ready');
        }
      },
      error: err => {
        this.message.set(apiErrorMessage(err));
        this.uploading.set(false);
        this.state.set('error');
      },
    });
  }

  protected retry() {
    if (this.file) this.start(this.file);             // the server remembers what it already has
  }

  /** Stops the upload (if running), deletes it on the server and empties the field */
  protected cancel() {
    this.subscription?.unsubscribe();
    this.subscription = undefined;

    const id = this.sessionId;
    this.sessionId = null;
    if (id) this.uploads.discard(id).subscribe({ error: () => {} });

    const input = this.fileInput()?.nativeElement;
    if (input) input.value = '';

    this.file = null;
    this.uploadId.set(null);
    this.uploading.set(false);
    this.state.set('idle');
    this.percent.set(0);
    this.message.set('');
  }
}