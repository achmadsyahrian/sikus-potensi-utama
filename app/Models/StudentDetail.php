<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'domicile_address',
        'study_program',
        'phone_number',
    ];
}
