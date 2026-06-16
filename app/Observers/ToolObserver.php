<?php

namespace App\Observers;

use App\Models\Tool;

class ToolObserver
{
    /**
     * Handle the Tool "created" event.
     */
    public function created(Tool $tool): void
    {
        if (!is_null($tool) && $tool->status === Tool::STATUS_ACTIVE && $tool->active_date === null) {
            $tool->active_date = now();
            $tool->withoutEvents(function () use ($tool) {
                $tool->save();
            });
        }
    }

    /**
     * Handle the Tool "updated" event.
     */
    public function updated(Tool $tool): void
    {
        if (!is_null($tool) && $tool->status === Tool::STATUS_ACTIVE && $tool->active_date === null) {
            $tool->active_date = now();
            $tool->withoutEvents(function () use ($tool) {
                $tool->save();
            });
        }
    }
}
