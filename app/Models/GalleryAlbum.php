<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'description',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }
}
