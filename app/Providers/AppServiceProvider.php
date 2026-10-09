<?php

namespace App\Providers;

use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Support\SchoolBranding;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Injecte les variables CSS du thème dans le HTML initial (évite tout flash de couleurs par défaut).
        View::composer('app', function ($view) {
            $school = auth()->user()?->school;
            if (! $school) {
                $resolver = app(TenantResolver::class);
                $school = $resolver->resolve(request());
            }
            $view->with('branding', SchoolBranding::for($school));
        });

        Inertia::share('auth.user.notifications', function () {
            if (! auth()->check()) {
                return [];
            }

            return auth()->user()
                ->notifications()
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'data' => $notification->data ?? [],
                    'is_read' => (bool) $notification->read_at,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ])
                ->all();
        });

        Inertia::share('auth.user.unread_notifications_count', function () {
            if (! auth()->check()) {
                return 0;
            }

            return auth()->user()->notifications()->whereNull('read_at')->count();
        });

        // "class" (mot réservé) et "guardian" n'ont pas de correspondance directe avec leur nom de modèle.
        Route::model('class', ClassRoom::class);
        Route::model('guardian', ParentGuardian::class);
    }
}
