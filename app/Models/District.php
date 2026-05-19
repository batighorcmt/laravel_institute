<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    protected $fillable = ['name', 'bn_name', 'division_id', 'status'];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function thanas()
    {
        return $this->hasMany(Thana::class, 'district_id');
    }
}
