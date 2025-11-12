<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'summary',
        'location',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'cv_file',
        'years_of_experience',
        'expected_salary',
        'is_available',
        'languages',
    ];

    protected $casts = [
        'languages' => 'array',
        'is_available' => 'boolean',
        'expected_salary' => 'integer',
        'years_of_experience' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skill')
                    ->withPivot('level')
                    ->withTimestamps();
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // Get all skill names as array
    public function getSkillNamesAttribute()
    {
        return $this->skills->pluck('name')->toArray();
    }

    // Get completion percentage
    public function getProfileCompletenessAttribute()
    {
        $fields = [
            'title', 'bio', 'summary', 'location', 'cv_file',
            'years_of_experience'
        ];
        
        $filledFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filledFields++;
            }
        }
        
        // Add bonus for having skills, education, experience
        if ($this->skills->count() > 0) $filledFields++;
        if ($this->education->count() > 0) $filledFields++;
        if ($this->experiences->count() > 0) $filledFields++;
        
        return round(($filledFields / 9) * 100);
    }
}