<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'name',
        'academic_year',
        'semester',
        'start_date',
        'end_date',
        'is_active',
        'last_synced_at'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    protected $appends = [
        'formatted_last_synced_at',
        'formatted_start_date',
        'formatted_end_date'
    ];

    public function getFormattedLastSyncedAtAttribute()
    {
        Carbon::setLocale('id');
        return $this->last_synced_at ? Carbon::parse($this->last_synced_at)->translatedFormat('l, d F Y H:i') : null;
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
