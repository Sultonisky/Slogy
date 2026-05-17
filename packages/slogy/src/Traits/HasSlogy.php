<?php

namespace Sultonisky\Slogy\Traits;

use Sultonisky\Slogy\Models\ActivityLog;

trait HasSlogy {

    protected static function bootHasSlogy(): void {

        static::created(function ($model) {

            ActivityLog::create([
                'user_id'       => auth()->id(),
                'action'        => 'created',
                'model'         => get_class($model),
                'model_id'      => $model->id,
                'new_values'    => json_encode($model->toArray()),
            ]);

        });

    }


}