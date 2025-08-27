<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'onit_reference',
        'amount',
        'status',
        'channel',
        'narration',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
