<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;

class AuthToken
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+([A-Za-z0-9_\-\.]+)/', $auth, $m)) {
            return response()->json(['error' => ['code'=>'unauthorized','message'=>'Token Bearer ausente']], 401);
        }

        $raw = $m[1];
        $hash = hash('sha256', $raw);

        $token = PersonalAccessToken::where('token_hash', $hash)
            ->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at','>', now());
            })
            ->with('user')
            ->first();

        if (!$token || !$token->user) {
            return response()->json(['error' => ['code'=>'unauthorized','message'=>'Token inválido ou expirado']], 401);
        }

        // Atualiza last_used_at e injeta o usuário
        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('auth_token', $token);
        $request->setUserResolver(fn() => $token->user);

        return $next($request);
    }
}
