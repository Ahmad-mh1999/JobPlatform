<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_profile_id',
        'degree',
        'field_of_study',
        'institution',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'description',
        'grade',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'grade' => 'decimal:2',
    ];

    // Relationships
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    // Scope to get current education
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    // Scope to order by date
    public function scopeOrderByDate($query, $direction = 'desc')
    {
        return $query->orderBy('start_date', $direction);
    }
}