<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sultonisky\Slogy\Traits\HasSlogy;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{

    use HasSlogy, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
    ];

    public $slogyLabel = 'name';
}
