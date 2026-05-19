<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thana extends Model
{
    protected $table = 'thanas';
    protected $fillable = ['name', 'bn_name', 'district_id', 'status'];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function unions()
    {
        return $this->hasMany(Union::class, 'thana_id');
    }
}
