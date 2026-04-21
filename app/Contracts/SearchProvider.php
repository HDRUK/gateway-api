<?php

namespace App\Contracts;

interface SearchProvider
{
    public function getFullName(): string;
    public function getShortName(): string;
    public function getProviderLogo(): string|null;
    public function getProviderBlurb(): string|null;
    public function getSearchURI(): string;
    public function search(string $query): array;
}
