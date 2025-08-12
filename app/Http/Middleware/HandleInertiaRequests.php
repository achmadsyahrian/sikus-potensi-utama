<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tighten\Ziggy\Ziggy as ZiggyZiggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app_name' => config('app.name', 'Laravel'),
            'ziggy' => fn () => [
                ...(new ZiggyZiggy())->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => Auth::check() ? Auth::user()->load('roles') : null,
                'activeRoleId' => Session::get('active_role_id')
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ]);
    }
}