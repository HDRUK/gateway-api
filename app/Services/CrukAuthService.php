<?php

namespace App\Services;

use Config;
use App\Models\User;
use App\Http\Controllers\JwtController;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\UnauthorizedException;

class CrukAuthService
{
    public function __construct(
        private readonly JwtController $jwt
    ) {
    }

    /**
     * Register a user and return auth payload.
     */
    public function register(array $input): array
    {
        $name = trim(($input['firstname'] ?? '') . ' ' . ($input['lastname'] ?? ''));
        if (empty($name)) {
            $name = $input['email'];
        }

        $provider = $this->resolveProvider($input['provider'] ?? null);

        $user = User::create([
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'name' => $name,
            'firstname' => $input['firstname'] ?? null,
            'lastname' => $input['lastname'] ?? null,
            'provider' => $provider,
        ]);

        return [
            'user' => $user,
            'access_token' => $this->jwt->generateToken($user->id),
            'token_type' => 'bearer',
        ];
    }

    /**
     * Login user and return auth payload.
     *
     * @throws UnauthorizedException
     */
    public function login(array $input): array
    {
        $provider = $this->resolveProvider($input['provider'] ?? null);

        $user = User::where('email', $input['email'])
            ->where('provider', $provider)
            ->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            throw new UnauthorizedException('Invalid credentials');
        }

        return [
            'user' => $user,
            'access_token' => $this->jwt->generateToken($user->id),
            'token_type' => 'bearer',
        ];
    }

    private function resolveProvider(?string $provider): string
    {
        if (!empty($provider) && $provider === Config::get('constants.provider.cruk')) {
            return Config::get('constants.provider.cruk');
        }

        return Config::get('constants.provider.service');
    }
}

