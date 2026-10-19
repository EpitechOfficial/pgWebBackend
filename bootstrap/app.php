<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Http\Middleware\RequireCmsUser;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['cms.auth' => RequireCmsUser::class]);
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($exception instanceof ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => collect($exception->errors())->flatten()->first() ?: 'The submitted data is invalid.',
                        'errors' => $exception->errors(),
                    ], 422);
                }

                if ($exception instanceof HttpExceptionInterface) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $exception->getMessage() ?: 'Request failed.',
                    ], $exception->getStatusCode());
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                ], 500);
            }
        });
    })->create();
