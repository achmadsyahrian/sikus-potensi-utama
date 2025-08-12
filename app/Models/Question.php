<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'category_id',
        'question_text',
        'question_type',
        'is_required',
        'order',
    ];

    protected $with = ['category'];

    protected $appends = ['formatted_question_type'];

    public function getFormattedQuestionTypeAttribute(): string
    {
        return [
            'multiple_choice' => 'Pilihan Ganda',
            'text' => 'Teks Bebas',
        ][$this->question_type] ?? 'Tidak Diketahui';
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
