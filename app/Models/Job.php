<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'responsibilities',
        'location',
        'job_type',
        'work_mode',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'vacancies',
        'deadline',
        'status',
        'views_count',
    ];

    protected $casts = [
        'deadline' => 'date',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'views_count' => 'integer',
        'vacancies' => 'integer',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skill')
                    ->withPivot('is_required')
                    ->withTimestamps();
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Get required skills
    public function getRequiredSkillsAttribute()
    {
        return $this->skills()->wherePivot('is_required', true)->get();
    }

    // Get optional skills
    public function getOptionalSkillsAttribute()
    {
        return $this->skills()->wherePivot('is_required', false)->get();
    }

    // Get applications count
    public function getApplicationsCountAttribute()
    {
        return $this->applications()->count();
    }

    // Check if job is active
    public function isActive()
    {
        return $this->status === 'published' && 
               ($this->deadline === null || $this->deadline->isFuture());
    }

    // Increment views
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                    ->where(function($q) {
                        $q->whereNull('deadline')
                          ->orWhere('deadline', '>=', now());
                    });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('job_type', $type);
    }

    public function scopeByWorkMode($query, $mode)
    {
        return $query->where('work_mode', $mode);
    }

    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }
}