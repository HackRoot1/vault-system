<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileItem extends Model
{
    protected $fillable = [
        'vault_id',
        'file_name',
        'file_path',
        'iv',
        'tag',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
