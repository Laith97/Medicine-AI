<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'criterion', 'specialty', 'notification_volume'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
