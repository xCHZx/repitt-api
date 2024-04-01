<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
}
