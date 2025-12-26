<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_common', 'frequency', 'active',
    ];

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }
}
