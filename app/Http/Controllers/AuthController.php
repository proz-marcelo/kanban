<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function issueTokens(User $user): array
    {
        $accessRaw  = Str::random(64);
        $refreshRaw = Str::random(64);

        $token = PersonalAccessToken::create([
            'user_id'            => $user->id,
            'token_hash'         => hash('sha256', $accessRaw),
            'refresh_token_hash' => hash('sha256', $refreshRaw),
            'expires_at'         => now()->addHours(2),
            'refresh_expires_at' => now()->addDays(7),
        ]);

        return [
            'access_token'  => $accessRaw,
            'refresh_token' => $refreshRaw,
            'expires_in'    => 2 * 60 * 60,
            'token_type'    => 'Bearer',
        ];
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['error'=>['code'=>'invalid_credentials','message'=>'E-mail ou senha inválidos']], 401);
        }

        return response()->json($this->issueTokens($user));
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required','string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        $record = PersonalAccessToken::where('refresh_token_hash', $hash)
            ->where(function($q){
                $q->whereNull('refresh_expires_at')->orWhere('refresh_expires_at','>', now());
            })
            ->with('user')
            ->first();

        if (!$record || !$record->user) {
            return response()->json(['error'=>['code'=>'invalid_refresh','message'=>'Refresh token inválido/expirado']], 401);
        }

        // opcional: invalidar o antigo
        $record->delete();

        return response()->json($this->issueTokens($record->user));
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('auth_token');
        if ($token) $token->delete();

        return response()->json(['ok'=>true]);
    }
}
