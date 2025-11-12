<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_profile_id',
        'job_title',
        'job_type',
        'company_name',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    // Relationships
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    // Get duration in months
    public function getDurationInMonthsAttribute()
    {
        $end = $this->is_current ? now() : $this->end_date;
        return $this->start_date->diffInMonths($end);
    }

    // Get formatted duration
    public function getFormattedDurationAttribute()
    {
        $months = $this->duration_in_months;
        $years = floor($months / 12);
        $remainingMonths = $months % 12;
        
        if ($years > 0 && $remainingMonths > 0) {
            return "{$years} سنة و {$remainingMonths} شهر";
        } elseif ($years > 0) {
            return "{$years} سنة";
        } else {
            return "{$remainingMonths} شهر";
        }
    }

    // Scope to get current experiences
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