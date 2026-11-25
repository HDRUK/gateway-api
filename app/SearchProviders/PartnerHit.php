<?php

namespace App\SearchProviders;

readonly class PartnerHit
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public ?string $organisation,
        public string $url,
    ) {
    }

    /**
     * @return array{id: string, title: string, description: string, organisation: ?string, url: string}
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'organisation' => $this->organisation,
            'url'          => $this->url,
        ];
    }
}
