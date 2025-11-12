<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'images',
        'video',
        'type',
        'job_id',
        'likes_count',
        'comments_count',
        'visibility',
    ];

    protected $casts = [
        'images' => 'array',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Increment likes
    public function incrementLikes()
    {
        $this->increment('likes_count');
    }

    // Decrement likes
    public function decrementLikes()
    {
        $this->decrement('likes_count');
    }

    // Increment comments
    public function incrementComments()
    {
        $this->increment('comments_count');
    }

    // Increment shares
    public function incrementShares()
    {
        $this->increment('shares_count');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}