<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'vault_id',
        'type',
        'encrypted_data',
        'iv',
        'tag',
    ];

    protected $hidden = [
        'encrypted_data',
        'iv',
        'tag',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
