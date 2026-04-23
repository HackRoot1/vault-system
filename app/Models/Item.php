<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

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

    protected $dates = [
        'deleted_at',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
