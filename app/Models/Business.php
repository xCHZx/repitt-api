<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'businesses';

    protected $fillable = [
        'name',
        'description',
        'address',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'businesses_users', 'business_id', 'user_id');
    }

    public function stamp_cards(): HasMany
    {
        return $this->hasMany(StampCard::class);
    }

    public function visits(): HasManyThrough
    {
        return $this->hasManyThrough(
            Visit::class,
            StampCard::class,
            'business_id', // Foreign key on StampCard table...
            'visitable_id', // Foreign key on visits table...
            'id', // Local key on businesses table...
            'id' // Local key on stamp_cards table...
        )->where('visitable_type', StampCard::class);
    }
}
