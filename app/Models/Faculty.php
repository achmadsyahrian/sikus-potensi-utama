<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_code',
        'name',
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
