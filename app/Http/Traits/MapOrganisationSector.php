<?php

namespace App\Http\Traits;

use Config;
use App\Models\Sector;

trait MapOrganisationSector
{
    /**
     * Map the input string to the index of one of the standard mapped sector names.
     *
     * Return null if not found.
     * @return ?int
     */
    public function mapOrganisationSector(string $organisationSector): ?int
    {
        $sector = strtolower($organisationSector);
        $categories = Sector::all();

        // Look up mapped sector, with default to null
        $category = Config::get('sectors.' . $sector, null);

        return (!is_null($category)) ? $categories->where('name', $category)->first()['id'] : null;
    }
}
