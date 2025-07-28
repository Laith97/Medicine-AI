<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DoctorBlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'title',
        'slug',
        'featured_image',
        'short_description',
        'content',
        'is_published',
        'published_at',
        'seo_meta',
        'views_count'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'seo_meta' => 'array',
        'views_count' => 'integer'
    ];

    protected $dates = [
        'published_at',
        'deleted_at'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);

                // Ensure unique slug
                $originalSlug = $post->slug;
                $counter = 1;
                while (static::where('slug', $post->slug)->exists()) {
                    $post->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('is_published') && $post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
        });
    }

    /**
     * Get the doctor that owns the blog post
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for recent posts
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('published_at', 'desc')->limit($limit);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the SEO title
     */
    public function getSeoTitleAttribute()
    {
        return $this->seo_meta['title'] ?? $this->title;
    }

    /**
     * Get the SEO description
     */
    public function getSeoDescriptionAttribute()
    {
        return $this->seo_meta['description'] ?? $this->short_description;
    }

    /**
     * Get the reading time estimate
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // Average reading speed
        return $readingTime . ' min read';
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get excerpt from content
     */
    public function getExcerptAttribute($length = 150)
    {
        return Str::limit(strip_tags($this->content), $length);
    }
}
