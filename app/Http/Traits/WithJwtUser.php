<?php

namespace App\Http\Traits;

trait WithJwtUser
{
    /**
     * Filter by user
     *
     * @return mixed
     */
    public function scopeGetAll($query, $userIdColumn, $jwtUser)
    {
        if (!count($jwtUser)) {
            return $query;
        }

        $userId = $jwtUser['id'];
        $userIsAdmin = (bool) $jwtUser['is_admin'];

        if (!$userIsAdmin) {
            return $query->where($userIdColumn, $userId);
        }

        return $query;
    }
}
