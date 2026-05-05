<?php

namespace App\Services;

use Config;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\JwtController;

class CrukAuthService
{
    public function __construct(private readonly JwtController $jwt)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function register(array $input): array
    {
        $provider = $this->resolveProvider($input);

        $firstname = $input['firstname'] ?? null;
        $lastname = $input['lastname'] ?? null;
        $name = trim(($firstname ?? '') . ' ' . ($lastname ?? ''));

        $user = User::create([
            'name' => $name !== '' ? $name : ($input['email'] ?? ''),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $input['email'],
            'provider' => $provider,
            'password' => Hash::make($input['password']),
            'is_admin' => 0,
        ]);

        $accessToken = $this->jwt->generateToken($user->id);

        return [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'user' => $user,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function login(array $input): array
    {
        $provider = $this->resolveProvider($input);

        $user = User::where('email', $input['email'])
            ->where('provider', $provider)
            ->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            throw new UnauthorizedException('Invalid credentials');
        }

        $accessToken = $this->jwt->generateToken($user->id);

        return [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'user' => $user,
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveProvider(array $input): string
    {
        $serviceProvider = Config::get('constants.provider.service');
        $crukProvider = Config::get('constants.provider.cruk');

        if (!empty($input['provider']) && $input['provider'] === $crukProvider) {
            return $crukProvider;
        }

        if ($serviceProvider === null) {
            throw new Exception('Provider configuration missing');
        }

        return $serviceProvider;
    }
}
