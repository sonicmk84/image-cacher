<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pornstar extends Model
{
    protected $fillable = [
        'id',
        'name',
        'link',
        'attributes',
        'aliases',
        'license',
        'wl_status',
    ];

    public function thumbnails(): HasMany
    {
        return $this->hasMany(Thumbnail::class);
    }
}