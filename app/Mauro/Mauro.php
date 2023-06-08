<?php

namespace App\Mauro;

use App\Exceptions\MauroServiceException;

use Illuminate\Support\Facades\Http;

class Mauro {
    public function createFolder(string $label, string $description, string $parentFolderId = ""): string
    {
        $postUrl = env('MAURO_API_URL');

        if ($parentFolderId !== "") {
            $postUrl .= '/' . $parentFolderId . '/folders';
        } else {
            $postUrl .= '/folders';
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $label,
                'description' => $description,
            ]);
            var_dump($response->json());
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }
}