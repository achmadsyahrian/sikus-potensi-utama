<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatisfactionCriterion extends Model
{
    use HasFactory;

    protected $table = 'satisfaction_criteria';

    protected $fillable = [
        'label',
        'min_value',
        'max_value',
        'color',
    ];
}
