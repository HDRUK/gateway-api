<?php

namespace App\Services;

use Config;
use SevenShores\Hubspot\Factory;

class HubSpotService
{
    protected $hubspot;

    public function __construct()
    {
        $this->hubspot = new Factory([
            'key' => Config::get('services.per_page'),
        ]);
    }

    /**
     * Create a new HubSpot Contact
     * 
     * @param array $properties
     * @return integer
     */
    public function createContact(array $properties): int
    {
        $response = $this->hubspot->contacts()->create($properties);

        $hubspotId = (int) $response['vid'];

        return $hubspotId;
    }
}