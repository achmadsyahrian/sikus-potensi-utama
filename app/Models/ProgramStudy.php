<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_study_code',
        'name',
        'faculty_code',
        'degree_level',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_last_synced_at',
    ];

    public function getFormattedLastSyncedAtAttribute()
    {
        Carbon::setLocale('id');
        return $this->last_synced_at ? Carbon::parse($this->last_synced_at)->translatedFormat('l, d F Y H:i') : null;
    }
}
