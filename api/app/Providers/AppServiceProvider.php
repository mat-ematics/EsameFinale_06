<?php

namespace App\Providers;

use App\Models\Authorization\Role;
use App\Models\User\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        Relation::enforceMorphMap([
            'film' => 'App/Models/Media/Film',
            'tvSeries' => 'App/Models/Media/TvSeries',
            'episode' => 'App/Models/Media/Episode',
        ]);


        Validator::excludeUnvalidatedArrayKeys();

        
        if (Schema::hasTable('roles')) {
            try {
                foreach (Role::all('name') as $role) {
                    $roleName = $role->name;
                    Gate::define('is' . ucfirst(Str::studly($roleName)), fn (User $user) => $user->hasRole($roleName));
                }
            } catch (\Throwable $e) {
                Log::alert('Failed to register roles in AppServiceProvider: ' . $e->getMessage());
            }
        }
    }
}
