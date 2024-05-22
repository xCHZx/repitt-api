<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDetails extends Model
{
    use HasFactory;
    protected $table = 'account_details';

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }
}
