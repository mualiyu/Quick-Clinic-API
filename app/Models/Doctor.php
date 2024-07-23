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
        'first_name',
        'last_name',
        'specialization',
        'license_number',
        'phone',
        'address',
        'gender',

        'education_qualifications',
        'years_of_experience',
        'doctor_description',
        'basic_pay_amount',

        'id_card',
        'license_document',
        'document1',
        'document2',
        'document3',
        'document4',
        'document5',

        'registered_date',
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
