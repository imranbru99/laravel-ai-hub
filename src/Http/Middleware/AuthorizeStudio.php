<?php

namespace ImranDevBd\AiHub\Http\Middleware;

use Closure;
use ImranDevBd\AiHub\Facades\AIHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeStudio
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAuthorized($request)) {
            return $next($request);
        }

        if (! $request->expectsJson() && ! $request->user()) {
            if (\Illuminate\Support\Facades\Route::has('login')) {
                return redirect()->guest(route('login'));
            }
        }

        abort(403, 'Unauthorized access to Laravel AI Hub Studio.');
    }

    protected function isAuthorized(Request $request): bool
    {
        // 1. Custom callback registered via AIHub::auth(fn ($req) => ...)
        if (AIHub::hasAuthCallback()) {
            return AIHub::check($request);
        }

        // 2. Named Gate (e.g. 'viewAiHub') if defined in Gate
        $gateName = config('ai-hub.settings.gate', 'viewAiHub');
        if ($gateName && Gate::has($gateName)) {
            return Gate::forUser($request->user())->allows($gateName);
        }

        // 3. Allowed emails from config or AI_HUB_EMAILS
        $allowedEmails = (array) config('ai-hub.settings.allowed_emails', []);
        $user = $request->user();
        if (! empty($allowedEmails)) {
            return $user && in_array($user->email, $allowedEmails, true);
        }

        // 4. Allowed roles from config or AI_HUB_ROLES
        $allowedRoles = (array) config('ai-hub.settings.roles', []);
        if (! empty($allowedRoles)) {
            if (! $user) {
                return false;
            }

            // Spatie or custom hasAnyRole / hasRole
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($allowedRoles);
            }

            if (method_exists($user, 'hasRole')) {
                foreach ($allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }

            // Direct attribute checks (role, is_admin, etc.)
            $userRole = $user->role ?? $user->role_name ?? null;
            if ($userRole && in_array($userRole, $allowedRoles, true)) {
                return true;
            }

            if (in_array('admin', $allowedRoles, true) && ! empty($user->is_admin)) {
                return true;
            }

            return false;
        }

        // 5. Default environment behavior: local / testing is allowed; production requires explicit auth or custom middleware
        if (app()->environment('local', 'testing')) {
            return true;
        }

        // In production without specific callback/roles/gate configured:
        // Allow if user is authenticated, otherwise block
        return $user !== null;
    }
}
