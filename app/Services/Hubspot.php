<?php

namespace App\Services;

use Config;
use Exception;
use Illuminate\Support\Facades\Http;

class Hubspot
{
    protected $baseUrl;
    protected $header;

    public function __construct()
    {
        $this->baseUrl = Config::get('services.hubspot.base_url');
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . Config::get('services.hubspot.key'),
        ];
    }

    public function createContact(array $properties)
    {
        try {
            $url = $this->baseUrl . '/contacts/v1/contact';

            $body = [
                'properties' => $this->convertProperties($properties),
            ];

            $response = Http::withHeaders($this->header)->post($url, $body);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateContactById(int $id, array $properties)
    {
        try {
            $url = $this->baseUrl . '/contacts/v1/contact/vid/' . $id . '/profile';

            $body = [
                'properties' => $this->convertProperties($properties),
            ];

            $response = Http::withHeaders($this->header)->post($url, $body);

            // Returns a 204 No Content response on success
            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getContactByEmail(string $email)
    {
        try {
            $url = $this->baseUrl . '/contacts/v1/contact/email/' . $email . '/profile';

            $response = Http::withHeaders($this->header)->get($url);

            $responseBody = $response->json();

            return (!is_array($responseBody)) ? null : (array_key_exists('vid', $responseBody) ? $responseBody['vid'] : null);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getContactById(int $id)
    {
        try {
            $url = $this->baseUrl . '/contacts/v1/contact/vid/' . $id . '/profile';

            $response = Http::withHeaders($this->header)->get($url);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteContactById($id)
    {
        try {
            $url = $this->baseUrl . '/contacts/v1/contact/vid/' . $id;

            $response = Http::withHeaders($this->header)->delete($url);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function convertProperties(array $properties)
    {
        $return = [];

        foreach ($properties as $key => $value) {
            $return[] = [
                'property' => $key,
                'value' => $value,
            ];
        }

        return $return;
    }
}
