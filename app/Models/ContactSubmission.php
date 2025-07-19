<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'service',
        'subject',
        'message',
        'is_read',
        'submitted_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'submitted_at' => 'datetime'
    ];

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('submitted_at', 'desc');
    }
}
