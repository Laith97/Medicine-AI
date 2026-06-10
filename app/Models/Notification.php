<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /**
     * @property string $id
     * @property string $type
     * @property string $notifiable_type
     * @property int $notifiable_id
     * @property array $data
     * @property \Carbon\Carbon|null $read_at
     */

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Get the entity that owns the notification.
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        if ($this->read_at) {
            $this->update(['read_at' => null]);
        }
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications of a specific type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications related to a specific model.
     */
    public function scopeRelatedTo($query, $type, $id)
    {
        return $query->where('data->related_type', $type)
                    ->where('data->related_id', $id);
    }

    /**
     * Scope for notifications for a specific user.
     */
    public function scopeForUser($query, $user)
    {
        return $query->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id);
    }

    /**
     * Get the icon class based on the notification type.
     */
    public function getIconClass(): string
    {
        return match($this->data['icon'] ?? 'info') {
            'success' => 'fas fa-check-circle text-success',
            'warning' => 'fas fa-exclamation-triangle text-warning',
            'error' => 'fas fa-times-circle text-danger',
            'info' => 'fas fa-info-circle text-info',
            default => 'fas fa-bell text-info',
        };
    }

    /**
     * Get the time ago for the notification.
     */
    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if the notification has a link.
     */
    public function hasLink(): bool
    {
        return !empty($this->data['link']);
    }

    /**
     * Get the related model if it exists.
     */
    public function getRelatedModel()
    {
        $relatedType = $this->data['related_type'] ?? null;
        $relatedId = $this->data['related_id'] ?? null;

        if (empty($relatedType) || empty($relatedId)) {
            return null;
        }

        $modelClass = '\\App\\Models\\' . ucfirst($relatedType);

        if (class_exists($modelClass)) {
            return $modelClass::find($relatedId);
        }

        return null;
    }

    /**
     * Get the notification's title from data
     */
    public function getTitleAttribute()
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get the notification's message from data
     */
    public function getMessageAttribute()
    {
        return $this->data['message'] ?? 'You have a new notification.';
    }

    /**
     * Get the notification's icon from data
     */
    public function getIconAttribute()
    {
        return $this->data['icon'] ?? 'bell';
    }

    /**
     * Get the notification's link from data
     */
    public function getLinkAttribute()
    {
        return $this->data['link'] ?? null;
    }

    /**
     * Get the notification's link text from data
     */
    public function getLinkTextAttribute()
    {
        return $this->data['link_text'] ?? 'View';
    }

    /**
     * Get the notification's related type from data
     */
    public function getRelatedTypeAttribute()
    {
        return $this->data['related_type'] ?? null;
    }

    /**
     * Get the notification's related ID from data
     */
    public function getRelatedIdAttribute()
    {
        return $this->data['related_id'] ?? null;
    }

    /**
     * Get the user associated with this notification
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
