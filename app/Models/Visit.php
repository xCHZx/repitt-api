<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Visit extends Model
{
    use HasFactory;

    protected $table = 'visits';

    protected $fillable = [
        'visitable_id',
        'visitable_type',
        'user_id',
    ];

    // public function stamp_cards(): MorphToMany
    // {
    //     return $this->morphToMany(StampCard::class, 'visitable', 'visits', 'visitable_id', 'visitable_type', 'user_id');
    // }

    public function visitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
