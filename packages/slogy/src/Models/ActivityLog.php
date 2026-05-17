<?php

namespace Sultonisky\Slogy\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model',
        'model_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(config('slogy.user_model', 'App\Models\User'));
    }

    public function subject()
    {
        return $this->morphTo('subject', 'model', 'model_id');
    }
}