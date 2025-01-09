<?php

namespace App\Models\Traits;

trait EntityCounter
{
    public function scopeApplyCount($query): mixed
    {
        $input = \request()->all();
        // If no count option passed, then always default to the first element
        // of our countableColumns array on the model
        $field = isset($input['field']) ? $input['field'] : static::$countableColumns[0];

        if (isset(static::$countableColumns) && !in_array(strtolower($field), static::$countableColumns)) {
            throw new \InvalidArgumentException('field ' . $field . ' is not countable.');
        }

        return $query->select($field)
            ->get()
            ->groupBy($field)
            ->map->count();
    }
}
