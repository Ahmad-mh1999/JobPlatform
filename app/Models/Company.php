<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'category',
        'website',
        'location',
        'description',
        'logo',
        'cover_image',
        'founded_year',
        'social_links',
        'is_verified',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_verified' => 'boolean',
        'founded_year' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    // Get active jobs count
    public function getActiveJobsCountAttribute()
    {
        return $this->jobs()->where('status', 'published')->count();
    }

    // Get total applications count
    public function getTotalApplicationsCountAttribute()
    {
        return Application::whereIn('job_id', $this->jobs->pluck('id'))->count();
    }

    // Scope to search companies
    public function scopeSearch($query, $term)
    {
        return $query->where('company_name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }

    // Scope to filter by industry
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    // Scope to get verified companies
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}