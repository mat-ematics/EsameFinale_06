import { Routes } from '@angular/router';
import { adminGuard, authGuard, guestGuard } from './core/auth.guard.js';

export const routes: Routes = [
    { path: '', redirectTo: 'login', pathMatch: 'full' },
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

            // Films
            { path: 'films', data: { type: 'film' }, loadComponent: () => import('./features/catalog/catalog.component').then(m => m.CatalogComponent) },
            { path: 'films/genre/:category', data: { type: 'film' }, loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
            { path: 'films/:id', data: { type: 'film' }, loadComponent: () => import('./features/media-detail/media-detail.component').then(m => m.MediaDetailComponent) },

            // TV series
            { path: 'tv-series', data: { type: 'tv-series' }, loadComponent: () => import('./features/catalog/catalog.component').then(m => m.CatalogComponent) },
            { path: 'tv-series/genre/:category', data: { type: 'tv-series' }, loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },
            { path: 'tv-series/:id', data: { type: 'tv-series' }, loadComponent: () => import('./features/media-detail/media-detail.component').then(m => m.MediaDetailComponent) },

            // A genre across both types, and search
            { path: 'genre/:category', loadComponent: () => import('./features/catalog/catalog.component').then(m => m.CatalogComponent) },
            { path: 'search', loadComponent: () => import('./features/media-list/media-list.component').then(m => m.MediaListComponent) },

            // Admin (placeholders until the real pages exist)
            {
                path: 'admin',
                canActivateChild: [adminGuard],
                children: [
                    { path: 'films/new', data: { type: 'film' }, loadComponent: () => import('./features/admin/media-form/media-form.component').then(m => m.MediaFormComponent) },
                    { path: 'films/:id/edit', data: { type: 'film' }, loadComponent: () => import('./features/admin/media-form/media-form.component').then(m => m.MediaFormComponent) },
                    { path: 'tv-series/new', data: { type: 'tv-series' }, loadComponent: () => import('./features/admin/media-form/media-form.component').then(m => m.MediaFormComponent) },
                    { path: 'tv-series/:id/edit', data: { type: 'tv-series' }, loadComponent: () => import('./features/admin/media-form/media-form.component').then(m => m.MediaFormComponent) },
                ],
            },
            { path: 'watch/film/:id', data: { type: 'film' }, loadComponent: () => import('./features/watch/watch.component').then(m => m.WatchComponent) },
            { path: 'watch/tv-series/:id', data: { type: 'tv-series' }, loadComponent: () => import('./features/watch/watch.component').then(m => m.WatchComponent) },        ],
    },
    {
        path: 'profile',
        canActivate: [authGuard],
        loadComponent: () => import('./features/profile/profile.component').then(m => m.ProfileComponent),
    },
    { path: '**', redirectTo: 'register' },
];
