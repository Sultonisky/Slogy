<?php

namespace Sultonisky\Slogy\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}