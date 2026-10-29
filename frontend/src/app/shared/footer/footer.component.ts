import { Component } from '@angular/core';

@Component({
  imports: [],
  selector: 'app-footer',
  styleUrl: './footer.component.scss',
  templateUrl: './footer.component.html',
})
export class FooterComponent {
    protected year = new Date().getFullYear();
    protected links = ['F.A.Q.', 'Privacy', 'Link1', 'Link2', 'Privacy', 'Link1', 'Link2', 'F.A.Q.', 'F.A.Q.'];
}
