<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message'
    ];

    protected $hidden = [
        'sender_type',
        'conversation_id',
    ];

    public function conversation() : BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sensder()
    {
        return $this->morphTo();
    }
}
