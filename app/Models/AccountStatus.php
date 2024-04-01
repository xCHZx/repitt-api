<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountStatus extends Model
{
    use HasFactory;

    protected $table = 'account_statuses';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'account_status_id');
    }
}
