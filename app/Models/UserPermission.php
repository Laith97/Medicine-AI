<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_id',
        'granted_by',
    ];

    /**
     * The user who has this permission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * The user who granted this permission
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}