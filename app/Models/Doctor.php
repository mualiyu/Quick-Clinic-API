<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'language_id',
        'firstname',
        'lastname',
        'specialization',
        'licensenumber',
        'contactnumber',
        'address',
        'registereddate',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(LanguageSupport::class, "language_id", "id");
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
