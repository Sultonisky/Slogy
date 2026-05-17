<?php

return [ 
    'events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
    ],

    'ignored_attributes' => [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
    ],

    'user_model' => 'App\Models\User',

    'log_retention_days' => 30,
];