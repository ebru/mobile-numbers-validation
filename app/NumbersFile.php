<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NumbersFile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_hash_name', 'original_file_path', 'modified_file_path', 'total_numbers_count', 'valid_numbers_count', 'corrected_numbers_count', 'not_valid_numbers_count',
    ];
}
