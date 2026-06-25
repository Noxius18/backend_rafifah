<?php

namespace App\Http\Middleware;

use App\Models\MahasantriApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMahasantriApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = MahasantriApiToken::with('mahasantri')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (!$token || !$token->mahasantri) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('mahasantri_api_token', $token);
        $request->setUserResolver(fn () => $token->mahasantri);

        return $next($request);
    }
}
