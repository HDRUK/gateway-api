<?php

namespace App\Http\Traits;

trait CheckAccess
{
    /**
     * Check Access
     *
     * @param array $input
     * @param integer $currentUserId This is the User Id coming from jwt
     * @param integer $dbTeamId This is the Team Id coming from database
     * @param integer $dbUserId This is the User Id coming from database
     * @param string $checkType Expect like values team or user or both
     * @return bool
     */
    public function checkAccess(
        array $input = [], 
        int $currentUserId = null, 
        int $dbTeamId = null, 
        int $dbUserId = null, 
        string $checkType = null
        )
    {


        return true;
    }
}