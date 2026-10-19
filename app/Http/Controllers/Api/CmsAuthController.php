<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CmsAuthController extends Controller
{
    public function __construct(private readonly LegacyRepository $legacy)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate(
            ['username' => ['required', 'string'], 'password' => ['required', 'string']],
            ['username.required' => 'Username and password are required.', 'password.required' => 'Username and password are required.']
        );

        try {
            $user = $this->legacy->instance()->authenticateCmsUser(trim($credentials['username']), $credentials['password']);
        } catch (\Throwable) {
            return response()->json(['status' => 'error', 'message' => 'Invalid login details.'], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('cms_user', $user['username']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Login successful.',
            'data' => ['user' => $user],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => ['user' => ['username' => $request->session()->get('cms_user')]],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'ok', 'message' => 'Logged out successfully.']);
    }
}
