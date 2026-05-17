<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sultonisky\Slogy\Traits\HasSlogy;


class Product extends Model
{

    use HasSlogy;

    protected $table = 'products';

    protected $fillable = [
        'name',
    ];
}
