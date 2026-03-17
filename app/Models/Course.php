<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration',
        'tuition_fee',
        'target_student',
    ];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
    ];

}
