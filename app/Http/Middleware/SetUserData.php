<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\BasePageController;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetUserData
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     * @throws BindingResolutionException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $user = User::find($userId);

            if ($user) {
                // Cache category exclusions per user (5 minutes)
                $user->categoryexclusions = Cache::remember(
                    'user_category_exclusions_'.$userId,
                    300,
                    fn () => User::getCategoryExclusionById($userId)
                );

                // Share user data with the controller if it's a BasePageController
                $route = $request->route();
                if ($route) {
                    $controller = $route->getController();
                    if ($controller instanceof BasePageController) {
                        $controller->userdata = $user;
                    }
                }
            }
        }

        return $next($request);
    }
}
