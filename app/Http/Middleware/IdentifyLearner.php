<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every visitor an anonymous ID in a long-lived cookie so progress and
 * quiz scores survive a refresh — without asking anybody to register.
 */
class IdentifyLearner
{
    public const COOKIE = 'learner_id';

    public function handle(Request $request, Closure $next): Response
    {
        $learnerId = $request->cookie(self::COOKIE);

        if (! is_string($learnerId) || ! Str::isUuid($learnerId)) {
            $learnerId = (string) Str::uuid();

            // One year, so a shared link still remembers you next week.
            Cookie::queue(self::COOKIE, $learnerId, 60 * 24 * 365);
        }

        $request->attributes->set('learner_id', $learnerId);

        return $next($request);
    }
}
