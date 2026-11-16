import {
  ChangeDetectionStrategy, Component, ElementRef, Injector, afterNextRender, effect, inject, input, output, viewChild,
} from '@angular/core';

const FIELDS = 'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])';
let nextId = 0;

@Component({
  selector: 'app-modal',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './modal.component.html',
  styleUrl: './modal.component.scss',
})
export class ModalComponent {
  readonly open = input(false);
  readonly title = input.required<string>();
  /** Set to false while something is being saved: the modal cannot be dismissed */
  readonly dismissible = input(true);
  /** Turn off for long forms, so a stray click outside does not discard what was typed */
  readonly closeOnBackdrop = input(true);
  /** The user asked to close (X, Escape or backdrop). The parent decides by changing `open` */
  readonly closed = output<void>();

  protected readonly titleId = `modal-title-${nextId++}`;
  private readonly dialog = viewChild.required<ElementRef<HTMLDialogElement>>('dialog');
  private readonly injector = inject(Injector);
  private pressStartedOnBackdrop = false;

  constructor() {
    effect(() => {
      const el = this.dialog().nativeElement;

      if (this.open()) {
        if (el.open) return;
        el.showModal();
        // Put the cursor in the first field once the projected form has rendered
        afterNextRender(() => el.querySelector<HTMLElement>(FIELDS)?.focus(), { injector: this.injector });
      } else if (el.open) {
        el.close();
      }
    });
  }

  protected request() {
    if (this.dismissible()) this.closed.emit();
  }

  /** Escape: take over, so the parent stays the single source of truth */
  protected onCancel(event: Event) {
    event.preventDefault();
    this.request();
  }

  /** The browser closed it by itself (rare): tell the parent so the state does not drift */
  protected onNativeClose() {
    if (this.open()) this.closed.emit();
  }

  // A drag that starts inside (selecting text) and ends on the backdrop must not close the modal
  protected onMouseDown(event: MouseEvent) {
    this.pressStartedOnBackdrop = event.target === this.dialog().nativeElement;
  }

  protected onClick(event: MouseEvent) {
    const onBackdrop = event.target === this.dialog().nativeElement;
    if (onBackdrop && this.pressStartedOnBackdrop && this.closeOnBackdrop()) this.request();
    this.pressStartedOnBackdrop = false;
  }
}