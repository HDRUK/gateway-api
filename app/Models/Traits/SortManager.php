<?php

namespace App\Models\Traits;

trait SortManager
{
    public function scopeApplySorting($query): mixed
    {
        $input = \request()->all();
        // If no sort option passed, then always default to the first element
        // of our sortableColumns array on the model
        $sort = isset($input['sort']) ? $input['sort'] : static::$sortableColumns[0] . ':desc';

        $tmp = explode(':', $sort);
        $field = strtolower($tmp[0]);

        if (isset(static::$sortableColumns) && !in_array(strtolower($field), static::$sortableColumns)) {
            throw new \InvalidArgumentException('field ' . $field . ' is not sortable.');
        }

        $direction = strtolower($tmp[1]);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('invalid sort direction ' . $direction);
        }

        return $query->orderBy($field, $direction);
    }
}
