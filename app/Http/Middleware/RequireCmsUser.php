<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireCmsUser
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (trim((string) $request->session()->get('cms_user', '')) === '') {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
