<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_period_id',
        'name',
        'description',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $with = ['academicPeriod', 'targets', 'categories', 'options'];

    protected $appends = [
        'formatted_start_date',
        'formatted_end_date'
    ];

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(QuestionnaireTarget::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(QuestionCategory::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function getFormattedStartDateAttribute()
    {
        Carbon::setLocale('id');
        return $this->start_date ? Carbon::parse($this->start_date)->translatedFormat('d F Y') : null;
    }

    public function getFormattedEndDateAttribute()
    {
        Carbon::setLocale('id');
        return $this->end_date ? Carbon::parse($this->end_date)->translatedFormat('d F Y') : null;
    }
}
