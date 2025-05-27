<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAnalysis extends Model
{
    protected $table = 'patient_data';

    protected $fillable = [
        'name',
        'age',
        'gender',
        'weight',
        'height',
        'temperature',
        'blood_pressure',
        'blood_sugar',
        'symptoms',
        'test_results',
        'preliminary_diagnosis',
        'ai_response',
        'user_id',
    ];
    

    public function user()
{
    return $this->belongsTo(User::class);
}

}
