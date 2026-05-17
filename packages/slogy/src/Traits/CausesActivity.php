<?php

namespace Sultonisky\Slogy\Traits;

use Sultonisky\Slogy\Models\ActivityLog;

trait CausesActivity
{
    public function activities()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}
