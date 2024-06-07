<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserStampCard extends Model
{
    use HasFactory;

    protected $table = 'user_stamp_cards';

    protected $fillable = [
        'user_id',
        'stamp_card_id',
        'visits_count',
        'is_active',
        'is_reward_redeemed',
    ];

    public function stamp_card(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
