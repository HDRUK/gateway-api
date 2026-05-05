<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'] ?? null;

        $name = null;
        if (is_object($user)) {
            $name = $user->name ?? trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
        } elseif (is_array($user)) {
            $name = $user['name'] ?? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }

        return [
            'access_token' => $this->resource['access_token'] ?? null,
            'token_type' => $this->resource['token_type'] ?? 'bearer',
            'user' => is_object($user) ? [
                'id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'name' => $name,
            ] : (is_array($user) ? [
                'id' => $user['id'] ?? null,
                'email' => $user['email'] ?? null,
                'name' => $name,
            ] : null),
        ];
    }
}
