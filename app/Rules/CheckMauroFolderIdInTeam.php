<?php

namespace App\Rules;

use Closure;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckMauroFolderIdInTeam implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $team = Team::where('id', (int) $value)->first()->toArray();

        if (empty($team['mdm_folder_id']) || !$team['mdm_folder_id']) {
            $fail('Mauro Data Mapper folder id for team ' . $value . ' not found');
        }
    }
}
