<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientVital extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'vital_type',
        'value',
        'timestamp',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
