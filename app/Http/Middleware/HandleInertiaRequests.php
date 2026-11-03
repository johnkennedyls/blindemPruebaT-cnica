<?php

namespace App\Http\Middleware;

use App\Http\Parametrics\ParametricDefinition;
use App\Http\Parametrics\ParametricRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'is_admin' => $request->user()?->isAdmin() ?? false,
                'role_label' => $request->user()?->role->label(),
            ],
            'parametrics' => array_map(
                fn (ParametricDefinition $definition): array => [
                    'slug' => $definition->slug,
                    'title' => $definition->title,
                ],
                ParametricRegistry::all(),
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
