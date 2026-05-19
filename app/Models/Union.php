<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    protected $table = 'unions';
    protected $fillable = ['name', 'bn_name', 'thana_id', 'status'];

    public function thana()
    {
        return $this->belongsTo(Thana::class, 'thana_id');
    }
}
