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
        path: 'browse',
        canActivate: [authGuard],
        loadComponent: () => import('./features/browse/browse.component').then(m => m.BrowseComponent),
    },
    {
        path: 'profile',
        canActivate: [authGuard],
        loadComponent: () => import('./features/profile/profile.component').then(m => m.ProfileComponent),
    },
    { path: '**', redirectTo: 'register' },
];
