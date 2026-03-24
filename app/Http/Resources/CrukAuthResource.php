<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CrukAuthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'access_token' => $this['access_token'] ?? null,
            'token_type' => $this['token_type'] ?? 'bearer',
            'user' => CrukAuthUserResource::make($this['user'] ?? null),
        ];
    }
}

