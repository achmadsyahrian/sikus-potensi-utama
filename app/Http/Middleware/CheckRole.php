<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect('login');
        }

        $activeRoleId = Session::get('active_role_id');
        $activeRole = $request->user()->roles->find($activeRoleId);

        if (!$activeRole) {
            if ($request->user()->roles->count() > 1) {
                return redirect()->route('select-role');
            }
            $activeRole = $request->user()->roles->first();
            Session::put('active_role_id', $activeRole->id);
        }

        if ($activeRole->slug === 'superadmin' || in_array($activeRole->slug, $roles)) {
            return $next($request);
        }

        return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses.');
    }
}
