<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'target_type',
        'target_value',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }
}
