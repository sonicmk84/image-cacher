<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Thumbnail extends Model
{
    protected $fillable = [
        'pornstar_id',
        'type',
        'url',
        'local_path',
        'width',
        'height',
    ];

    protected $appends = ['local_url'];

    public function getLocalUrlAttribute(): string
    {
        return asset('storage/' . $this->local_path);
    }

    public function pornstar(): BelongsTo
    {
        return $this->belongsTo(Pornstar::class);
    }
}