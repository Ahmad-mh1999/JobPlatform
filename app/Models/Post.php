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
        'images' => 'array', // This allows storing images as JSON array
        'likes_count' => 'integer',
        'comments_count' => 'integer',
    ];

    protected $appends = ['images']; // Ensure images are always included in JSON

    // Accessors
    public function getImagesAttribute($value)
    {
        // Handle different storage formats
        if (is_array($value)) {
            return $value;
        }
        
        // Handle legacy string format (JSON string)
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        // Handle single image URL (string)
        if (is_string($value) && !empty($value) && !json_decode($value)) {
            return [$value]; // Convert single image to array
        }
        
        return []; // Default to empty array
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'user_id', 'user_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function getFirstImageAttribute()
    {
        $images = $this->images;
        return !empty($images) ? $images[0] : null;
    }

    public function getLastImageAttribute()
    {
        $images = $this->images;
        return !empty($images) ? end($images) : null;
    }

    public function hasImages()
    {
        return !empty($this->images);
    }

    public function getImageCountAttribute()
    {
        return count($this->images);
    }

    // Methods for image manipulation
    public function addImage($imageUrl)
    {
        $images = $this->images ?? [];
        $images[] = $imageUrl;
        $this->images = $images;
        return $this->save();
    }

    public function removeImage($index)
    {
        $images = $this->images ?? [];
        if (isset($images[$index])) {
            unset($images[$index]);
            $this->images = array_values($images); // Re-index array
            return $this->save();
        }
        return false;
    }

    public function setImages($imageUrls)
    {
        $this->images = is_array($imageUrls) ? $imageUrls : [];
        return $this->save();
    }

    public function getUserImageAttribute()
    {
        if ($this->user) {
            return $this->user->profile_image;
        }
        
        if ($this->company) {
            return $this->company->logo;
        }
        
        return null;
    }

    public function getUserNameAttribute()
    {
        if ($this->user) {
            return $this->user->name;
        }
        
        if ($this->company) {
            return $this->company->company_name;
        }
        
        return 'مستخدم';
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

    public function scopeSaved($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->whereHas('savedByUsers', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}