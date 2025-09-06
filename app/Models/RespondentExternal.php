<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespondentExternal extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'name',
        'company_or_institution',
        'contact_number',
    ];
}
