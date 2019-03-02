<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Number extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number_id', 'number_value', 'is_valid', 'is_modified', 'before_modified_value',
    ];
}
