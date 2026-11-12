import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/auth.guard.js';

export const routes: Routes = [
    { path: '', redirectTo: 'register', pathMatch: 'full' },
    {
        path: 'register',
        canActivate: [guestGuard],
        loadComponent: () => import('./features/auth/register/register.component').then(m => m.RegisterComponent),
    },
    {
        path: 'login',
        canActivate: [guestGuard],
        loadComponent: () => import('./features/auth/login/login.component').then(m => m.LoginComponent),
    },
    {
        path: '',
        canActivateChild: [authGuard],
        children: [
            { path: 'browse', loadComponent: () => import('./features/browse/browse.component').then(m => m.BrowseComponent) },

            { path: 'films', data: { type: 'film' }, loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
            { path: 'films/:id', data: { type: 'film' }, loadComponent: () => import('./features/media-detail/media-detail.component').then(m => m.MediaDetailComponent) },

            { path: 'tv-series', data: { type: 'tv-series' }, loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
            { path: 'tv-series/:id', data: { type: 'tv-series' }, loadComponent: () => import('./features/media-detail/media-detail.component').then(m => m.MediaDetailComponent) },

            { path: 'genre/:category', loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
            { path: 'search', loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
        ],
    },
    {
        path: 'profile',
        canActivate: [authGuard],
        loadComponent: () => import('./features/profile/profile.component').then(m => m.ProfileComponent),
    },
    { path: '**', redirectTo: 'register' },
];
