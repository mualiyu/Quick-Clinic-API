<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id'
    ];

    protected $hidden = [
        'patient_id',
        'doctor_id'
    ];

    public function patient() : BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor() : BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function messages() : HasMany
    {
        return $this->hasMany(Message::class);
    }
}
