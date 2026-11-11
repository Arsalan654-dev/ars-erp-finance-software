<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    /**
     * Convert 403 HTTP exceptions into a redirect with a modal-friendly flash message.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpException && $e->getStatusCode() === 403) {
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['message' => 'You do not have permission to access this.'], 403);
            }

            return redirect()->back()->with('permission_denied', true);
        }

        return parent::render($request, $e);
    }
}
