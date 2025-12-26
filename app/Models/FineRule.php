<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FineRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_category_id', 'type', 'rate', 'max_cap', 'active',
    ];
}
