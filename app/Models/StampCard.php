<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class StampCard extends Model
{
    use HasFactory;

    protected $table = 'stamp_cards';

    protected $fillable = [
        'name',
        'description',
        'required_stamps',
        'start_date',
        'end_date',
        'stamp_icon_path',
        'primary_color',
        'business_id',
        'reward',
    ];

    public function visits(): MorphMany
    {
        return $this->morphMany(Visit::class, 'visitable');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }


}
