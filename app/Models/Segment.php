<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segment extends Model
{
    use HasFactory;

    protected $table = 'segments';

    public function stamp_cards(): HasMany
    {
        return $this->hasMany(Business::class, 'segment_id');
    }

}
