<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'title',
        'slug',
        'short_description',
        'content',
        'featured_image',
        'is_published',
        'published_at',
        'seo_title',
        'seo_description',
        'seo_meta',
        'views_count',
        'reading_time',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'seo_meta' => 'array',
        'views_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }

            if (empty($post->reading_time)) {
                $post->reading_time = static::calculateReadingTime($post->content);
            }

            if (empty($post->seo_title)) {
                $post->seo_title = $post->title;
            }

            if (empty($post->seo_description)) {
                $post->seo_description = $post->short_description;
            }

            if ($post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = static::generateUniqueSlug($post->title, $post->id);
            }

            if ($post->isDirty('content')) {
                $post->reading_time = static::calculateReadingTime($post->content);
            }

            if ($post->isDirty('is_published')) {
                if ($post->is_published && !$post->published_at) {
                    $post->published_at = now();
                } elseif (!$post->is_published) {
                    $post->published_at = null;
                }
            }
        });
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function generateUniqueSlug($title, $excludeId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
                    ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
                    ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public static function calculateReadingTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute

        return $readingTime . ' min read';
    }

    public function getSeoTitleAttribute($value)
    {
        return $value ?: $this->title;
    }

    public function getSeoDescriptionAttribute($value)
    {
        return $value ?: $this->short_description;
    }

    public function getExcerptAttribute()
    {
        return Str::limit(strip_tags($this->content), 200);
    }

    public function getFormattedPublishedDateAttribute()
    {
        return $this->published_at ? $this->published_at->format('F j, Y') : null;
    }

    public function getEstimatedReadingTimeAttribute()
    {
        return $this->reading_time ?: static::calculateReadingTime($this->content);
    }
}
