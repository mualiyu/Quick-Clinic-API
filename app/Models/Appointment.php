<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'status',

        'description_of_problem',
        'attachment',
        'type',
        // Add these new fields
        'doctor_remark',
        'report_url',
        'prescription_url',

        'payment_reference',
        'meeting_link',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Add this method if you want to handle file uploads
    public function addFile($type, $file)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('appointments', $fileName, 'public/appointments');
        $this->update([$type . '_url' => '/storage/appointments/' . $filePath]);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_reference', 'reference');
    }
}
