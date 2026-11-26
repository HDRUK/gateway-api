<?php

namespace App\Modules\Concerns;

use App\Modules\ModuleManager;
use App\Modules\ModuleScope;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Gives a model, controller, observer or job access to enrichment modules.
 *
 *   On a model:        $dataset->modules()->resolve();
 *   Anywhere else:     $this->modules($dataset, ['wave' => 3])->refresh();
 *
 * The using class is recorded as the origin of the request.
 */
trait HasModules
{
    public function modules(?Model $subject = null, array $params = []): ModuleScope
    {
        $subject ??= $this instanceof Model
            ? $this
            : throw new LogicException('Pass a subject to modules() when calling it from outside a model.');

        return app(ModuleManager::class)->for($subject, $params, static::class);
    }
}
