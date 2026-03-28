<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicExam extends Model
{
    protected $fillable = ['school_id', 'short_name', 'full_name', 'status'];
}
